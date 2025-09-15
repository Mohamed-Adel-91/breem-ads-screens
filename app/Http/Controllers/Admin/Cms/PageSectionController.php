<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageSectionController extends Controller
{
    public function toggle(Request $request, PageSection $section)
    {
        $section->is_active = ! (bool) $section->is_active;
        $section->save();
        return response()->json(['ok' => true, 'is_active' => (bool) $section->is_active]);
    }

    public function update(Request $request, PageSection $section)
    {
        $data = $request->validate([
            'type' => ['nullable', 'string', 'max:100'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'section_data' => ['nullable', 'array'],
            'uploads' => ['nullable', 'array'],
            'uploads.*' => ['nullable', 'file', 'max:30720'], // up to ~30MB
        ]);

        // Merge file uploads into section_data as paths
        $sectionData = $data['section_data'] ?? (array) ($section->section_data ?? []);
        $uploads = $request->file('uploads', []);
        foreach ($uploads as $key => $file) {
            if (!$file) continue;
            // Store on public disk, then expose via storage path
            $stored = $file->store('cms', 'public'); // cms/<file>
            if ($stored) {
                $sectionData[$key] = 'storage/' . $stored;
            }
        }

        DB::transaction(function () use ($section, $data, $sectionData) {
            if (array_key_exists('type', $data)) $section->type = $data['type'];
            if (array_key_exists('order', $data)) $section->order = $data['order'];
            if (array_key_exists('is_active', $data)) $section->is_active = (bool) $data['is_active'];
            // Always persist computed section data if any provided/merged
            if (!empty($sectionData) || array_key_exists('section_data', $data)) {
                $section->section_data = $sectionData;
            }
            $section->save();
        });

        return response()->json(['ok' => true, 'section' => $section->fresh('items')]);
    }

    public function destroy(PageSection $section)
    {
        $section->items()->delete();
        $section->delete();
        return response()->json(['ok' => true]);
    }
}
