<?php

namespace App\Http\Requests\Api\Screens;

use App\Http\Requests\Api\ApiRequest;

class PlaylistRequest extends ApiRequest
{
    /**
     * Determine whether the request should validate a timestamp.
     */
    protected function expectsTimestamp(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
