<?php

namespace App\Http\Requests\Admin\Screens;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScreenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statuses = array_map(fn(ScreenStatus $status) => $status->value, ScreenStatus::cases());
        $screen = $this->route('screen');
        $screenId = $screen instanceof Screen ? $screen->id : $screen;

        return [
            'place_id' => ['required', 'integer', 'exists:places,id'],
            'code' => ['required', 'string', 'max:255', Rule::unique('screens', 'code')->ignore($screenId)],
            'device_uid' => ['nullable', 'string', 'max:255', Rule::unique('screens', 'device_uid')->ignore($screenId)],
            'status' => ['required', Rule::in($statuses)],
            'last_heartbeat' => ['nullable', 'date'],
        ];
    }
}
