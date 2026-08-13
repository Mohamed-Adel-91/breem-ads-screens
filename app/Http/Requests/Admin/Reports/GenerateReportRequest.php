<?php

namespace App\Http\Requests\Admin\Reports;

use App\Support\ReportPeriod;
use App\Support\ReportType;
use Illuminate\Contracts\Validation\Validator;
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

    /**
     * Enforce the report-period ceiling.
     *
     * Here rather than in a `rules()` entry because the limit is a property of the
     * *pair* of dates, and because an omitted `to_date` still describes a period —
     * `from_date` to now — which no per-field rule can see. Server-side and
     * authoritative: the form also states the limit, but nothing relies on it doing so.
     *
     * The error is attached to `from_date` because that is the field that opens the
     * window, and it is the one an operator has to change.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Only meaningful once the dates themselves are valid.
            if ($validator->errors()->hasAny(['from_date', 'to_date'])) {
                return;
            }

            if (! ReportPeriod::exceedsMaximum($this->all())) {
                return;
            }

            $validator->errors()->add('from_date', __('validation.report_period_too_long', [
                'max' => ReportPeriod::maxDays(),
            ]));
        });
    }
}
