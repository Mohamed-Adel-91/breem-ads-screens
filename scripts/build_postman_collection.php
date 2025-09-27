<?php

declare(strict_types=1);

chdir(__DIR__ . '/..');

$routeJson = shell_exec('php artisan route:list --json');

if ($routeJson === null) {

    fwrite(STDERR, "Failed to run php artisan route:list --json\n");

    exit(1);

}

$routes = json_decode($routeJson, true);

if (!is_array($routes)) {

    fwrite(STDERR, "Unable to decode route list.\n");

    exit(1);

}

function generateUuid(): string

{

    $data = random_bytes(16);

    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);

    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

}

function ensureDir(string $path): void

{

    if (!is_dir($path)) {

        mkdir($path, 0777, true);

    }

}

function routeSupportsMethod(array $route, string $method): bool

{

    $method = strtoupper($method);

    $routeMethod = strtoupper($route['method'] ?? '');

    if ($routeMethod === 'ANY') {

        return true;

    }

    $methods = array_map('strtoupper', explode('|', $routeMethod));

    return in_array($method, $methods, true);

}

function chooseMethod(?array $route, ?string $preferred = null): string

{

    if ($preferred) {

        return strtoupper($preferred);

    }

    if (!$route) {

        return 'GET';

    }

    $routeMethod = strtoupper($route['method'] ?? 'GET');

    if ($routeMethod === 'ANY') {

        return 'GET';

    }

    $methods = array_map('strtoupper', explode('|', $routeMethod));

    foreach ($methods as $method) {

        if (!in_array($method, ['HEAD', 'OPTIONS'], true)) {

            return $method;

        }

    }

    return $methods[0] ?? 'GET';

}

function findRoute(array $routes, string $uri, ?string $method = null): ?array

{

    foreach ($routes as $route) {

        if (($route['uri'] ?? '') !== $uri) {

            continue;

        }

        if ($method === null || routeSupportsMethod($route, $method)) {

            return $route;

        }

    }

    return null;

}

function convertUriToPath(string $uri): string

{

    $path = ltrim($uri, '/');

    $replacements = [

        '{lang?}' => '{{lang}}',

        '{ad}' => '{{ad_id}}',

        '{screen}' => '{{screen_id}}',

        '{schedule}' => '1',

        '{place}' => '1',

        '{report}' => '1',

        '{submission}' => '1',

    ];

    return str_replace(array_keys($replacements), array_values($replacements), $path);

}

function pathSegments(string $path): array

{

    $trimmed = trim($path, '/');

    if ($trimmed === '') {

        return [];

    }

    return array_values(array_filter(explode('/', $trimmed), static fn ($segment) => $segment !== ''));

}

function normalizeNewLines(string $code): string

{

    return str_replace(["\r\n", "\r"], "\n", $code);

}

function scriptLines(string $code): array

{

    $code = normalizeNewLines($code);

    $code = trim($code);

    if ($code === '') {

        return [];

    }

    return array_map(static fn ($line) => rtrim($line, "\r"), explode("\n", $code));

}

function jsonPretty(array $payload): string

{

    return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

}

function buildRequest(array $routes, array $definition): ?array

{

    $uri = $definition['uri'];

    $preferredMethod = $definition['method'] ?? null;

    $route = findRoute($routes, $uri, $preferredMethod);

    if (!$route && empty($definition['force'])) {

        return null;

    }

    $method = chooseMethod($route, $preferredMethod);

    $path = $definition['path'] ?? convertUriToPath($uri);

    $rawUrl = rtrim('{{base_url}}', '/');

    if ($path !== '') {

        $rawUrl .= '/' . ltrim($path, '/');

    }

    $url = [

        'raw' => $rawUrl,

        'host' => ['{{base_url}}'],

    ];

    $segments = pathSegments($path);

    if (!empty($segments)) {

        $url['path'] = $segments;

    }

    if (!empty($definition['query'])) {

        $url['query'] = $definition['query'];

    }

    $headers = array_map(static function (array $header): array {

        if (!isset($header['type'])) {

            $header['type'] = 'text';

        }

        return $header;

    }, $definition['headers'] ?? []);

    $request = [

        'method' => $method,

        'header' => $headers,

        'url' => $url,

    ];

    if (!empty($definition['body'])) {

        $request['body'] = $definition['body'];

    }

    if (!empty($definition['description'])) {

        $request['description'] = $definition['description'];

    }

    $item = [

        'name' => $definition['name'],

        'request' => $request,

        'response' => [],

    ];

    if (!empty($definition['tests'])) {

        $item['event'][] = [

            'listen' => 'test',

            'script' => [

                'type' => 'text/javascript',

                'exec' => scriptLines($definition['tests']),

            ],

        ];

    }

    if (!empty($definition['prerequest'])) {

        $item['event'][] = [

            'listen' => 'prerequest',

            'script' => [

                'type' => 'text/javascript',

                'exec' => scriptLines($definition['prerequest']),

            ],

        ];

    }

    return $item;

}

