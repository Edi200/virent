<?php

namespace App\Services;

use App\Enums\ExtraPriceType;
use App\Models\Extra;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PricingService
{
    /**
     * Calculate rental pricing for a vehicle and date range.
     *
     * Day convention: start_date is the first rented day; end_date is the first
     * day NOT rented (return day). Example: Jun 1 → Jun 4 = 3 billed days.
     *
     * @param  Collection<int, Extra>  $extras
     * @return array{
     *     base_price: string,
     *     operator_price: string,
     *     extras_price: string,
     *     total_price: string,
     *     breakdown: list<array{label: string, amount: string}>
     * }
     */
    public function calculate(
        Vehicle $vehicle,
        Carbon $start,
        Carbon $end,
        bool $withOperator,
        Collection $extras,
    ): array {
        $days = $this->rentalDays($start, $end);
        $breakdown = [];

        $basePrice = $this->calculateBasePrice($vehicle, $days, $breakdown);
        $operatorPrice = $this->calculateOperatorPrice($vehicle, $days, $withOperator, $breakdown);
        $extrasPrice = $this->calculateExtrasPrice($extras, $days, $breakdown);

        $totalPrice = $this->roundMoney(
            (float) $basePrice + (float) $operatorPrice + (float) $extrasPrice
        );

        return [
            'base_price' => $basePrice,
            'operator_price' => $operatorPrice,
            'extras_price' => $extrasPrice,
            'total_price' => $totalPrice,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Count billed rental days (start inclusive, end exclusive).
     * Same-calendar-day rental counts as 1 day.
     */
    private function rentalDays(Carbon $start, Carbon $end): int
    {
        // startOfDay() boundaries guarantee whole days; cast satisfies PHPStan (diffInDays is float|int).
        $days = (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay());

        return max(1, $days);
    }

    /**
     * @param  list<array{label: string, amount: string}>  $breakdown
     */
    private function calculateBasePrice(Vehicle $vehicle, int $days, array &$breakdown): string
    {
        $dailyRate = (float) $vehicle->daily_rate;

        if ($vehicle->monthly_rate !== null && $days >= 28) {
            return $this->calculateTieredPrice(
                $days,
                28,
                (float) $vehicle->monthly_rate,
                $dailyRate,
                'month',
                '/mo',
                $breakdown,
            );
        }

        if ($vehicle->weekly_rate !== null && $days >= 7) {
            return $this->calculateTieredPrice(
                $days,
                7,
                (float) $vehicle->weekly_rate,
                $dailyRate,
                'week',
                '/wk',
                $breakdown,
            );
        }

        $amount = $days * $dailyRate;
        $breakdown[] = [
            'label' => $this->quantityLabel($days, 'day', '/day', $dailyRate),
            'amount' => $this->roundMoney($amount),
        ];

        return $this->roundMoney($amount);
    }

    /**
     * @param  list<array{label: string, amount: string}>  $breakdown
     */
    private function calculateTieredPrice(
        int $days,
        int $tierDays,
        float $tierRate,
        float $dailyRate,
        string $tierUnit,
        string $tierSuffix,
        array &$breakdown,
    ): string {
        $fullTiers = intdiv($days, $tierDays);
        $remainder = $days % $tierDays;
        $amount = $fullTiers * $tierRate + $remainder * $dailyRate;

        if ($fullTiers > 0) {
            $breakdown[] = [
                'label' => $this->quantityLabel($fullTiers, $tierUnit, $tierSuffix, $tierRate),
                'amount' => $this->roundMoney($fullTiers * $tierRate),
            ];
        }

        if ($remainder > 0) {
            $breakdown[] = [
                'label' => $this->quantityLabel($remainder, 'day', '/day', $dailyRate),
                'amount' => $this->roundMoney($remainder * $dailyRate),
            ];
        }

        return $this->roundMoney($amount);
    }

    /**
     * @param  list<array{label: string, amount: string}>  $breakdown
     */
    private function calculateOperatorPrice(
        Vehicle $vehicle,
        int $days,
        bool $withOperator,
        array &$breakdown,
    ): string {
        if (! $withOperator || ! $vehicle->available_with_operator || $vehicle->operator_daily_rate === null) {
            return $this->roundMoney(0);
        }

        $rate = (float) $vehicle->operator_daily_rate;
        $amount = $rate * $days;

        $breakdown[] = [
            'label' => "Operator ({$days} days @ €".number_format($rate, 2, '.', '').'/day)',
            'amount' => $this->roundMoney($amount),
        ];

        return $this->roundMoney($amount);
    }

    /**
     * @param  Collection<int, Extra>  $extras
     * @param  list<array{label: string, amount: string}>  $breakdown
     */
    private function calculateExtrasPrice(Collection $extras, int $days, array &$breakdown): string
    {
        $total = 0.0;

        foreach ($extras as $extra) {
            $price = (float) $extra->price;

            $amount = match ($extra->price_type) {
                ExtraPriceType::Flat => $price,
                ExtraPriceType::PerDay => $price * $days,
            };

            $label = match ($extra->price_type) {
                ExtraPriceType::Flat => "{$extra->name} (flat)",
                ExtraPriceType::PerDay => "{$extra->name} (per day × {$days})",
            };

            $rounded = $this->roundMoney($amount);
            $breakdown[] = [
                'label' => $label,
                'amount' => $rounded,
            ];

            $total += (float) $rounded;
        }

        return $this->roundMoney($total);
    }

    private function quantityLabel(int $quantity, string $unit, string $suffix, float $rate): string
    {
        $pluralUnit = $quantity === 1 ? $unit : $unit.'s';

        return "{$quantity} {$pluralUnit} @ €".number_format($rate, 2, '.', '').$suffix;
    }

    private function roundMoney(float|string $amount): string
    {
        return number_format(round((float) $amount, 2, PHP_ROUND_HALF_UP), 2, '.', '');
    }
}
