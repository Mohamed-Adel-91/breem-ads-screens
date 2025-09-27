# Android Device API Reference

This document outlines the contract between Android screen players and the Laravel backend.

## Authentication

- Players sign every request with an `X-Screens-Signature` header computed using the shared `SCREENS_HMAC_SECRET`.
- Timestamps and nonce values must fall within the configured `SCREENS_SIGNATURE_LEEWAY`.

## Core Endpoints

| Endpoint | Method | Description |
| --- | --- | --- |
| `/api/screens/auth` | POST | Exchange provisioning code for credentials and configuration bootstrap. |
| `/api/screens/playlists` | GET | Fetch the active playlist manifest, respecting `SCREENS_PLAYLIST_TTL`. |
| `/api/screens/config` | GET | Retrieve device-specific settings, respecting `SCREENS_CONFIG_TTL`. |
| `/api/screens/heartbeat` | POST | Report device vitals and current playback status every `SCREENS_HEARTBEAT_INTERVAL` seconds. |

## Error Handling

- `401 Unauthorized` indicates signature mismatches or expired tokens.
- `429 Too Many Requests` signals aggressive polling; devices should back off for 60 seconds.

For payload schemas and examples, consult the Postman collection stored alongside this repository.
