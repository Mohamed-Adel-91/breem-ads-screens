# QA Checklist

This checklist captures the required manual validations before promoting a build. Complete each section in order and record the results in the release log.

## Local Upload Validations
1. Start the local development server and ensure the uploads feature flag is enabled.
2. Upload a sample video via the local UI and confirm the request body matches the expected schema in the network inspector.
3. Verify the uploaded asset appears in the media library with the correct title, duration, and thumbnail.
4. Confirm the asset is written to the local storage disk (`storage/app/public`) and that the file checksum matches the original.
5. Remove the asset through the UI and ensure both the UI listing and underlying file are deleted.

## `ffprobe` Behavior
1. Run the media ingestion job and capture the `ffprobe` command logged for the uploaded file.
2. Execute the logged `ffprobe` command manually and confirm the parsed metadata matches the database record (codec, bitrate, resolution, duration).
3. Validate that malformed or audio-only uploads surface the expected validation error and the job is marked as failed without retries.
4. Confirm the fallback thumbnail generation runs when `ffprobe` is unable to extract video frames.

## Playlist ETag Flow
1. Seed a playlist with at least three items and publish it to a test player.
2. Capture the initial playlist GET request and record the returned `ETag` header value.
3. Modify the playlist order and republish; ensure the next GET sends the `If-None-Match` header and receives a 200 with a new `ETag`.
4. Refresh the player without changing the playlist; verify the server responds with 304 Not Modified and no payload when the `ETag` matches.
5. Check that stale cache entries are invalidated after five minutes by observing a new `ETag` after the TTL elapses.

## Heartbeat & Offline Handling
1. Bring a test player online and confirm heartbeat pings arrive at the API on the configured interval.
2. Disconnect the player network and verify the heartbeat stops within two intervals and the device status flips to **Offline** in the dashboard.
3. Reconnect the player and confirm the status returns to **Online** after the first successful heartbeat.
4. Trigger a long-running playlist playback and ensure offline alerts fire in Slack or email only after the grace period expires.

## S3 Switch Verification
1. Toggle the storage configuration to use the S3 disk and redeploy the worker services.
2. Upload a new asset and confirm the object is created in the configured bucket with public-read ACL disabled.
3. Validate signed URL generation by streaming the asset in the player; ensure unsigned requests fail with 403.
4. Disable the S3 configuration and confirm the system falls back to local storage without residual S3 references in the config cache.

## Slack Optionality
1. Open the notification settings page and toggle Slack alerts off.
2. Generate a system alert (e.g., upload failure) and verify only email notifications are sent.
3. Re-enable Slack alerts and ensure both Slack and email notifications fire for the next alert.
4. Confirm the configuration persists after a page refresh and across user sessions.

## Troubleshooting: Top Five Common Issues
1. **Uploads stuck in "Processing"** – Check the queue worker logs for failed jobs, re-run `php artisan queue:restart`, and ensure the storage disk is writable.
2. **`ffprobe` command not found** – Verify the binary is installed and available in `$PATH`; reinstall the FFmpeg suite if missing.
3. **Playlist not updating on players** – Clear the CDN cache, confirm the latest `ETag` is returned by the API, and verify the player is sending `If-None-Match` headers.
4. **Players staying Offline** – Confirm heartbeats reach the API, inspect network connectivity, and review server time drift that might delay offline detection.
5. **S3 playback failures** – Check IAM permissions for `s3:GetObject`, validate bucket CORS rules, and ensure signed URLs have not expired.
