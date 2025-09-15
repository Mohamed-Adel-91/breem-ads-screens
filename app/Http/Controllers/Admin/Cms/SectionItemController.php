<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\SectionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SectionItemController extends Controller
{
    public function toggle(Request $request, SectionItem $item)
    {
        // Prefer a dedicated column if exists; if not, fall back to JSON `data.is_active`
        if (Schema::hasColumn($item->getTable(), 'is_active')) {
            $item->is_active = ! (bool) ($item->is_active ?? true);
        } else {
            $data = $item->data ?? [];
            $data['is_active'] = ! (bool) ($data['is_active'] ?? true);
            $item->data = $data;
        }
        $item->save();
        $isActive = isset($item->is_active) ? (bool)$item->is_active : (bool)($item->data['is_active'] ?? true);
        return response()->json(['ok' => true, 'is_active' => $isActive]);
    }

    public function update(Request $request, SectionItem $item)
    {
        $data = $request->validate([
            'order' => ['nullable', 'integer', 'min:0'],
            'data' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($item, $data) {
            if (array_key_exists('order', $data)) $item->order = $data['order'];
            if (array_key_exists('data', $data)) $item->data = $data['data'];
            if (array_key_exists('is_active', $data)) {
                if (Schema::hasColumn($item->getTable(), 'is_active')) {
                    $item->is_active = (bool) $data['is_active'];
                } else {
                    $tmp = $item->data ?? [];
                    $tmp['is_active'] = (bool) $data['is_active'];
                    $item->data = $tmp;
                }
            }
            $item->save();
        });

        return response()->json(['ok' => true, 'item' => $item->fresh()]);
    }

    public function destroy(SectionItem $item)
    {
        $item->delete();
        return response()->json(['ok' => true]);
    }
}