function buildSubfolder(string $name, array $items, string $description = ''): ?array

{

    $items = array_values(array_filter($items));

    if (empty($items)) {

        return null;

    }

    $folder = [

        'name' => $name,

        'item' => $items,

    ];

    if ($description !== '') {

        $folder['description'] = $description;

    }

    return $folder;

}

function collectionVariables(): array

{

    return [

        ['key' => 'base_url', 'value' => 'http://127.0.0.1:8000'],

        ['key' => 'lang', 'value' => 'en'],

        ['key' => 'device_uid', 'value' => 'KM3-ABC-123'],

        ['key' => 'screen_code', 'value' => 'SCR-001'],

        ['key' => 'screen_id', 'value' => '1'],

        ['key' => 'ad_id', 'value' => '1'],

        ['key' => 'etag', 'value' => ''],

        ['key' => 'admin_email', 'value' => 'admin@example.com'],

        ['key' => 'admin_password', 'value' => 'password'],

        ['key' => 'xsrf', 'value' => ''],

    ];

}

function buildDeviceFolder(array $routes): ?array

{

    $items = [];

    $items[] = buildRequest($routes, [

        'name' => 'Handshake',

        'uri' => 'api/v1/screens/handshake',

        'method' => 'POST',

        'description' => "Initiate the screen handshake and register the player. Returns screen metadata, config and current playlist.",

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/json'],

            ['key' => 'Accept', 'value' => 'application/json'],

            ['key' => 'X-Device-UID', 'value' => '{{device_uid}}'],

        ],

        'body' => [

            'mode' => 'raw',

            'raw' => jsonPretty([

                'device_uid' => '{{device_uid}}',

                'code' => '{{screen_code}}',

                'version' => '1.0.0',

                'capabilities' => [

                    'video' => true,

                    'image' => true,

                ],

            ]),

            'options' => ['raw' => ['language' => 'json']],

        ],

        'tests' => "pm.test('Status 200', function () {\n  pm.response.to.have.status(200);\n});",

        'force' => true,

    ]);

    $items[] = buildRequest($routes, [

        'name' => 'Heartbeat',

        'uri' => 'api/v1/screens/heartbeat',

        'method' => 'POST',

        'description' => 'Post periodic heartbeat data so the backend can monitor screen liveness.',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/json'],

            ['key' => 'Accept', 'value' => 'application/json'],

            ['key' => 'X-Device-UID', 'value' => '{{device_uid}}'],

        ],

        'body' => [

            'mode' => 'raw',

            'raw' => jsonPretty([

                'screen_id' => '{{screen_id}}',

                'current_ad_code' => 'AD-15',

                'ts' => '2025-09-22T10:20:00Z',

            ]),

            'options' => ['raw' => ['language' => 'json']],

        ],

        'tests' => "pm.test('Status 200', function () {\n  pm.response.to.have.status(200);\n});",

        'force' => true,

    ]);

    $items[] = buildRequest($routes, [

        'name' => 'Playlist',

        'uri' => 'api/v1/screens/{screen}/playlist',

        'method' => 'GET',

        'description' => 'Fetch the current playlist for a screen. Pass If-None-Match with the cached ETag to leverage 304 responses.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'application/json'],

            ['key' => 'X-Device-UID', 'value' => '{{device_uid}}'],

            ['key' => 'If-None-Match', 'value' => '{{etag}}', 'description' => 'Send the cached playlist ETag to receive 304 when unchanged.'],

        ],

        'path' => 'api/v1/screens/{{screen_id}}/playlist',

        'tests' => "const etag = pm.response.headers.get('ETag');\nif (etag) {\n  pm.collectionVariables.set('etag', etag);\n}\npm.test('Status 200 or 304', function () {\n  pm.expect([200, 304]).to.include(pm.response.code);\n});",

        'force' => true,

    ]);

    $items[] = buildRequest($routes, [

        'name' => 'Playbacks',

        'uri' => 'api/v1/playbacks',

        'method' => 'POST',

        'description' => 'Report completed ad playback for analytics.',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/json'],

            ['key' => 'Accept', 'value' => 'application/json'],

            ['key' => 'X-Device-UID', 'value' => '{{device_uid}}'],

        ],

        'body' => [

            'mode' => 'raw',

            'raw' => jsonPretty([

                'screen_id' => '{{screen_id}}',

                'ad_id' => '{{ad_id}}',

                'played_at' => '2025-09-22T10:26:21Z',

                'duration' => 20,

                'extra' => [

                    'bufferedMs' => 120,

                    'droppedFrames' => 2,

                ],

            ]),

            'options' => ['raw' => ['language' => 'json']],

        ],

        'tests' => "pm.test('Accepted', () => pm.expect([200, 202]).to.include(pm.response.code));",

        'force' => true,

    ]);

    $items[] = buildRequest($routes, [

        'name' => 'Config',

        'uri' => 'api/v1/config',

        'method' => 'GET',

        'description' => 'Retrieve global configuration tweaks for the player (polling intervals, feature flags, etc.).',

        'headers' => [

            ['key' => 'Accept', 'value' => 'application/json'],

            ['key' => 'X-Device-UID', 'value' => '{{device_uid}}'],

        ],

        'force' => true,

    ]);

    $items = array_values(array_filter($items));

    if (empty($items)) {

        return null;

    }

    return [

        'name' => 'Android v1 (Device)',

        'description' => 'Device API v1 endpoints consumed by the Android TV player. All requests send the X-Device-UID header.',

        'item' => $items,

    ];

}

