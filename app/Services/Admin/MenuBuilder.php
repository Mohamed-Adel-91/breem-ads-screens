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

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($admin, $variant) {
            $items = config('admin_menu', []);

            return $this->filterItems($items, $admin, $variant);
        });
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
            $item['is_active'] = $this->isItemActive($item);
            $item['is_open'] = $item['is_active'] || $this->hasActiveChild($item);

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

    protected function isItemActive(array $item): bool
    {
        $patterns = Arr::wrap(Arr::get($item, 'active', []));

        if (!empty($item['route'])) {
            $patterns[] = $item['route'];
        }

        foreach (array_filter($patterns) as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        $url = Arr::get($item, 'url');
        if ($url && $url !== '#') {
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



