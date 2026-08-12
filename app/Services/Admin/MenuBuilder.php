<?php

namespace App\Services\Admin;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class MenuBuilder
{
    public function build(?string $variant = 'sidebar'): array
    {
        $admin = auth()->guard('admin')->user();
        if (!$admin) {
            return [];
        }

        $variant = $variant ?: 'sidebar';
        $locale = App::getLocale();
        $userKey = $admin->getAuthIdentifier() ?? 'guest';
        $rolesSignature = $admin->getRoleNames()->sort()->implode('|');
        $permissionsSignature = method_exists($admin, 'getAllPermissions')
            ? $admin->getAllPermissions()->pluck('name')->sort()->implode('|')
            : '';
        $signature = md5($rolesSignature . '|' . $permissionsSignature);

        $cacheKey = sprintf('admin_menu:%s:%s:%s:%s', $variant, $locale, $userKey, $signature);

        $items = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($admin, $variant) {
            $items = config('admin_menu', []);

            return $this->filterItems($items, $admin, $variant);
        });

        return $this->markActiveItems($items);
    }

    protected function filterItems(array $items, Authenticatable $admin, string $variant, int $depth = 0): array
    {
        $result = [];

        foreach ($items as $item) {
            if (!$this->shouldRenderForVariant($item, $variant)) {
                continue;
            }

            if (!$this->passesPermissions($item, $admin)) {
                continue;
            }

            $item['key'] = $item['key'] ?? $this->makeKey($item);

            $children = Arr::get($item, 'children', []);
            if (!empty($children) && is_array($children)) {
                $item['children'] = $this->filterItems($children, $admin, $variant, $depth + 1);
            } else {
                $item['children'] = [];
            }

            if (empty($item['route']) && empty($item['url']) && empty($item['children'])) {
                continue;
            }

            $item['url'] = $this->resolveUrl($item);
            $item['is_active'] = false;
            $item['is_open'] = false;

            $result[] = $item;
        }

        return array_values($result);
    }

    protected function shouldRenderForVariant(array $item, string $variant): bool
    {
        if (!isset($item['variants'])) {
            return true;
        }

        $variants = Arr::wrap($item['variants']);

        return in_array($variant, $variants, true);
    }

    protected function passesPermissions(array $item, Authenticatable $admin): bool
    {
        $single = Arr::get($item, 'permission');
        if ($single && !$admin->can($single)) {
            return false;
        }

        $allPermissions = Arr::wrap(Arr::get($item, 'permissions_all', []));
        foreach ($allPermissions as $permission) {
            if (!$admin->can($permission)) {
                return false;
            }
        }

        $anyPermissions = Arr::wrap(Arr::get($item, 'permissions_any', []));
        if (!empty($anyPermissions)) {
            foreach ($anyPermissions as $permission) {
                if ($admin->can($permission)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    protected function resolveUrl(array $item): string
    {
        if (!empty($item['url'])) {
            return $item['url'];
        }

        $route = Arr::get($item, 'route');
        if ($route && Route::has($route)) {
            $parameters = $this->resolveRouteParameters($item);
            $url = route($route, $parameters);

            $query = Arr::get($item, 'query', []);
            if (!empty($query)) {
                $url .= '?' . http_build_query($query);
            }

            return $url;
        }

        return Arr::get($item, 'href', '#');
    }

    protected function resolveRouteParameters(array $item): array
    {
        $parameters = Arr::get($item, 'parameters', []);

        if (Arr::get($item, 'include_locale', true)) {
            $localeParam = request()->route('lang', App::getLocale());
            $parameters = array_merge(['lang' => $localeParam], $parameters);
        }

        return $parameters;
    }

    /**
     * An `active` entry is either a route-name pattern, or an array carrying a
     * pattern plus query-string constraints:
     *
     *     'active' => [
     *         'admin.ads.schedules.*',
     *         ['route' => 'admin.ads.index', 'query' => ['tab' => 'schedules']],
     *     ]
     *
     * A null constraint value means the parameter must be absent or empty, which
     * is what lets two siblings share one route and stay mutually exclusive.
     */
    protected function isItemActive(array $item): bool
    {
        $rules = Arr::wrap(Arr::get($item, 'active', []));

        // The item's own route only acts as an implicit rule when it declares no
        // explicit `active` list. Otherwise two siblings pointing at the same
        // route (All Ads / Schedules) would both match that route.
        if (empty($rules) && !empty($item['route'])) {
            $rules[] = $item['route'];
        }

        foreach ($rules as $rule) {
            if ($this->matchesActiveRule($rule)) {
                return true;
            }
        }

        // URL comparison is the fallback for entries that have no route at all.
        // Route-backed entries are decided by their patterns above, so a query
        // string cannot silently re-activate a sibling.
        $url = Arr::get($item, 'url');
        if (empty($item['route']) && $url && $url !== '#') {
            if (request()->fullUrlIs($url) || url()->current() === $url) {
                return true;
            }
        }

        foreach (Arr::get($item, 'children', []) as $child) {
            if (!empty($child['is_active'])) {
                return true;
            }
        }

        return false;
    }

    protected function matchesActiveRule(mixed $rule): bool
    {
        if (is_string($rule)) {
            return $rule !== '' && request()->routeIs($rule);
        }

        if (!is_array($rule)) {
            return false;
        }

        $pattern = Arr::get($rule, 'route');
        if (!$pattern || !request()->routeIs($pattern)) {
            return false;
        }

        return $this->matchesQuery(Arr::get($rule, 'query', []));
    }

    /**
     * @param  array<string, string|int|null>  $constraints
     */
    protected function matchesQuery(array $constraints): bool
    {
        foreach ($constraints as $key => $expected) {
            $actual = request()->query($key);

            if ($expected === null) {
                if ($actual !== null && $actual !== '') {
                    return false;
                }

                continue;
            }

            if ((string) $actual !== (string) $expected) {
                return false;
            }
        }

        return true;
    }

    protected function markActiveItems(array $items): array
    {
        foreach ($items as &$item) {
            if (!empty($item['children'])) {
                $item['children'] = $this->markActiveItems($item['children']);
            }

            $item['is_active'] = $this->isItemActive($item);
            $item['is_open'] = $item['is_active'] || $this->hasActiveChild($item);
        }

        unset($item);

        return $items;
    }

    protected function hasActiveChild(array $item): bool
    {
        foreach (Arr::get($item, 'children', []) as $child) {
            if (!empty($child['is_active']) || $this->hasActiveChild($child)) {
                return true;
            }
        }

        return false;
    }

    protected function makeKey(array $item): string
    {
        $raw = Arr::get($item, 'key')
            ?? Arr::get($item, 'title')
            ?? Arr::get($item, 'route')
            ?? Str::uuid()->toString();

        if (is_array($raw)) {
            $raw = json_encode($raw);
        }

        return Str::slug((string) $raw, '-');
    }

    public static function title(array $item): string
    {
        $title = Arr::get($item, 'title');

        if (is_array($title)) {
            $locale = App::getLocale();

            if (isset($title[$locale])) {
                return $title[$locale];
            }

            if (isset($title['en'])) {
                return $title['en'];
            }

            $first = reset($title);

            return is_string($first) ? $first : '';
        }

        if (is_string($title)) {
            return __($title);
        }

        $titleKey = Arr::get($item, 'title_key');
        if (is_string($titleKey)) {
            return __($titleKey);
        }

        return '';
    }
}



