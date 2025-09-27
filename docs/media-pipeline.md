# Media Pipeline Guidance

This file supplements the README media storage section with operational processes.

## Upload workflow

1. Creatives upload media through the Laravel admin, which stores assets on the configured filesystem disk.
2. If `ADS_TRY_FFPROBE=true`, the system invokes `ffprobe` (via `FFPROBE_BIN`) to detect duration and resolution metadata.
3. Background jobs replicate or transcode assets when required by specific device profiles.

## Purge workflow

- Scheduled jobs prune expired assets referenced by inactive playlists.
- Operators can trigger manual cleanup using `php artisan ads:media:purge` (ensure queues are paused during mass deletes).

## Storage tips

- Version assets when updating creatives to prevent caching issues on Android devices.
- For S3-compatible storage, enable object-level logging to audit device access.
- Maintain lifecycle policies to transition archival creatives to cold storage while keeping active campaigns in the primary bucket.
