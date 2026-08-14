<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use App\Services\LayoutService;
use App\Support\SocialPlatforms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    /**
     * Where an uploaded logo is written.
     *
     * Under `cms/`, with the rest of the managed media, so media_path() resolves it and
     * the test suite's `media.upload_root` override keeps real files untouched.
     */
    private const BRANDING_FOLDER = 'cms/branding';

    /**
     * Setting key => the form field that uploads it.
     *
     * @var array<string, string>
     */
    private const LOGO_FIELDS = [
        'header.logo' => 'header_logo',
        'footer.logo' => 'footer_logo',
    ];

    public function __construct(
        private LayoutService $layout,
        private FileServiceInterface $fileService,
    ) {}

    public function edit(string $lang)
    {
        $stored = Setting::whereIn('key', LayoutService::BUSINESS_KEYS)->get()->keyBy('key');

        $translations = fn (string $key): array => $stored->get($key)?->getTranslations('value') ?? [];

        /*
         * Social URLs on CANONICAL keys. SocialPlatforms::normalise() is what carries a
         * value stored under the legacy `twitter` key into the `x` field, so an
         * administrator sees the link the website is really using rather than an empty box
         * next to a working icon.
         */
        $socials = SocialPlatforms::normalise($translations('social.links'));

        /*
         * Logos are shown as PREVIEWS, not as editable paths. The resolved src comes from
         * the same service the public site reads, so the picture in the admin is literally
         * the picture on the website — including the fallback when nothing is configured.
         */
        $resolved = $this->layout->getSettings();

        return view('admin.settings.edit')->with([
            'pageName' => 'Settings',
            'lang' => $lang,
            'data' => [
                'email' => $this->localised($stored->get('email'), $lang),
                'phone' => $this->localised($stored->get('site.phone'), $lang),
                'address' => $translations('address'),
                /*
                 * Read back through the service the public footer reads, so the field
                 * shows the URL the website is actually embedding rather than a second
                 * opinion parsed by a second regex here. A stored value that no longer
                 * passes App\Rules\MapEmbedUrl comes back empty — the honest answer,
                 * because the footer is not rendering it either.
                 */
                'location' => $resolved['map_embed_url'] ?? '',
                'socials' => $socials,
                'logos' => [
                    'header.logo' => $resolved['header_logo'],
                    'footer.logo' => $resolved['footer_logo'],
                ],
                'logo_configured' => [
                    'header.logo' => filled($translations('header.logo')['image_path'] ?? null),
                    'footer.logo' => filled($translations('footer.logo')['image_path'] ?? null),
                ],
                'lang_switch' => $translations('site.lang_switch'),
            ],
            /*
             * Anything this page has no purpose-built control for. Both the typed keys and
             * the superseded ones are excluded: offering a key twice gives the page two
             * editors for one value, and update() writes the generic bucket last, so a raw
             * textarea would silently overwrite whatever the labelled field validated.
             *
             * On a standard installation this is empty and the card does not render.
             */
            'settings' => Setting::whereNotIn(
                'key',
                array_merge(LayoutService::BUSINESS_KEYS, LayoutService::LEGACY_KEYS),
            )->orderBy('key')->get(),
        ]);
    }

    public function update(SettingsRequest $request, string $lang)
    {
        $validated = $request->validated();

        /*
         * Uploads are written to disk before the transaction commits, so a rollback would
         * otherwise leave an orphaned file behind and — worse — the logo it replaced
         * already deleted. Same reconciliation the CMS controllers use.
         */
        try {
            DB::transaction(function () use ($request, $validated) {
                $this->saveContactDetails($validated);
                $this->saveSocialLinks($validated);
                $this->saveMap($validated);
                $this->saveLogos($request);
                $this->saveDeviceSettings($validated);
                $this->saveGenericSettings($request);
            });
        } catch (\Throwable $e) {
            $this->fileService->discardUploadedFiles();

            throw $e;
        }

        $this->fileService->commitReplacedFiles();

        activity()
            ->causedBy(Auth::guard('admin')->user())
            // Uploaded files are objects, not loggable values — record which logos were
            // replaced rather than trying to serialise the binaries.
            ->withProperties(array_merge(
                array_diff_key($validated, array_flip(array_values(self::LOGO_FIELDS))),
                ['logos_replaced' => array_values(array_filter(
                    self::LOGO_FIELDS,
                    fn (string $field): bool => $request->hasFile($field),
                ))],
            ))
            ->log('Updated Settings');

        session()->flash('success', __('admin.forms.saved_successfully') ?? 'Settings updated successfully');

        return redirect()->route('admin.settings.edit', ['lang' => $lang]);
    }

    // ------------------------------------------------------------------ persistence

    /**
     * @param  array<string, mixed>  $validated
     */
    private function saveContactDetails(array $validated): void
    {
        // Email and phone are not really translatable — they are one value the site shows
        // in both languages — but the column is, so the same string is written to each
        // locale rather than leaving one of them empty.
        foreach (['email' => 'email', 'phone' => 'site.phone'] as $field => $key) {
            if (array_key_exists($field, $validated)) {
                $this->putTranslations($key, $this->forEveryLocale($validated[$field]));
            }
        }

        if (array_key_exists('address', $validated) && is_array($validated['address'])) {
            $this->putTranslations('address', $validated['address']);
        }
    }

    /**
     * Write every social channel under its canonical key.
     *
     * THE LEGACY KEY IS RETIRED HERE, NOT DISCARDED. A URL stored under `twitter` has
     * already been carried into the `x` form field by SocialPlatforms::normalise(), so it
     * is saved back under `x` and the stale key is dropped — otherwise the old value stays
     * in the JSON forever, and the next person reading the row cannot tell which of the
     * two the site is using.
     *
     * @param  array<string, mixed>  $validated
     */
    private function saveSocialLinks(array $validated): void
    {
        $platforms = SocialPlatforms::keys();

        // Nothing social was submitted at all — a partial POST, not a request to clear
        // every channel.
        if (empty(array_intersect($platforms, array_keys($validated)))) {
            return;
        }

        $stored = Setting::key('social.links')->first()?->getTranslations('value') ?? [];
        $links = SocialPlatforms::normalise($stored);

        foreach ($platforms as $platform) {
            if (! array_key_exists($platform, $validated)) {
                continue;
            }

            $url = trim((string) ($validated[$platform] ?? ''));

            if ($url === '') {
                unset($links[$platform]);

                continue;
            }

            $links[$platform] = $url;
        }

        $this->putTranslations('social.links', $links);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function saveMap(array $validated): void
    {
        if (! array_key_exists('location', $validated)) {
            return;
        }

        $url = (string) ($validated['location'] ?? '');

        // The stored shape stays a complete element so nothing that reads this key has to
        // change. It is BUILT from a validated URL and never accepted as markup, and the
        // footer extracts the src and renders its own frame regardless.
        $embed = $url === ''
            ? ''
            : '<iframe src="' . e($url) . '" width="400" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';

        $this->putTranslations('map.iframe', ['embed' => $embed]);
    }

    /**
     * Replace a logo image, preserving the alternative text already stored with it.
     *
     * The alt is not on the form: it is the company name in each language, it is already
     * correct, and asking an operator to retype it every time they change a picture is how
     * it ends up blank.
     */
    private function saveLogos(Request $request): void
    {
        foreach (self::LOGO_FIELDS as $key => $field) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $setting = Setting::firstOrNew(['key' => $key]);
            $existing = $setting->getTranslations('value');

            $path = $this->fileService->uploadSingle(
                $request,
                $field,
                self::BRANDING_FOLDER,
                $existing['image_path'] ?? null,
            );

            if ($path === null) {
                continue;
            }

            $existing['image_path'] = $path;
            $setting->setTranslations('value', $existing);
            $setting->save();
        }
    }

    /**
     * Settings published to screen devices rather than to the website.
     *
     * `site.lang_switch` is in DeviceConfigService::ALLOWED_SETTING_KEYS, so it reaches
     * every paired screen through GET /api/v1/config. Its stored shape is a plain
     * locale => label map and must stay that way — the device contract reads it.
     *
     * @param  array<string, mixed>  $validated
     */
    private function saveDeviceSettings(array $validated): void
    {
        if (array_key_exists('lang_switch', $validated) && is_array($validated['lang_switch'])) {
            $this->putTranslations('site.lang_switch', $validated['lang_switch']);
        }
    }

    private function saveGenericSettings(Request $request): void
    {
        $dynamic = $request->input('settings', []);

        if (! is_array($dynamic) || empty($dynamic)) {
            return;
        }

        foreach ($dynamic as $key => $val) {
            // A structured value typed into the textarea is stored as structure, not as a
            // string that happens to look like JSON.
            $saveVal = $val;

            if (is_string($val)) {
                $trim = trim($val);

                if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
                    $decoded = json_decode($trim, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $saveVal = $decoded;
                    }
                }
            }

            $setting = Setting::firstOrCreate(['key' => $key]);
            $setting->value = $saveVal;
            $setting->save();
        }
    }

    // ----------------------------------------------------------------------- helpers

    /**
     * A translatable setting's value for one locale, as a plain string.
     */
    private function localised(?Setting $setting, string $lang): string
    {
        $translations = $setting?->getTranslations('value') ?? [];

        $value = $translations[$lang] ?? $translations['en'] ?? $translations['ar'] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * @return array<string, string>
     */
    private function forEveryLocale(?string $value): array
    {
        return array_fill_keys(LayoutService::LOCALES, (string) ($value ?? ''));
    }

    /**
     * Write a setting's whole value, REPLACING what was there.
     *
     * `replaceTranslations()` and not `setTranslations()`, and the difference is not
     * cosmetic: `setTranslations()` merges key by key, so a key absent from the new array
     * survives from the old one. For `social.links` — whose "translations" are platform
     * names rather than locales — that meant clearing a social field in the admin left the
     * old URL in storage and the icon still on the website. Emptying a field has to be
     * able to empty it.
     *
     * @param  array<string, mixed>  $value
     */
    private function putTranslations(string $key, array $value): void
    {
        $setting = Setting::firstOrNew(['key' => $key]);
        $setting->replaceTranslations('value', $value);
        $setting->save();
    }
}
