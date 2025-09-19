<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class ApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        if (! $validator instanceof Validator) {
            return;
        }

        $validator->after(function (Validator $validator): void {
            if ($validator->fails()) {
                return;
            }

            if ($this->expectsTimestamp() && ! $this->hasValidTimestamp()) {
                $validator->errors()->add('timestamp', __('The timestamp is outside the allowed window.'));
            }

            if ($this->expectsSignature() && ! $this->hasValidSignature()) {
                $validator->errors()->add('signature', __('Invalid request signature.'));
            }
        });
    }

    /**
     * Determine whether the request should be signed.
     */
    protected function expectsSignature(): bool
    {
        return true;
    }

    /**
     * Determine whether the request should validate a timestamp.
     */
    protected function expectsTimestamp(): bool
    {
        return true;
    }

    /**
     * Retrieve the canonical payload that should be signed for the request.
     */
    protected function signaturePayload(): string
    {
        if (in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $this->fullUrl();
        }

        $content = $this->getContent();

        return $content === '' ? $this->fullUrl() : $content;
    }

    /**
     * Validate the presence and accuracy of the timestamp parameter.
     */
    protected function hasValidTimestamp(): bool
    {
        $timestamp = $this->input('timestamp');

        if (! is_numeric($timestamp)) {
            return false;
        }

        $timestamp = (int) $timestamp;
        $allowedSkew = (int) config('services.screens.signature_leeway', 300);
        $delta = abs(now()->timestamp - $timestamp);

        return $delta <= $allowedSkew;
    }

    /**
     * Validate the HMAC signature header for the request.
     */
    protected function hasValidSignature(): bool
    {
        $secret = (string) config('services.screens.hmac_secret');

        if ($secret === '') {
            return true;
        }

        $signature = (string) $this->headers->get('X-Screen-Signature', '');

        if ($signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $this->signaturePayload(), $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Retrieve the If-None-Match header stripped from quotes.
     */
    public function ifNoneMatch(): ?string
    {
        $etag = $this->headers->get('If-None-Match');

        if (! $etag) {
            return null;
        }

        return trim($etag, '"');
    }
}
