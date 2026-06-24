<?php

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Models\Category;
use App\Models\Vehicle;
use App\Services\FleetFilterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FleetController extends Controller
{
    public function index(Request $request, FleetFilterService $fleetFilter): Response
    {
        $category = $this->resolveCategory($request->query('category'));

        $vehicles = Vehicle::query()
            ->fleetFilter($request)
            ->with(['category', 'media'])
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Vehicle $vehicle) => $this->vehicleListProps($vehicle));

        $categories = Category::query()
            ->orderBy('sort_order')
            ->get(['name', 'slug', 'sort_order']);

        return Inertia::render('Home', [
            'categories' => $categories,
            'vehicles' => $vehicles,
            'filters' => $fleetFilter->filtersFromRequest($request),
            'filterAttributes' => $category !== null
                ? $fleetFilter->filterAttributes($category, $request)
                : [],
            'priceBounds' => $fleetFilter->priceBounds($request),
        ]);
    }

    public function show(Vehicle $vehicle): Response
    {
        abort_unless($vehicle->status === VehicleStatus::Available, 404);

        $vehicle->load(['category.categoryAttributes', 'media']);

        return Inertia::render('Fleet/Show', [
            'vehicle' => $this->vehicleShowProps($vehicle),
        ]);
    }

    public function book(Vehicle $vehicle): RedirectResponse
    {
        abort_unless($vehicle->status === VehicleStatus::Available, 404);

        if (auth()->check()) {
            return redirect()->route('fleet.show', $vehicle);
        }

        return redirect()
            ->setIntendedUrl(route('fleet.show', $vehicle))
            ->route('login');
    }

    private function resolveCategory(?string $slug): ?Category
    {
        if (blank($slug)) {
            return null;
        }

        return Category::query()->where('slug', $slug)->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function vehicleListProps(Vehicle $vehicle): array
    {
        $thumbnailUrl = $vehicle->getFirstMediaUrl('fleet-images');

        return [
            'slug' => $vehicle->slug,
            'name' => $vehicle->name,
            'year' => $vehicle->year,
            'daily_rate' => $vehicle->daily_rate,
            'category' => [
                'name' => $vehicle->category->name,
                'slug' => $vehicle->category->slug,
            ],
            'thumbnail_url' => $thumbnailUrl !== '' ? $thumbnailUrl : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vehicleShowProps(Vehicle $vehicle): array
    {
        return [
            'slug' => $vehicle->slug,
            'name' => $vehicle->name,
            'year' => $vehicle->year,
            'description' => $vehicle->description,
            'daily_rate' => $vehicle->daily_rate,
            'weekly_rate' => $vehicle->weekly_rate,
            'monthly_rate' => $vehicle->monthly_rate,
            'deposit_amount' => $vehicle->deposit_amount,
            'available_with_operator' => $vehicle->available_with_operator,
            'operator_daily_rate' => $vehicle->operator_daily_rate,
            'requires_license_type' => $vehicle->requires_license_type,
            'category' => [
                'name' => $vehicle->category->name,
                'slug' => $vehicle->category->slug,
            ],
            'specs' => $vehicle->enrichedSpecs(),
            'images' => $this->vehicleImageProps($vehicle),
        ];
    }

    /**
     * @return list<array{src: string, width: int, height: int, alt: string}>
     */
    private function vehicleImageProps(Vehicle $vehicle): array
    {
        return array_values($vehicle->getMedia('fleet-images')
            ->map(fn (Media $media) => [
                'src' => $media->getUrl(),
                'width' => (int) ($media->getCustomProperty('width') ?? 1200),
                'height' => (int) ($media->getCustomProperty('height') ?? 800),
                'alt' => $vehicle->name,
            ])
            ->values()
            ->all());
    }
}
