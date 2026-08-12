<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * These routes live under a `{lang?}` prefix, so every action must declare the
 * locale as its first parameter. Laravel splices route values positionally, and
 * omitting it shifts the locale string onto the model argument.
 */
class PageSectionController extends Controller
{
    public function toggle(?string $lang, PageSection $section)
    {
        $section->is_active = ! (bool) $section->is_active;
        $section->save();
        return response()->json(['ok' => true, 'is_active' => (bool) $section->is_active]);
    }

    public function update(?string $lang, PageSection $section, Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable', 'string', 'max:100'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'section_data' => ['nullable', 'array'],
            'uploads' => ['nullable', 'array'],
            'uploads.*' => ['nullable', 'file', 'max:30720'], // up to ~30MB
        ]);

        // section_data is translatable: this screen edits one locale at a time,
        // which is what the form and the JSON textarea both render.
        $locale = app()->getLocale();
        $previousData = $this->localeData($section, $locale);
        $sectionData = $data['section_data'] ?? $previousData;
        $uploads = $request->file('uploads', []);

        // Tracked so the filesystem can be reconciled with the transaction's
        // outcome instead of being mutated ahead of it.
        $storedFiles = [];
        $supersededFiles = [];

        foreach ($uploads as $key => $file) {
            if (!$file) continue;
            // Store on public disk, then expose via storage path
            $stored = $file->store('cms', 'public'); // cms/<file>
            if ($stored) {
                $storedFiles[] = $stored;
                $previous = $previousData[$key] ?? null;

                if (is_string($previous) && str_starts_with($previous, 'storage/')) {
                    $supersededFiles[] = substr($previous, strlen('storage/'));
                }

                $sectionData[$key] = 'storage/' . $stored;
            }
        }

        try {
            DB::transaction(function () use ($section, $data, $sectionData, $locale) {
                if (array_key_exists('type', $data)) $section->type = $data['type'];
                if (array_key_exists('order', $data)) $section->order = $data['order'];
                if (array_key_exists('is_active', $data)) $section->is_active = (bool) $data['is_active'];
                // Always persist computed section data if any provided/merged.
                // setTranslation() targets one locale; assigning the array
                // directly would make Spatie read its keys as locale codes and
                // wipe the other language.
                if (!empty($sectionData) || array_key_exists('section_data', $data)) {
                    $section->setTranslation('section_data', $locale, $sectionData);
                }
                $section->save();
            });
        } catch (\Throwable $e) {
            // The database kept the old paths, so drop the new files instead.
            Storage::disk('public')->delete($storedFiles);

            throw $e;
        }

        // Committed: the replaced files are now unreferenced.
        Storage::disk('public')->delete($supersededFiles);

        return response()->json(['ok' => true, 'section' => $section->fresh('items')]);
    }

    /**
     * The stored payload for one locale, tolerating rows whose translation is
     * still a raw JSON string.
     */
    private function localeData(PageSection $section, string $locale): array
    {
        $value = $section->getTranslation('section_data', $locale, true);

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    public function destroy(?string $lang, PageSection $section)
    {
        $section->items()->delete();
        $section->delete();
        return response()->json(['ok' => true]);
    }
}
