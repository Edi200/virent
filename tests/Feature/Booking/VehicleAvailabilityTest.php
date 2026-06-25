<?php

use App\Enums\BookingStatus;
use App\Enums\VehicleStatus;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\Category;
use App\Models\MaintenanceBlock;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;

function availabilityVehicle(int $bufferHours = 0): Vehicle
{
    $category = Category::factory()->create(['buffer_hours' => $bufferHours]);

    return Vehicle::factory()->for($category)->create();
}

it('reports available when there are no conflicts', function () {
    $vehicle = availabilityVehicle();

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-05'),
    ))->toBeTrue();
});

it('reports unavailable on exact booking overlap', function () {
    $vehicle = availabilityVehicle();

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-10',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-10'),
    ))->toBeFalse();
});

it('reports unavailable when candidate start falls inside existing booking', function () {
    $vehicle = availabilityVehicle();

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-10',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-05'),
        Carbon::parse('2026-06-15'),
    ))->toBeFalse();
});

it('reports unavailable when candidate end falls inside existing booking', function () {
    $vehicle = availabilityVehicle();

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-20',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-05'),
        Carbon::parse('2026-06-15'),
    ))->toBeFalse();
});

it('reports unavailable when candidate fully encompasses existing booking', function () {
    $vehicle = availabilityVehicle();

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-05',
        'end_date' => '2026-06-10',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-20'),
    ))->toBeFalse();
});

it('reports unavailable for back-to-back bookings within buffer hours', function () {
    $vehicle = availabilityVehicle(bufferHours: 24);

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-05',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-05'),
        Carbon::parse('2026-06-10'),
    ))->toBeFalse();
});

it('reports available for back-to-back bookings beyond buffer hours', function () {
    $vehicle = availabilityVehicle(bufferHours: 24);

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-05',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-06'),
        Carbon::parse('2026-06-10'),
    ))->toBeTrue();
});

it('reports unavailable when candidate ends shortly before a future booking within buffer hours', function () {
    $vehicle = availabilityVehicle(bufferHours: 24);

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-15',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-10'),
    ))->toBeFalse();
});

it('reports available when candidate ends with exactly the required buffer before a future booking', function () {
    $vehicle = availabilityVehicle(bufferHours: 24);

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-15',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-09'),
    ))->toBeTrue();
});

it('reports unavailable when overlapping a maintenance block', function () {
    $vehicle = availabilityVehicle();

    MaintenanceBlock::factory()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-05',
        'end_date' => '2026-06-10',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-07'),
        Carbon::parse('2026-06-12'),
    ))->toBeFalse();
});

it('does not block availability for completed or cancelled bookings', function () {
    $vehicle = availabilityVehicle();

    Booking::factory()->completed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-10',
    ]);

    Booking::factory()->cancelled()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-06-20',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-20'),
    ))->toBeTrue();
});

it('reports unavailable when vehicle status is not available', function () {
    $vehicle = availabilityVehicle();
    $vehicle->update(['status' => VehicleStatus::Maintenance]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-05'),
    ))->toBeFalse();
});

it('blocks pending and active bookings but not completed ones', function () {
    $vehicle = availabilityVehicle();

    Booking::factory()->pending()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-07-01',
        'end_date' => '2026-07-10',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-07-05'),
        Carbon::parse('2026-07-15'),
    ))->toBeFalse();

    $vehicle->bookings()->update(['status' => BookingStatus::Completed]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-07-05'),
        Carbon::parse('2026-07-15'),
    ))->toBeTrue();
});

