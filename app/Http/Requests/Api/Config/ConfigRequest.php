<?php

namespace App\Http\Requests\Api\Config;

use App\Http\Requests\Api\ApiRequest;

/**
 * The screen comes from the authenticated credential; no identifiers are read
 * from the query string.
 */
class ConfigRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
