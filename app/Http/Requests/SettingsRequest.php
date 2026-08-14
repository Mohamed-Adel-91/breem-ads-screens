<?php

namespace App\Http\Requests;

use App\Rules\MapEmbedUrl;
use App\Rules\WhatsAppLink;
use App\Services\LayoutService;
use App\Support\SocialPlatforms;
use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize()
    {
        // Authorization is the route's job here, as it is for every other admin module:
        // routes/admin.php gates settings.edit / settings.update with the matching
        // spatie permission. Duplicating the check would give two places to change.
        return true;
    }

    public function rules()
    {
        return [
            /*
             * `hr_mail`, `customer_service_mail`, `hotline` and `slogan` used to be
             * declared here. They had no form field, no stored row, no reader anywhere in
             * the application and no test — the rules were the only trace of them. They
             * are gone rather than left as a contract the controller does not honour.
             */
            'email' => 'nullable|email',

            /*
             * Dialable characters only. The stored Arabic value is `99654334+` — a leading
             * `+` typed into an RTL field — so the sign is allowed anywhere rather than
             * anchored to the front, and Arabic-Indic digits are accepted because that is
             * what the site shows the person editing it. LayoutService::telHref() is what
             * normalises the value into an ASCII `tel:` href.
             */
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9\x{0660}-\x{0669}\s()+\-]+$/u'],

            // A postal address is one line of the footer, not a paragraph.
            'address.en' => 'nullable|string|max:255',
            'address.ar' => 'nullable|string|max:255',

            /*
             * The footer map. Validated as a Google Maps EMBED url specifically — a share
             * link or a search url is not embeddable and renders as a refused frame, which
             * reads as a broken site rather than a bad setting.
             */
            'location' => ['nullable', 'url:https', 'max:2048', new MapEmbedUrl()],

            /*
             * Branding. The same shape the CMS uses for its own media, so both go through
             * FileService's replace-on-commit lifecycle rather than a second uploader.
             * SVG is not accepted: it is a script-carrying document, and every logo this
             * project ships is a raster file.
             */
            'header_logo' => 'nullable|file|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'footer_logo' => 'nullable|file|image|mimes:jpg,jpeg,png,gif,webp|max:5120',

            /*
             * Published to screen devices through the Device API's allow-list, NOT used by
             * the website. Kept short because it is a control label on a device screen.
             */
            'lang_switch.en' => 'nullable|string|max:32',
            'lang_switch.ar' => 'nullable|string|max:32',

            /*
             * The generic key/value editor. Bounded so a paste cannot exceed the column,
             * and restricted to keys that already exist so the form cannot invent rows.
             *
             * The business fields above own their keys exclusively — the edit view hides
             * them from this list, because two editors writing one key means whichever
             * branch of the controller runs last silently wins.
             */
            'settings' => 'nullable|array',
            'settings.*' => 'nullable|string|max:65535',

            ...$this->socialRules(),
        ];
    }

    /**
     * One rule per supported social channel, derived from the registry.
     *
     * Spelled out from App\Support\SocialPlatforms rather than by hand so a channel can
     * never be added to the form and then silently skipped by the validator.
     *
     * `url:https` and not a bare `url`. The bare rule accepts any scheme that parses,
     * which lets `data://…` through — and these values are written straight into an `href`
     * on every page of the public site. `javascript:` is rejected either way (it has no
     * authority component), but relying on that is relying on a side effect.
     *
     * WhatsApp is the exception: it is a click-to-chat endpoint carrying a phone number,
     * not a profile page, so it gets its own rule.
     *
     * @return array<string, list<mixed>>
     */
    private function socialRules(): array
    {
        $rules = [];

        foreach (SocialPlatforms::keys() as $platform) {
            $rules[$platform] = $platform === 'whatsapp'
                ? ['nullable', 'string', 'max:255', new WhatsAppLink()]
                : ['nullable', 'url:https', 'max:255'];
        }

        return $rules;
    }

    /**
     * Reject a generic `settings[...]` key that the typed fieldset already owns.
     *
     * Belt and braces for the view hiding them: a hand-crafted POST could otherwise
     * write raw HTML into `map.iframe` and bypass App\Rules\MapEmbedUrl entirely, which
     * is the exact hole this task closed.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $submitted = array_keys((array) $this->input('settings', []));
            $reserved = array_merge(LayoutService::BUSINESS_KEYS, LayoutService::LEGACY_KEYS);

            foreach (array_intersect($submitted, $reserved) as $key) {
                $validator->errors()->add(
                    'settings.' . $key,
                    __('validation.setting_key_reserved', ['key' => $key]),
                );
            }
        });
    }
}
