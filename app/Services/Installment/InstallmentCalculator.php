<?php

namespace App\Services\Installment;

class InstallmentCalculator
{
    /**
     * @return array{principal: float, apr: float, monthly: float|null, total: float|null}
     */
    public function summarize(float $carPrice, float $downPayment, int $months, float $aprYear): array
    {
        $carPrice   = max(0.0, $carPrice);
        $downPayment = max(0.0, min($downPayment, $carPrice));
        $principal   = max(0.0, $carPrice - $downPayment);

        if ($months <= 0) {
            return [
                'principal' => $principal,
                'apr'       => $aprYear,
                'monthly'   => null,
                'total'     => null,
            ];
        }

        $monthlyRate = ($aprYear / 100.0) / 12.0;

        if ($monthlyRate <= 0.0) {
            $monthly = $principal / $months;
            return [
                'principal' => $principal,
                'apr'       => $aprYear,
                'monthly'   => $monthly,
                'total'     => $monthly * $months,
            ];
        }

        $monthly = $principal * ($monthlyRate / (1 - pow(1 + $monthlyRate, -$months)));

        return [
            'principal' => $principal,
            'apr'       => $aprYear,
            'monthly'   => $monthly,
            'total'     => $monthly * $months,
        ];
    }
}
