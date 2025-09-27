# Ads API v1 Reference

This document describes the public REST API used by Breem screens and partner integrations. It covers the base URL, authentication scheme, caching headers, endpoint contracts, and guidance for building resilient clients.

## Base URL

All endpoints are served over HTTPS from the following base URL:

```
https://api.breemads.com/api/v1
```

Combine this base with the paths listed below to construct full request URLs. A sandbox environment is available at `https://sandbox-api.breemads.com/api/v1` and mirrors the same contract for testing purposes.

## Authentication

Requests must include the following headers:

| Header | Description |
| --- | --- |
| `Authorization: Bearer <token>` | OAuth 2.0 access token issued by the Breem Auth service. Tokens expire after 60 minutes. |
| `X-Client-Id` | Static identifier assigned to each integration partner. |
| `X-Request-Id` (optional) | Client-generated UUID used for tracing. Echoed back in responses. |

Clients should refresh tokens via the OAuth client credentials flow before expiry. Requests without valid credentials receive a `401 Unauthorized` response.

## Caching with ETag

Most read endpoints return an `ETag` header computed from the resource payload. Clients should:

1. Cache the response body alongside the `ETag` value.
2. Send the `If-None-Match` header with the cached `ETag` on subsequent requests.
3. Treat a `304 Not Modified` response as confirmation to reuse the cached content.

Responses also include `Cache-Control` directives indicating how long the content may be reused. Do not cache error responses.

## Endpoints

### List Campaigns

- **Method:** `GET`
- **Path:** `/campaigns`
- **Query Parameters:**
  - `status` *(optional)* — Filter by `active`, `paused`, or `archived`.
  - `updated_after` *(optional)* — ISO-8601 timestamp to fetch only recently changed campaigns.

**Sample Request**

```
GET /api/v1/campaigns?status=active HTTP/1.1
Host: api.breemads.com
Authorization: Bearer eyJhbGciOi...
X-Client-Id: screens-prod
If-None-Match: "6c3aa0e1"
```

**Sample Response**

```json
{
  "data": [
    {
      "id": "cmp_123",
      "name": "Summer Splash",
      "status": "active",
      "budget": 25000,
      "currency": "USD",
      "updated_at": "2024-05-10T14:21:33Z"
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 50,
    "has_more": false
  }
}
```

Possible status codes: `200 OK`, `304 Not Modified`, `400 Bad Request`, `401 Unauthorized`.

### Retrieve Campaign Details

- **Method:** `GET`
- **Path:** `/campaigns/{campaignId}`
- **Path Parameters:**
  - `campaignId` — Unique identifier returned by the listing endpoint.

**Sample Request**

```
GET /api/v1/campaigns/cmp_123 HTTP/1.1
Host: api.breemads.com
Authorization: Bearer eyJhbGciOi...
X-Client-Id: screens-prod
```

**Sample Response**

```json
{
  "id": "cmp_123",
  "name": "Summer Splash",
  "status": "active",
  "budget": 25000,
  "currency": "USD",
  "schedule": {
    "start": "2024-05-01T00:00:00Z",
    "end": "2024-08-31T23:59:59Z"
  },
  "targets": {
    "regions": ["US-CA", "US-NV"],
    "devices": ["screen_101", "screen_202"]
  }
}
```

Possible status codes: `200 OK`, `304 Not Modified`, `401 Unauthorized`, `404 Not Found`.

### Fetch Screen Playlist Manifest

- **Method:** `GET`
- **Path:** `/screens/{screenId}/playlist`
- **Headers:**
  - `X-Screens-Signature` — HMAC signature for device-level verification.

**Sample Request**

```
GET /api/v1/screens/screen_101/playlist HTTP/1.1
Host: api.breemads.com
Authorization: Bearer eyJhbGciOi...
X-Client-Id: screens-prod
X-Screens-Signature: sha256=f4c485...
If-None-Match: "b9f7d5ab"
```

**Sample Response**

```json
{
  "screen_id": "screen_101",
  "playlist": [
    {
      "slot": 1,
      "campaign_id": "cmp_123",
      "creative_url": "https://cdn.breemads.com/creatives/cmp_123/spot.mp4",
      "duration_seconds": 30
    },
    {
      "slot": 2,
      "campaign_id": "cmp_456",
      "creative_url": "https://cdn.breemads.com/creatives/cmp_456/banner.jpg",
      "duration_seconds": 15
    }
  ],
  "updated_at": "2024-05-11T07:18:10Z"
}
```

Possible status codes: `200 OK`, `304 Not Modified`, `401 Unauthorized`, `403 Forbidden` (signature mismatch), `404 Not Found`.

### Report Playback Event

- **Method:** `POST`
- **Path:** `/analytics/events`
- **Body:** JSON payload describing playback metrics.

**Sample Request**

```
POST /api/v1/analytics/events HTTP/1.1
Host: api.breemads.com
Authorization: Bearer eyJhbGciOi...
X-Client-Id: screens-prod
Content-Type: application/json

{
  "screen_id": "screen_101",
  "campaign_id": "cmp_123",
  "event_type": "impression",
  "occurred_at": "2024-05-11T07:18:45Z",
  "metadata": {
    "slot": 1,
    "duration_seconds": 30,
    "temperature_celsius": 23.1
  }
}
```

**Sample Response**

```json
{
  "status": "accepted",
  "processed_at": "2024-05-11T07:18:45Z"
}
```

Possible status codes: `202 Accepted`, `400 Bad Request`, `401 Unauthorized`, `413 Payload Too Large`.

## Error Handling

| Status | Meaning | Client Action |
| --- | --- | --- |
| `400 Bad Request` | Validation failed or malformed JSON. | Inspect the `errors` array in the response and fix the payload. |
| `401 Unauthorized` | Missing or expired credentials. | Refresh the OAuth token and retry. |
| `403 Forbidden` | Authenticated but lacking required permissions/signature. | Verify client scopes and signing key. |
| `404 Not Found` | Resource does not exist. | Check identifiers before retrying. |
| `409 Conflict` | Concurrent modification detected. | Re-fetch the resource and retry with latest version. |
| `429 Too Many Requests` | Rate limit exceeded. | Honor the `Retry-After` header before retrying. |
| `500 Internal Server Error` | Unexpected server error. | Retry with exponential backoff; report if persistent. |

Error responses use the following JSON schema:

```json
{
  "error": {
    "code": "string",
    "message": "Human readable summary",
    "errors": [
      {
        "field": "field_name",
        "detail": "Validation message"
      }
    ],
    "request_id": "7dd4f9f9-4f6c-4ef4-83be-8d9ccbbf0539"
  }
}
```

## Client Implementation Guidelines

- **Use resilient retry logic.** Implement exponential backoff for `5xx` and `429` responses. Avoid retrying non-idempotent requests without deduplication.
- **Paginate responsibly.** Respect `meta.has_more` and `meta.page` values when iterating through large datasets.
- **Monitor rate limits.** The API communicates remaining quota via `X-RateLimit-Remaining` headers; throttle requests before hitting zero.
- **Secure secrets.** Store OAuth credentials and signing keys in a secure vault, never in source control or client binaries.
- **Validate TLS.** Pin Breem's certificate authority bundle when running on embedded devices.
- **Log request IDs.** Propagate `X-Request-Id` to correlate client and server logs during support investigations.
- **Handle schema changes.** Ignore unknown fields and default optional properties to ensure forward compatibility.

For SDKs, sample clients, and Postman collections, contact the Breem integrations team.
