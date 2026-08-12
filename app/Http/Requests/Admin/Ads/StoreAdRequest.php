<?php

namespace App\Http\Requests\Admin\Ads;

use App\Support\CreativeMedia;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Creating an advertisement.
 *
 * TWO FIELDS ARE DELIBERATELY ABSENT, and adding them back would reopen the hole
 * Phase 13 closed:
 *
 *   - `status` — a free select here let anyone with `ads.create` publish straight
 *     to `active`, bypassing review entirely. A new ad always starts `pending`;
 *     status only moves along the edges declared in AdStatus, through the
 *     lifecycle transition action, and only with `ads.approve`.
 *   - `approved_by` — an approval field on a content form. The approver is recorded
 *     by the approval action, from the authenticated admin.
 *
 * `created_by` stays: it is a NOT NULL foreign key to `users` and represents the
 * content owner, which is a separate question from which admin performed the
 * write (that goes to `created_by_admin_id`).
 */
class StoreAdRequest extends FormRequest
{
    use ValidatesCreativeUpload;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'array'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'title.ar' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.ar' => ['nullable', 'string'],
            'creative' => [
                'required',
                'file',
                // Content-based detection, from the one authoritative list.
                'mimetypes:'.CreativeMedia::allowedMimeTypeList(),
                // Blanket ceiling; the per-category limit is applied in withValidator().
                'max:'.CreativeMedia::absoluteMaxKilobytes(),
            ],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'created_by' => ['required', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'screens' => ['nullable', 'array'],
            'screens.*' => ['integer', 'exists:screens,id'],
            'play_order' => ['nullable', 'array'],
            'play_order.*' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateCreativeSize($validator);
    }
}
