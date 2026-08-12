<?php

namespace App\Services\Screen;

use RuntimeException;

/**
 * Raised when a device cannot claim a screen. `reason` is one of the
 * DevicePairingService::REASON_* constants and maps to an API error code.
 */
class PairingException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('Pairing failed: '.$reason);
    }
}
