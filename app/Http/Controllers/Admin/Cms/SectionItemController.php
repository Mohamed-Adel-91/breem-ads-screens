<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\SectionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * These routes live under a `{lang?}` prefix, so every action must declare the
 * locale as its first parameter. Laravel splices route values positionally, and
 * omitting it shifts the locale string onto the model argument.
 */
class SectionItemController extends Controller
{
    public function toggle(?string $lang, SectionItem $item)
    {
        // Activation lives in the section_items.is_active column. It used to be
        // written into the translated `data` JSON, which the public renderer
        // never read; translated content is left untouched here.
        $item->is_active = ! $item->is_active;
        $item->save();

        return response()->json(['ok' => true, 'is_active' => $item->is_active]);
    }

    public function update(?string $lang, SectionItem $item, Request $request)
    {
        $data = $request->validate([
            'order' => ['nullable', 'integer', 'min:0'],
            'data' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($item, $data) {
            if (array_key_exists('order', $data)) $item->order = $data['order'];
            if (array_key_exists('data', $data)) $item->data = $data['data'];
            if (array_key_exists('is_active', $data)) $item->is_active = (bool) $data['is_active'];
            $item->save();
        });

        return response()->json(['ok' => true, 'item' => $item->fresh()]);
    }

    public function destroy(?string $lang, SectionItem $item)
    {
        $item->delete();
        return response()->json(['ok' => true]);
    }
}
