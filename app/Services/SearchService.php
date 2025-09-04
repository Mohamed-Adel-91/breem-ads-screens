<?php

namespace App\Services;

use App\Models\CarModel;
use Illuminate\Database\Eloquent\Collection;

class SearchService
{
    public function search(array $filters): Collection
    {
        $brandId   = $filters['brandId']   ?? null;
        $modelId   = $filters['modelId']   ?? null;
        $termId    = $filters['termId']    ?? null;
        $priceFrom = $filters['priceFrom'] ?? null;
        $priceTo   = $filters['priceTo']   ?? null;

        $from = is_numeric($priceFrom) ? (float) $priceFrom : null;
        $to   = is_numeric($priceTo)   ? (float) $priceTo   : null;

        return CarModel::query()
            ->with(['brand', 'colors', 'terms.specs'])
            ->where('show_status', true)
            ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
            ->when($modelId, fn($q) => $q->where('id', $modelId))
            ->when($termId, fn($q) => $q->whereHas('terms', fn($t) => $t->where('id', $termId)))
            ->when($from !== null || $to !== null, function ($q) use ($from, $to, $termId) {
                $q->where(function ($w) use ($from, $to, $termId) {
                    $apply = function ($builder, $col) use ($from, $to) {
                        if ($from !== null) $builder->where($col, '>=', $from);
                        if ($to   !== null) $builder->where($col, '<=', $to);
                    };

                    if ($termId) {
                        $w->whereHas('terms', function ($t) use ($termId, $apply) {
                            $t->where('id', $termId);
                            $apply($t, 'price');
                        })->orWhere(function ($w2) use ($apply) {
                            $apply($w2, 'start_price');
                        });
                    } else {
                        $w->where(function ($w2) use ($apply) {
                            $apply($w2, 'start_price');
                        })->orWhereHas('terms', function ($t) use ($apply) {
                            $apply($t, 'price');
                        });
                    }
                });
            })
            ->orderBy('brand_id')
            ->orderBy('name')
            ->get();
    }
}