function urlencodedBody(array $fields): array

{

    $entries = [];

    foreach ($fields as $key => $value) {

        $entries[] = ['key' => (string) $key, 'value' => (string) $value, 'type' => 'text'];

    }

    return ['mode' => 'urlencoded', 'urlencoded' => $entries];

}

function formDataBody(array $fields): array

{

    $entries = [];

    foreach ($fields as $field) {

        $entries[] = $field;

    }

    return ['mode' => 'formdata', 'formdata' => $entries];

}

function xsrfGuardScript(): string

{

    return <<<'JS'

if (!pm.collectionVariables.get('xsrf')) {

  throw new Error('Missing xsrf token. Run "Auth > Get CSRF cookie" first.');

}

JS;

}

function adminFolderPrerequestScript(): string

{

    return <<<'JS'

const skip = ['Get CSRF cookie'];

if (!pm.collectionVariables.get('xsrf') && !skip.includes(pm.info.requestName)) {

  console.warn('XSRF token is empty. Run "Auth > Get CSRF cookie" first.');

}

JS;

}

function buildAdminFolder(array $routes): ?array

{

    $subfolders = [];

    // Auth subfolder

    $authItems = [];

    $authItems[] = buildRequest($routes, [

        'name' => 'Get CSRF cookie',

        'uri' => 'sanctum/csrf-cookie',

        'method' => 'GET',

        'description' => 'Fetch the Sanctum CSRF cookie so that session-authenticated requests can include the XSRF token.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'application/json'],

        ],

        'tests' => "pm.test('CSRF cookie present', function () {\n  const xsrf = pm.cookies.get('XSRF-TOKEN');\n  if (xsrf) {\n    pm.collectionVariables.set('xsrf', xsrf);\n  }\n  pm.expect(xsrf, 'XSRF-TOKEN cookie').to.exist;\n});",

    ]);

    $authItems[] = buildRequest($routes, [

        'name' => 'Load login page',

        'uri' => '{lang?}/admin-panel/login',

        'method' => 'GET',

        'description' => 'Loads the HTML login form. Useful to confirm localization and middleware.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

        ],

        'path' => '{{lang}}/admin-panel/login',

    ]);

    $authItems[] = buildRequest($routes, [

        'name' => 'Login',

        'uri' => '{lang?}/admin-panel/login',

        'method' => 'POST',

        'description' => 'Submit admin credentials via form POST. Expects 302 redirect to the dashboard on success.',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/login',

        'body' => urlencodedBody([

            'email' => '{{admin_email}}',

            'password' => '{{admin_password}}',

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Login response status', function () {\n  pm.expect([200, 302]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $authItems[] = buildRequest($routes, [

        'name' => 'Verify OTP',

        'uri' => '{lang?}/admin-panel/login/verify-otp',

        'method' => 'POST',

        'description' => 'Confirm the one-time password challenge for admins with MFA enabled.',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'application/json,text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/login/verify-otp',

        'body' => urlencodedBody([

            'email' => '{{admin_email}}',

            'otp' => '123456',

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('OTP verification accepted', function () {\n  pm.expect([200, 302]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $authItems[] = buildRequest($routes, [

        'name' => 'Logout',

        'uri' => '{lang?}/admin-panel/logout',

        'method' => 'POST',

        'description' => 'Destroy the current admin session and return to the login screen.',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/logout',

        'body' => urlencodedBody([

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Logout redirects', function () {\n  pm.expect([200, 302]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $authFolder = buildSubfolder('Auth', $authItems, 'Session authentication helpers (HTML forms + Sanctum CSRF cookie).');

    if ($authFolder) {

        $subfolders[] = $authFolder;

    }

    // Ads subfolder

    $adsItems = [];

    $adsItems[] = buildRequest($routes, [

        'name' => 'List ads',

        'uri' => '{lang?}/admin-panel/ads',

        'method' => 'GET',

        'description' => 'Render the ads index table (HTML view).',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/ads',

        'tests' => "pm.test('Status 200', function () {\n  pm.response.to.have.status(200);\n});",

    ]);

    $adsItems[] = buildRequest($routes, [

        'name' => 'Create ad form',

        'uri' => '{lang?}/admin-panel/ads/create',

        'method' => 'GET',

        'description' => 'Display the ad creation form (HTML).',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/ads/create',

    ]);

    $adsItems[] = buildRequest($routes, [

        'name' => 'Store ad',

        'uri' => '{lang?}/admin-panel/ads',

        'method' => 'POST',

        'description' => 'Submit a new advertisement with translated metadata and primary asset upload.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/ads',

        'body' => formDataBody([

            ['key' => 'title[en]', 'value' => 'Summer Campaign', 'type' => 'text'],

            ['key' => 'title[ar]', 'value' => 'Summer Campaign (AR)', 'type' => 'text'],

            ['key' => 'description[en]', 'value' => 'Outdoor signage loop', 'type' => 'text'],

            ['key' => 'description[ar]', 'value' => 'Arabic copy placeholder', 'type' => 'text'],

            ['key' => 'file', 'type' => 'file', 'src' => ''],

            ['key' => 'file_type', 'value' => 'video', 'type' => 'text'],

            ['key' => 'duration_seconds', 'value' => '30', 'type' => 'text'],

            ['key' => 'start_date', 'value' => '2025-09-22', 'type' => 'text'],

            ['key' => 'end_date', 'value' => '2025-10-22', 'type' => 'text'],

            ['key' => '_token', 'value' => '{{xsrf}}', 'type' => 'text'],

        ]),

        'tests' => "pm.test('Ad created redirect', function () {\n  pm.expect([200, 302]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $adsItems[] = buildRequest($routes, [

        'name' => 'Show ad',

        'uri' => '{lang?}/admin-panel/ads/{ad}',

        'method' => 'GET',

        'description' => 'Show the ad detail page (HTML).',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/ads/{{ad_id}}',

    ]);

    $adsItems[] = buildRequest($routes, [

        'name' => 'Edit ad form',

        'uri' => '{lang?}/admin-panel/ads/{ad}/edit',

        'method' => 'GET',

        'description' => 'Load the edit form for an existing ad (HTML).',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/ads/{{ad_id}}/edit',

    ]);

    $adsItems[] = buildRequest($routes, [

        'name' => 'Update ad',

        'uri' => '{lang?}/admin-panel/ads/{ad}',

        'method' => 'PUT',

        'description' => 'Update ad metadata. Attach a new asset if needed (optional file field provided).',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/ads/{{ad_id}}',

        'body' => formDataBody([

            ['key' => 'title[en]', 'value' => 'Summer Campaign Updated', 'type' => 'text'],

            ['key' => 'title[ar]', 'value' => 'Summer Campaign Updated (AR)', 'type' => 'text'],

            ['key' => 'description[en]', 'value' => 'Updated copy for QA', 'type' => 'text'],

            ['key' => 'description[ar]', 'value' => 'Updated Arabic copy placeholder', 'type' => 'text'],

            ['key' => 'file', 'type' => 'file', 'src' => '', 'disabled' => true],

            ['key' => 'file_type', 'value' => 'video', 'type' => 'text'],

            ['key' => 'duration_seconds', 'value' => '25', 'type' => 'text'],

            ['key' => 'start_date', 'value' => '2025-09-22', 'type' => 'text'],

            ['key' => 'end_date', 'value' => '2025-11-01', 'type' => 'text'],

            ['key' => '_token', 'value' => '{{xsrf}}', 'type' => 'text'],

            ['key' => '_method', 'value' => 'PUT', 'type' => 'text', 'disabled' => true],

        ]),

        'tests' => "pm.test('Ad updated redirect', function () {\n  pm.expect([200, 302]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $adsItems[] = buildRequest($routes, [

        'name' => 'Delete ad',

        'uri' => '{lang?}/admin-panel/ads/{ad}',

        'method' => 'DELETE',

        'description' => 'Remove an ad permanently. Ensure dependent schedules are cleaned up.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/ads/{{ad_id}}',

        'body' => urlencodedBody([

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Ad deleted redirect', function () {\n  pm.expect([200, 302, 204]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $adsFolder = buildSubfolder('Ads', $adsItems, 'CRUD actions for advertisements and their assets.');

    if ($adsFolder) {

        $subfolders[] = $adsFolder;

    }

    // Schedules subfolder

    $schedulesItems = [];

    $schedulesItems[] = buildRequest($routes, [

        'name' => 'List schedules',

        'uri' => '{lang?}/admin-panel/ads/{ad}/schedules',

        'method' => 'GET',

        'description' => 'List schedules attached to an ad (HTML view with filters).',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/ads/{{ad_id}}/schedules',

    ]);

    $schedulesItems[] = buildRequest($routes, [

        'name' => 'Create schedule',

        'uri' => '{lang?}/admin-panel/ads/{ad}/schedules',

        'method' => 'POST',

        'description' => 'Create a new schedule slot for an ad and attach to a screen.',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/ads/{{ad_id}}/schedules',

        'body' => urlencodedBody([

            'screen_id' => '{{screen_id}}',

            'start_time' => '2025-09-22 08:00:00',

            'end_time' => '2025-09-22 18:00:00',

            'is_active' => '1',

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Schedule created redirect', function () {\n  pm.expect([200, 302]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $schedulesItems[] = buildRequest($routes, [

        'name' => 'Update schedule',

        'uri' => '{lang?}/admin-panel/ads/{ad}/schedules/{schedule}',

        'method' => 'PUT',

        'description' => 'Update timings or toggle active status for an existing schedule.',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/ads/{{ad_id}}/schedules/1',

        'body' => urlencodedBody([

            'screen_id' => '{{screen_id}}',

            'start_time' => '2025-09-23 08:00:00',

            'end_time' => '2025-09-23 20:00:00',

            'is_active' => '1',

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Schedule updated redirect', function () {\n  pm.expect([200, 302]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $schedulesItems[] = buildRequest($routes, [

        'name' => 'Delete schedule',

        'uri' => '{lang?}/admin-panel/ads/{ad}/schedules/{schedule}',

        'method' => 'DELETE',

        'description' => 'Delete a schedule entry from the ad.',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/ads/{{ad_id}}/schedules/1',

        'body' => urlencodedBody([

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Schedule deleted redirect', function () {\n  pm.expect([200, 302, 204]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $schedulesFolder = buildSubfolder('Schedules', $schedulesItems, 'Per-ad schedule management (HTML flows controlled by ScheduleController).');

    if ($schedulesFolder) {

        $subfolders[] = $schedulesFolder;

    }

    // Screens subfolder

    $screensItems = [];

    $screensItems[] = buildRequest($routes, [

        'name' => 'List screens',

        'uri' => '{lang?}/admin-panel/screens',

        'method' => 'GET',

        'description' => 'Screens dashboard showing device status and counts.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/screens',

    ]);

    $screensItems[] = buildRequest($routes, [

        'name' => 'Create screen form',

        'uri' => '{lang?}/admin-panel/screens/create',

        'method' => 'GET',

        'description' => 'HTML form to create a new playback screen.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/screens/create',

    ]);

    $screensItems[] = buildRequest($routes, [

        'name' => 'Store screen',

        'uri' => '{lang?}/admin-panel/screens',

        'method' => 'POST',

        'description' => 'Create a new screen and optionally attach a device UID.',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/screens',

        'body' => urlencodedBody([

            'place_id' => '1',

            'code' => 'SCR-QA-01',

            'device_uid' => '{{device_uid}}',

            'status' => 'online',

            'last_heartbeat' => '2025-09-22 09:00:00',

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Screen created redirect', function () {\n  pm.expect([200, 302]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $screensItems[] = buildRequest($routes, [

        'name' => 'Show screen',

        'uri' => '{lang?}/admin-panel/screens/{screen}',

        'method' => 'GET',

        'description' => 'Detailed screen view with logs, schedule and attached ads.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/screens/{{screen_id}}',

    ]);

    $screensItems[] = buildRequest($routes, [

        'name' => 'Edit screen form',

        'uri' => '{lang?}/admin-panel/screens/{screen}/edit',

        'method' => 'GET',

        'description' => 'Load the edit UI for a screen.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/screens/{{screen_id}}/edit',

    ]);

    $screensItems[] = buildRequest($routes, [

        'name' => 'Update screen',

        'uri' => '{lang?}/admin-panel/screens/{screen}',

        'method' => 'PUT',

        'description' => 'Update screen metadata or mark maintenance/offline.',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/screens/{{screen_id}}',

        'body' => urlencodedBody([

            'place_id' => '1',

            'code' => 'SCR-QA-01',

            'device_uid' => '{{device_uid}}',

            'status' => 'maintenance',

            'last_heartbeat' => '2025-09-22 11:00:00',

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Screen updated redirect', function () {\n  pm.expect([200, 302]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $screensItems[] = buildRequest($routes, [

        'name' => 'Delete screen',

        'uri' => '{lang?}/admin-panel/screens/{screen}',

        'method' => 'DELETE',

        'description' => 'Delete a screen (will detach related ads).',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/screens/{{screen_id}}',

        'body' => urlencodedBody([

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Screen deleted redirect', function () {\n  pm.expect([200, 302, 204]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $screensFolder = buildSubfolder('Screens', $screensItems, 'Manage playback screens, device assignment, and statuses.');

    if ($screensFolder) {

        $subfolders[] = $screensFolder;

    }

    // Places subfolder

    $placesItems = [];

    $placesItems[] = buildRequest($routes, [

        'name' => 'List places',

        'uri' => '{lang?}/admin-panel/places',

        'method' => 'GET',

        'description' => 'List venues/places hosting screens.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/places',

    ]);

    $placesItems[] = buildRequest($routes, [

        'name' => 'Create place form',

        'uri' => '{lang?}/admin-panel/places/create',

        'method' => 'GET',

        'description' => 'HTML creation form for a place.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/places/create',

    ]);

    $placesItems[] = buildRequest($routes, [

        'name' => 'Store place',

        'uri' => '{lang?}/admin-panel/places',

        'method' => 'POST',

        'description' => 'Create a new place with translated name/address.',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/places',

        'body' => urlencodedBody([

            'name[en]' => 'Downtown Mall',

            'name[ar]' => 'Downtown Mall AR',

            'address[en]' => 'Main street 101',

            'address[ar]' => 'Main street 101 AR',

            'type' => 'mall',

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Place created redirect', function () {\n  pm.expect([200, 302]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $placesItems[] = buildRequest($routes, [

        'name' => 'Show place',

        'uri' => '{lang?}/admin-panel/places/{place}',

        'method' => 'GET',

        'description' => 'View place details with associated screens.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/places/1',

    ]);

    $placesItems[] = buildRequest($routes, [

        'name' => 'Edit place form',

        'uri' => '{lang?}/admin-panel/places/{place}/edit',

        'method' => 'GET',

        'description' => 'HTML edit UI for a place.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/places/1/edit',

    ]);

    $placesItems[] = buildRequest($routes, [

        'name' => 'Update place',

        'uri' => '{lang?}/admin-panel/places/{place}',

        'method' => 'PUT',

        'description' => 'Update translated place metadata.',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/places/1',

        'body' => urlencodedBody([

            'name[en]' => 'Downtown Mall Updated',

            'name[ar]' => 'Downtown Mall Updated AR',

            'address[en]' => 'Main street 102',

            'address[ar]' => 'Main street 102 AR',

            'type' => 'mall',

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Place updated redirect', function () {\n  pm.expect([200, 302]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $placesItems[] = buildRequest($routes, [

        'name' => 'Delete place',

        'uri' => '{lang?}/admin-panel/places/{place}',

        'method' => 'DELETE',

        'description' => 'Delete an empty place (fails if screens are still attached).',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/places/1',

        'body' => urlencodedBody([

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Place delete response', function () {\n  pm.expect([200, 302, 204]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $placesFolder = buildSubfolder('Places', $placesItems, 'Manage locations that host screens.');

    if ($placesFolder) {

        $subfolders[] = $placesFolder;

    }

    // Monitoring subfolder

    $monitoringItems = [];

    $monitoringItems[] = buildRequest($routes, [

        'name' => 'Monitoring dashboard',

        'uri' => '{lang?}/admin-panel/monitoring',

        'method' => 'GET',

        'description' => 'Monitoring overview dashboard showing screen health metrics.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/monitoring',

    ]);

    $monitoringItems[] = buildRequest($routes, [

        'name' => 'Monitoring screen detail',

        'uri' => '{lang?}/admin-panel/monitoring/screens/{screen}',

        'method' => 'GET',

        'description' => 'Detailed monitoring page for a specific screen (HTML).',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/monitoring/screens/{{screen_id}}',

    ]);

    $monitoringItems[] = buildRequest($routes, [

        'name' => 'Acknowledge alert',

        'uri' => '{lang?}/admin-panel/monitoring/screens/{screen}/acknowledge',

        'method' => 'POST',

        'description' => 'Acknowledge monitoring alert and set a new status (JSON payload expected).',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/monitoring/screens/{{screen_id}}/acknowledge',

        'body' => urlencodedBody([

            'status' => 'online',

            'note' => 'Cleared after manual check',

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Alert acknowledged redirect', function () {\n  pm.expect([200, 302]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $monitoringFolder = buildSubfolder('Monitoring', $monitoringItems, 'Screen monitoring dashboards and alert acknowledgement.');

    if ($monitoringFolder) {

        $subfolders[] = $monitoringFolder;

    }

    // Logs subfolder

    $logsItems = [];

    $logsItems[] = buildRequest($routes, [

        'name' => 'Logs dashboard',

        'uri' => '{lang?}/admin-panel/logs',

        'method' => 'GET',

        'description' => 'View application logs inside the admin UI (HTML).',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/logs',

    ]);

    $logsItems[] = buildRequest($routes, [

        'name' => 'Download log archive',

        'uri' => '{lang?}/admin-panel/logs/download',

        'method' => 'GET',

        'description' => 'Download the latest log export (ZIP or CSV depending on configuration). Use Postman “Save Response” to persist the file.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'application/zip,text/csv'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/logs/download',

    ]);

    $logsFolder = buildSubfolder('Logs', $logsItems, 'Operational logs for diagnostics.');

    if ($logsFolder) {

        $subfolders[] = $logsFolder;

    }

    // Reports subfolder

    $reportsItems = [];

    $reportsItems[] = buildRequest($routes, [

        'name' => 'Reports index',

        'uri' => '{lang?}/admin-panel/reports',

        'method' => 'GET',

        'description' => 'List generated reports with filters for type/date.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/reports',

    ]);

    $reportsItems[] = buildRequest($routes, [

        'name' => 'Generate report',

        'uri' => '{lang?}/admin-panel/reports/generate',

        'method' => 'POST',

        'description' => 'Generate a new playback or uptime report. Responds with redirect to the generated report view.',

        'headers' => [

            ['key' => 'Content-Type', 'value' => 'application/x-www-form-urlencoded'],

            ['key' => 'Accept', 'value' => 'text/html,application/json'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/reports/generate',

        'body' => urlencodedBody([

            'name' => 'Ad performance September',

            'type' => 'playback',

            'from_date' => '2025-09-01',

            'to_date' => '2025-09-30',

            'screen_id' => '{{screen_id}}',

            'ad_id' => '{{ad_id}}',

            '_token' => '{{xsrf}}',

        ]),

        'tests' => "pm.test('Report generation redirect', function () {\n  pm.expect([200, 302]).to.include(pm.response.code);\n});",

        'prerequest' => xsrfGuardScript(),

    ]);

    $reportsItems[] = buildRequest($routes, [

        'name' => 'Show report',

        'uri' => '{lang?}/admin-panel/reports/{report}',

        'method' => 'GET',

        'description' => 'View a generated report (HTML table).',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/html'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/reports/1',

    ]);

    $reportsItems[] = buildRequest($routes, [

        'name' => 'Download report CSV',

        'uri' => '{lang?}/admin-panel/reports/{report}/download',

        'method' => 'GET',

        'description' => 'Download the report as CSV. Use Postman “Save Response” to store the file.',

        'headers' => [

            ['key' => 'Accept', 'value' => 'text/csv'],

            ['key' => 'X-XSRF-TOKEN', 'value' => '{{xsrf}}'],

        ],

        'path' => '{{lang}}/admin-panel/reports/1/download',

    ]);

    $reportsFolder = buildSubfolder('Reports', $reportsItems, 'Playback and uptime reporting workflows.');

    if ($reportsFolder) {

        $subfolders[] = $reportsFolder;

    }

    $subfolders = array_values(array_filter($subfolders));

    if (empty($subfolders)) {

        return null;

    }

    $adminFolder = [

        'name' => 'Admin',

        'description' => 'Admin panel (HTML + AJAX) endpoints grouped by feature area. Requires logged-in session with XSRF token.',

        'item' => $subfolders,

        'event' => [

            [

                'listen' => 'prerequest',

                'script' => [

                    'type' => 'text/javascript',

                    'exec' => scriptLines(adminFolderPrerequestScript()),

                ],

            ],

        ],

    ];

    return $adminFolder;

}

function environmentPayload(string $name, string $baseUrl): array

{

    $values = [

        ['key' => 'base_url', 'value' => $baseUrl, 'enabled' => true],

        ['key' => 'lang', 'value' => 'en', 'enabled' => true],

        ['key' => 'device_uid', 'value' => 'KM3-ABC-123', 'enabled' => true],

        ['key' => 'screen_code', 'value' => 'SCR-001', 'enabled' => true],

        ['key' => 'screen_id', 'value' => '1', 'enabled' => true],

        ['key' => 'ad_id', 'value' => '1', 'enabled' => true],

        ['key' => 'etag', 'value' => '', 'enabled' => true],

        ['key' => 'admin_email', 'value' => 'admin@example.com', 'enabled' => true],

        ['key' => 'admin_password', 'value' => 'password', 'enabled' => true],

        ['key' => 'xsrf', 'value' => '', 'enabled' => true],

    ];

    return [

        'name' => $name,

        'values' => $values,

        '_postman_variable_scope' => 'environment',

        '_postman_exported_using' => 'postman/10.x',

    ];

}

$deviceFolder = buildDeviceFolder($routes);

$adminFolder = buildAdminFolder($routes);

$collectionItems = array_values(array_filter([$deviceFolder, $adminFolder]));

$collection = [

    'info' => [

        '_postman_id' => generateUuid(),

        'name' => 'Breem Ads Screens - Full (Admin + Device API v1)',

        'description' => 'Full Postman collection covering the Android device API v1 and Laravel admin panel routes discovered via artisan route:list.',

        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',

    ],

    'item' => $collectionItems,

    'variable' => array_map(static fn (array $entry) => $entry + ['type' => 'default'], collectionVariables()),

];

ensureDir('docs/postman');

ensureDir('docs/postman/env');

file_put_contents('docs/postman/Breem-Ads-Full.postman_collection.json', json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$localEnv = environmentPayload('Breem Local', 'http://127.0.0.1:8000');

$productionEnv = environmentPayload('Breem Production', 'https://breemads.example.com');

file_put_contents('docs/postman/env/Breem-Local.postman_environment.json', json_encode($localEnv, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

file_put_contents('docs/postman/env/Breem-Production.postman_environment.json', json_encode($productionEnv, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Postman collection and environments generated under docs/postman/." . PHP_EOL;