it('returns blocked ranges from blocking bookings and maintenance with buffer and matches availability checks', function () {
    $vehicle = availabilityVehicle(bufferHours: 24);

    Booking::factory()->pending()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-03',
    ]);

    Booking::factory()->confirmed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-08-06',
        'end_date' => '2026-08-08',
    ]);

    Booking::factory()->active()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-08-11',
        'end_date' => '2026-08-13',
    ]);

    Booking::factory()->completed()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-08-16',
        'end_date' => '2026-08-18',
    ]);

    Booking::factory()->cancelled()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-08-20',
        'end_date' => '2026-08-22',
    ]);

    MaintenanceBlock::factory()->create([
        'vehicle_id' => $vehicle->id,
        'start_date' => '2026-08-24',
        'end_date' => '2026-08-25',
    ]);

    $ranges = collect($vehicle->blockedDateRanges(
        Carbon::parse('2026-07-25'),
        Carbon::parse('2026-08-30'),
    ));

    expect($ranges)->toHaveCount(4);

    $serializedRanges = $ranges
        ->map(fn (array $range): array => [
            'start' => $range['start']->toDateString(),
            'end' => $range['end']->toDateString(),
        ])
        ->values()
        ->all();

    expect($serializedRanges)->toContain(
        ['start' => '2026-08-01', 'end' => '2026-08-04'],
        ['start' => '2026-08-06', 'end' => '2026-08-09'],
        ['start' => '2026-08-11', 'end' => '2026-08-14'],
        ['start' => '2026-08-24', 'end' => '2026-08-26'],
    )
        ->not->toContain(
            ['start' => '2026-08-16', 'end' => '2026-08-19'],
            ['start' => '2026-08-20', 'end' => '2026-08-23'],
        );

    $isBlockedByRanges = function (Carbon $start, Carbon $end) use ($ranges): bool {
        return $ranges->contains(
            fn (array $range): bool => ! ($end <= $range['start']) && ! ($range['end'] <= $start),
        );
    };

    $samples = [
        ['start' => '2026-08-02', 'end' => '2026-08-03'],
        ['start' => '2026-08-08', 'end' => '2026-08-09'],
        ['start' => '2026-08-24', 'end' => '2026-08-25'],
        ['start' => '2026-07-28', 'end' => '2026-07-29'],
        ['start' => '2026-08-14', 'end' => '2026-08-15'],
        ['start' => '2026-08-26', 'end' => '2026-08-27'],
    ];

    foreach ($samples as $sample) {
        $sampleStart = Carbon::parse($sample['start'])->startOfDay();
        $sampleEnd = Carbon::parse($sample['end'])->startOfDay();

        expect($vehicle->isAvailableBetween($sampleStart, $sampleEnd))
            ->toBe(! $isBlockedByRanges($sampleStart, $sampleEnd));
    }
});

it('includes active booking holds from other users in blocked date ranges', function () {
    $vehicle = availabilityVehicle();
    $otherUser = User::factory()->create();

    BookingHold::factory()->active()->create([
        'vehicle_id' => $vehicle->id,
        'user_id' => $otherUser->id,
        'start_date' => '2026-06-05',
        'end_date' => '2026-06-10',
    ]);

    $ranges = $vehicle->blockedDateRanges(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-15'),
    );

    expect($ranges)->toHaveCount(1)
        ->and($ranges[0]['start']->toDateString())->toBe('2026-06-05')
        ->and($ranges[0]['end']->toDateString())->toBe('2026-06-10');
});

it('excludes the requesting users own active hold when excludeUserId is passed', function () {
    $vehicle = availabilityVehicle();
    $user = User::factory()->create();

    BookingHold::factory()->active()->create([
        'vehicle_id' => $vehicle->id,
        'user_id' => $user->id,
        'start_date' => '2026-06-05',
        'end_date' => '2026-06-10',
    ]);

    $rangesWithoutExclusion = $vehicle->blockedDateRanges(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-15'),
    );

    $rangesWithExclusion = $vehicle->blockedDateRanges(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-15'),
        $user->id,
    );

    expect($rangesWithoutExclusion)->toHaveCount(1)
        ->and($rangesWithExclusion)->toBeEmpty();
});

it('excludes expired booking holds from blocked date ranges', function () {
    $vehicle = availabilityVehicle();
    $otherUser = User::factory()->create();

    BookingHold::factory()->expired()->create([
        'vehicle_id' => $vehicle->id,
        'user_id' => $otherUser->id,
        'start_date' => '2026-06-05',
        'end_date' => '2026-06-10',
    ]);

    $ranges = $vehicle->blockedDateRanges(
        Carbon::parse('2026-06-01'),
        Carbon::parse('2026-06-15'),
    );

    expect($ranges)->toBeEmpty();
});

it('hard-blocks availability when another user has an active hold on overlapping dates', function () {
    $vehicle = availabilityVehicle();
    $otherUser = User::factory()->create();
    $requestingUser = User::factory()->create();

    BookingHold::factory()->active()->create([
        'vehicle_id' => $vehicle->id,
        'user_id' => $otherUser->id,
        'start_date' => '2026-06-05',
        'end_date' => '2026-06-10',
    ]);

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-05'),
        Carbon::parse('2026-06-10'),
    ))->toBeFalse();

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-05'),
        Carbon::parse('2026-06-10'),
        $requestingUser->id,
    ))->toBeFalse();

    expect($vehicle->isAvailableBetween(
        Carbon::parse('2026-06-05'),
        Carbon::parse('2026-06-10'),
        $otherUser->id,
    ))->toBeTrue();
});
