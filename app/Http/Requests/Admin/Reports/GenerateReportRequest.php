<?php

namespace App\Http\Requests\Admin\Reports;

use App\Support\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateReportRequest extends FormRequest
{
    /**
     * Generatable report types.
     *
     * App\Support\ReportType is the registry; a PHP constant cannot call it, so this
     * mirrors it and `ReportTypeRegistryTest` asserts the two can never drift apart.
     * The constant itself stays because views and tests already reference it.
     *
     * Do not add a value here alone — add it to ReportType.
     *
     * @var array<int, string>
     */
    public const TYPES = [ReportType::PLAYBACK, ReportType::SCREEN_UPTIME];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(ReportType::supported())],
            // Dates are UTC calendar days; `to_date` is inclusive of the whole day.
            // See ReportGenerationService::resolvePeriod().
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'screen_id' => ['nullable', 'integer', 'exists:screens,id'],
            'ad_id' => ['nullable', 'integer', 'exists:ads,id'],
        ];
    }
}
