<?php

namespace App\Http\Resources\Api\Screens;

use App\Http\Resources\Api\ScreenResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The pairing response — the ONLY place credentials are ever transmitted.
 *
 * The bearer token and signing secret exist in plaintext here and nowhere else:
 * the token is stored as a SHA-256 hash and the secret encrypted at rest. A
 * device that loses them must be re-paired by an administrator.
 */
class HandshakeResource extends JsonResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'screen' => ScreenResource::make($this->resource['screen']),
            'config' => $this->resource['config'] ?? [],
            'auth' => [
                'token_type' => 'Bearer',
                'access_token' => $this->resource['token'],
                'hmac_secret' => $this->resource['hmac_secret'],
                'signature_algorithm' => 'HMAC-SHA256',
                'signature_headers' => [
                    'timestamp' => 'X-Screen-Timestamp',
                    'nonce' => 'X-Screen-Nonce',
                    'signature' => 'X-Screen-Signature',
                ],
            ],
            'meta' => $this->resource['meta'] ?? [],
        ];
    }
}
