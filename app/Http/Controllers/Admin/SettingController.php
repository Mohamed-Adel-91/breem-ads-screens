<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function edit(string $lang)
    {
        // Build a lightweight view model that matches the edit.blade expectations
        $vm = new class {
            public string $email = '';
            public string $hr_mail = '';
            public string $customer_service_mail = '';
            public string $phone = '';
            public string $hotline = '';
            public string $facebook = '';
            public string $youtube = '';
            public string $instagram = '';
            public string $linkedin = '';
            public string $location = '';
            public array $slogan = [];
            public array $address = [];
            public function getTranslation(string $field, string $lang, $fallback = false)
            {
                return $this->{$field}[$lang] ?? '';
            }
        };

        // Simple helpers
        $getStr = function (string $key) use ($lang) {
            $s = Setting::key($key)->first();
            if (!$s) return '';
            // For translatable values, prefer explicit locale
            try {
                $val = $s->getTranslation('value', $lang, false);
                if (is_string($val) && $val !== '') return $val;
            } catch (\Throwable $e) {
                // fallthrough
            }
            $v = $s->value;
            if (is_string($v)) return $v;
            if (is_array($v)) {
                // common fallbacks
                return (string)($v[$lang] ?? $v['en'] ?? $v['ar'] ?? ($v['value'] ?? ''));
            }
            return '';
        };

        $getTrans = function (string $key): array {
            $s = Setting::key($key)->first();
            return $s?->getTranslations('value') ?? [];
        };

        $getSocial = function (): array {
            $s = Setting::key('social.links')->first();
            return $s?->getTranslations('value') ?? [];
        };

        $vm->email = $getStr('email');
        $vm->hr_mail = $getStr('hr_mail');
        $vm->customer_service_mail = $getStr('customer_service_mail');
        // Phone is stored under site.phone
        $vm->phone = $getStr('site.phone');
        $vm->hotline = $getStr('hotline');

        $social = $getSocial();
        $vm->facebook = $social['facebook'] ?? '';
        $vm->youtube = $social['youtube'] ?? '';
        $vm->instagram = $social['instagram'] ?? '';
        $vm->linkedin = $social['linkedin'] ?? '';

        // Map location – stored as iframe embed under map.iframe[value.embed]
        $mapData = $getTrans('map.iframe');
        $embed = $mapData['embed'] ?? '';
        if (is_string($embed) && str_contains($embed, 'src=')) {
            if (preg_match('/src=\"([^\"]+)\"/i', $embed, $m)) {
                $vm->location = $m[1];
            } else {
                $vm->location = $embed;
            }
        } else {
            $vm->location = is_string($embed) ? $embed : '';
        }

        // Translatable fields
        $vm->slogan = $getTrans('slogan');
        $vm->address = $getTrans('address');

        return view('admin.settings.edit')->with([
            'pageName' => 'Settings',
            'data' => $vm,
            'lang' => $lang,
        ]);
    }

    public function update(SettingsRequest $request, string $lang)
    {
        $validated = $request->validated();

        // Upsert helper for translatable value
        $upsertTrans = function (string $key, array $translations) {
            $s = Setting::firstOrCreate(['key' => $key]);
            if (method_exists($s, 'setTranslations')) {
                $s->setTranslations('value', $translations);
            } else {
                $s->value = $translations;
            }
            $s->save();
        };

        // Upsert helper for plain array JSON
        $upsertJson = function (string $key, array $data) {
            $s = Setting::firstOrCreate(['key' => $key]);
            $s->value = $data;
            $s->save();
        };

        // Scalar fields – save as same across locales
        $locales = ['en', 'ar'];
        $sameForLocales = function (?string $val) use ($locales) {
            $out = [];
            foreach ($locales as $lc) { $out[$lc] = (string)($val ?? ''); }
            return $out;
        };

        if (array_key_exists('email', $validated)) {
            $upsertTrans('email', $sameForLocales($validated['email']));
        }
        if (array_key_exists('hr_mail', $validated)) {
            $upsertTrans('hr_mail', $sameForLocales($validated['hr_mail']));
        }
        if (array_key_exists('customer_service_mail', $validated)) {
            $upsertTrans('customer_service_mail', $sameForLocales($validated['customer_service_mail']));
        }
        if (array_key_exists('hotline', $validated)) {
            $upsertTrans('hotline', $sameForLocales($validated['hotline']));
        }
        if (array_key_exists('phone', $validated)) {
            // Keep header expectations in sync
            $upsertTrans('site.phone', $sameForLocales($validated['phone']));
        }

        // Social links (single JSON object)
        $social = Setting::key('social.links')->first()?->getTranslations('value') ?? [];
        foreach (['facebook', 'youtube', 'instagram', 'linkedin'] as $sn) {
            if (array_key_exists($sn, $validated)) {
                $social[$sn] = $validated[$sn] ?? '';
            }
        }
        $upsertJson('social.links', $social);

        // Map location – store as iframe embed
        if (array_key_exists('location', $validated)) {
            $url = (string) ($validated['location'] ?? '');
            $iframe = $url ? '<iframe src="' . e($url) . '" width="400" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>' : '';
            $upsertJson('map.iframe', ['embed' => $iframe]);
        }

        // Translatable arrays
        if (array_key_exists('slogan', $validated) && is_array($validated['slogan'])) {
            $upsertTrans('slogan', $validated['slogan']);
        }
        if (array_key_exists('address', $validated) && is_array($validated['address'])) {
            $upsertTrans('address', $validated['address']);
        }

        activity()
            ->causedBy(Auth::guard('admin')->user())
            ->withProperties($validated)
            ->log('Updated Settings');

        session()->flash('success', __('admin.forms.saved_successfully') ?? 'Settings updated successfully');
        return redirect()->route('admin.settings.edit', ['lang' => $lang]);
    }
}

