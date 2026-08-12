<?php

namespace App\Http\Requests\Admin\Ads;

use App\Enums\AdStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A lifecycle action on an advertisement.
 *
 * The input is an ACTION NAME, never a target status. That distinction is the whole
 * point: `status=active` in a request body is a caller asserting an outcome, while
 * `action=publish` is a caller requesting an edge that AdStatus either declares from
 * the ad's current status or refuses. The target is derived server-side, so an
 * unlisted pair cannot be reached however the request is shaped.
 */
class TransitionAdStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route already carries permission:ads.approve; this is the second gate
        // for anything that reaches the request directly.
        return (bool) $this->user('admin')?->can('ads.approve');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(AdStatus::actions())],
            // Free-text context for the activity log. There is no rejection-reason
            // column, and none is invented here.
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function action(): string
    {
        return (string) $this->validated()['action'];
    }

    public function reason(): ?string
    {
        $reason = trim((string) ($this->validated()['reason'] ?? ''));

        return $reason === '' ? null : $reason;
    }
}
