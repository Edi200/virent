<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Events\VehicleAvailabilityChanged;
use App\Exceptions\VehicleUnavailableException;
use App\Mail\BookingCreatedMailable;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\Extra;
use App\Models\Vehicle;
use App\Services\BookingContractService;
use App\Services\BookingService;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly PricingService $pricingService,
    ) {}

    public function index(Request $request): RedirectResponse|Response
    {
        if ($request->user()->isAdmin() || $request->user()->isStaff()) {
            return redirect()->route('profile.edit');
        }

        $customer = $request->user()->ensureCustomerRecord();

        $bookings = $customer->bookings()
            ->with('vehicle')
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Booking $booking): array => [
                'id' => $booking->id,
                'reference' => $booking->reference(),
                'vehicle' => [
                    'name' => $booking->vehicle->name,
                ],
                'start_date' => $booking->start_date->format('Y-m-d'),
                'end_date' => $booking->end_date->format('Y-m-d'),
                'status' => $booking->status->value,
                'total_price' => $booking->total_price,
            ]);

        return Inertia::render('Bookings/Index', [
            'bookings' => $bookings,
        ]);
    }

    public function create(Vehicle $vehicle): Response
    {
        $this->ensureVehicleAvailable($vehicle);

        $vehicle->load('category');

        $extras = Extra::query()->orderBy('name')->get();

        return Inertia::render('Booking/Create', [
            'vehicle' => [
                'id' => $vehicle->id,
                'slug' => $vehicle->slug,
                'name' => $vehicle->name,
                'daily_rate' => $vehicle->daily_rate,
                'available_with_operator' => $vehicle->available_with_operator,
                'operator_daily_rate' => $vehicle->operator_daily_rate,
                'category' => [
                    'name' => $vehicle->category->name,
                    'slug' => $vehicle->category->slug,
                ],
            ],
            'extras' => $extras->map(fn (Extra $extra): array => [
                'id' => $extra->id,
                'name' => $extra->name,
                'price' => $extra->price,
                'price_type' => $extra->price_type->value,
            ])->values()->all(),
        ]);
    }

    public function unavailableDates(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->ensureVehicleAvailable($vehicle);

        $windowStart = Carbon::today()->startOfDay();
        $windowEnd = $windowStart->copy()->addMonthsNoOverflow(12)->addDay();
        $excludeUserId = optional($request->user())->id;

        $blockedRanges = collect($vehicle->blockedDateRanges($windowStart, $windowEnd, $excludeUserId))
            ->map(function (array $range) use ($windowStart, $windowEnd): array {
                $clampedStart = Carbon::parse($range['start'])->max($windowStart);
                $clampedEndExclusive = Carbon::parse($range['end'])->min($windowEnd);
                $clampedEndInclusive = $clampedEndExclusive->copy()->subSecond();

                return [
                    'start_date' => $clampedStart->toDateString(),
                    'end_date' => $clampedEndInclusive->toDateString(),
                ];
            })
            ->filter(fn (array $range): bool => $range['start_date'] <= $range['end_date'])
            ->values()
            ->all();

        return response()->json($blockedRanges);
    }

    public function pricePreview(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->ensureVehicleAvailable($vehicle);

        $validated = $this->validateBookingInput($request, $vehicle);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->startOfDay();
        $withOperator = (bool) ($validated['with_operator'] ?? false);
        $extras = $this->resolveExtras($validated['extras'] ?? []);

        $vehicle->loadMissing('category');

        $pricing = $this->pricingService->calculate($vehicle, $start, $end, $withOperator, $extras);

        return response()->json([
            ...$pricing,
            'available' => $vehicle->isAvailableBetween($start, $end, $request->user()->id),
        ]);
    }

    public function storeHold(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->ensureVehicleAvailable($vehicle);

        $validated = $this->validateDateRangeInput($request);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->startOfDay();

        if (! $vehicle->isAvailableBetween($start, $end, $request->user()->id)) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => [
                    'dates' => [__('This vehicle is not available for the selected dates.')],
                ],
            ], 422);
        }

        $hold = BookingHold::query()->updateOrCreate(
            [
                'vehicle_id' => $vehicle->id,
                'user_id' => $request->user()->id,
            ],
            [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'expires_at' => now()->addMinutes(10),
            ],
        );

        VehicleAvailabilityChanged::dispatch($vehicle->id);

        return response()->json([
            'start_date' => $hold->start_date->toDateString(),
            'end_date' => $hold->end_date->toDateString(),
            'expires_at' => $hold->expires_at->toIso8601String(),
        ]);
    }

    public function destroyHold(Request $request, Vehicle $vehicle): HttpResponse
    {
        $this->ensureVehicleAvailable($vehicle);

        BookingHold::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        VehicleAvailabilityChanged::dispatch($vehicle->id);

        return response()->noContent();
    }

    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->ensureVehicleAvailable($vehicle);

        $validated = $this->validateBookingInput($request, $vehicle);

        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->startOfDay();
        $withOperator = (bool) ($validated['with_operator'] ?? false);
        $extras = $this->resolveExtras($validated['extras'] ?? []);

        $customer = $request->user()->ensureCustomerRecord();

        try {
            $booking = $this->bookingService->create(
                $vehicle,
                $customer,
                $start,
                $end,
                $withOperator,
                $extras,
            );
        } catch (VehicleUnavailableException) {
            return back()->withErrors([
                'dates' => __('This vehicle is not available for the selected dates.'),
            ]);
        }

        Mail::to($request->user())->send(new BookingCreatedMailable(
            $booking->load(['vehicle', 'customer.user', 'extras']),
        ));

        return redirect()->route('bookings.show', $booking);
    }

    public function show(Booking $booking): Response
    {
        $booking->load(['vehicle.category', 'customer', 'extras']);

        abort_unless($booking->customer->user_id === auth()->id(), 403);

        return Inertia::render('Booking/Show', [
            'booking' => [
                'id' => $booking->id,
                'reference' => $booking->reference(),
                'status' => $booking->status->value,
                'start_date' => $booking->start_date->format('Y-m-d'),
                'end_date' => $booking->end_date->format('Y-m-d'),
                'total_price' => $booking->total_price,
                'deposit_amount' => $booking->deposit_amount,
                'with_operator' => $booking->with_operator,
                'pricing_breakdown' => $booking->pricing_breakdown ?? [],
            ],
            'vehicle' => [
                'slug' => $booking->vehicle->slug,
                'name' => $booking->vehicle->name,
                'category' => [
                    'name' => $booking->vehicle->category->name,
                    'slug' => $booking->vehicle->category->slug,
                ],
            ],
        ]);
    }

    public function contract(Booking $booking, BookingContractService $contracts): HttpResponse
    {
        $booking->load('customer');

        abort_unless($booking->customer->user_id === auth()->id(), 403);
        abort_if(
            $booking->status === BookingStatus::Pending,
            403,
            __('The rental agreement will be available once your booking is confirmed.'),
        );
        abort_if(
            $booking->status === BookingStatus::Cancelled,
            403,
            __('Rental agreement is not available for cancelled bookings.'),
        );
        abort_unless(
            in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Active, BookingStatus::Completed], true),
            403,
        );

        $pdf = $contracts->generate($booking);

        return $pdf->download($contracts->filename($booking));
    }

    private function ensureVehicleAvailable(Vehicle $vehicle): void
    {
        abort_unless($vehicle->status === VehicleStatus::Available, 404);
    }

    /**
     * @return array{start_date: string, end_date: string}
     */
    private function validateDateRangeInput(Request $request): array
    {
        /** @var array{start_date: string, end_date: string} $validated */
        $validated = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        return $validated;
    }

    /**
     * @return array{
     *     start_date: string,
     *     end_date: string,
     *     with_operator?: bool,
     *     extras?: list<int>
     * }
     */
    private function validateBookingInput(Request $request, Vehicle $vehicle): array
    {
        /** @var array{
         *     start_date: string,
         *     end_date: string,
         *     with_operator?: bool,
         *     extras?: list<int>
         * } $validated */
        $validated = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'with_operator' => ['sometimes', 'boolean'],
            'extras' => ['sometimes', 'array'],
            'extras.*' => ['integer', Rule::exists('extras', 'id')],
        ]);

        if (($validated['with_operator'] ?? false) && (! $vehicle->available_with_operator || $vehicle->operator_daily_rate === null)) {
            throw ValidationException::withMessages([
                'with_operator' => [__('This vehicle does not support operator rental.')],
            ]);
        }

        return $validated;
    }

    /**
     * @param  list<int>  $extraIds
     * @return Collection<int, Extra>
     */
    private function resolveExtras(array $extraIds): Collection
    {
        if ($extraIds === []) {
            return collect();
        }

        return Extra::query()->whereIn('id', $extraIds)->get();
    }
}
