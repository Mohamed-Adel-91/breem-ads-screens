<?php

namespace App\Http\Requests\Admin\Screens;

use App\Enums\ScreenStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScreenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = array_map(fn(ScreenStatus $status) => $status->value, ScreenStatus::cases());

        return [
            'place_id' => ['required', 'integer', 'exists:places,id'],
            'code' => ['required', 'string', 'max:255', 'unique:screens,code'],
            'device_uid' => ['nullable', 'string', 'max:255', 'unique:screens,device_uid'],
            'status' => ['required', Rule::in($statuses)],
            'last_heartbeat' => ['nullable', 'date'],
        ];
    }
}
