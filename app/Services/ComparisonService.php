<?php

namespace App\Services;

use App\Enums\EngineTypeEnum;
use App\Models\Brand;
use App\Models\CarTerm;
use App\Models\CarTermFeature;
use App\Models\FeatureCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ComparisonService
{
    public function build(Request $request): array
    {
        $brands = Brand::with(['cars' => function ($q) {
            $q->where('show_status', true);
        }])
            ->where('show_status', true)
            ->get();
        $raw = collect($request->input('cars', []))->take(4)->values();
        $prefill = $raw->map(fn($r) => [
            'brand_id' => $r['brand_id'] ?? null,
            'model_id' => $r['model_id'] ?? null,
            'term_id'  => $r['term_id']  ?? null,
        ])->values();
        $termIds = $raw->pluck('term_id')->filter()->unique()->values()->all();
        $terms = !empty($termIds) ? $this->loadTerms($termIds) : collect();
        $featureRowBlocks = !empty($termIds)
            ? $this->buildFeatureRowBlocks($termIds)
            : collect();
        $topSpecsRows = $this->topSpecsRows();
        return [
            'brands'           => $brands,
            'terms'            => $terms,
            'featureRowBlocks' => $featureRowBlocks,
            'topSpecsRows'     => $topSpecsRows,
            'prefill'          => $prefill,
        ];
    }
    private function loadTerms(array $termIds): Collection
    {
        $norm = fn(?string $s) => $this->normalize($s);
        $terms = CarTerm::query()
            ->with([
                'model.brand',
                'specs',
                'features' => function ($q) {
                    $q->select('features.id', 'features.name', 'features.feature_category_id');
                },
            ])
            ->whereIn('id', $termIds)
            ->get()
            ->sortBy(fn($t) => array_search($t->id, $termIds))
            ->values();
        $terms->each(function (CarTerm $t) use ($norm) {
            $brand = $t->model->brand->name ?? '';
            $model = $t->model->name ?? '';
            $t->display_name = trim($brand . ' ' . $model . ' ' . ($t->term_name ?? ''));
            $t->price_formatted = $t->price
                ? number_format((float)$t->price, 0, '.', ',') . ' جنيه مصري'
                : '—';
            $t->spec_by_label = collect($t->specs)->mapWithKeys(function ($s) use ($norm) {
                $key = $norm($s->name ?? '');
                return [$key => $s->value ?? null];
            });
            $t->feat_by_label = $t->features->mapWithKeys(function ($f) use ($norm) {
                $key = $norm($f->name ?? '');
                return [$key => [
                    'value'  => $f->pivot->value ?? null,
                    'status' => (bool)($f->pivot->status ?? false),
                ]];
            });
            $engineTypeLabel = null;
            $engineType = $t->model->engine_type ?? null;
            if (!is_null($engineType)) {
                $engineTypeLabel = ($engineType instanceof EngineTypeEnum)
                    ? $engineType->description
                    : EngineTypeEnum::getDescription($engineType);
            } else {
                $engineTypeLabel = $t->spec_by_label[$norm('نوع المحرك')] ?? null;
            }
            $t->top = [
                'price'        => $t->price_formatted,
                'engine'       => $t->model->engine
                    ?? $t->spec_by_label[$norm('السعة اللترية')]
                    ?? null,
                'engine_type'  => $engineTypeLabel,
                'horse_power'  => $t->model->horse_power
                    ?? $t->spec_by_label[$norm('أقصى قدرة المحرك')]
                    ?? null,
                'torque'       => $t->model->torque
                    ?? $t->spec_by_label[$norm('أقصى عزم المحرك')]
                    ?? null,
                'gear_box' => $t->model->gear_box
                    ?? $t->spec_by_label[$norm('ناقل حركة')]
                    ?? null,
            ];
        });
        return $terms;
    }
    private function buildFeatureRowBlocks(array $termIds): Collection
    {
        $norm = fn(?string $s) => $this->normalize($s);
        $featureIdsUsed = CarTermFeature::whereIn('car_term_id', $termIds)
            ->pluck('feature_id')
            ->unique()
            ->values()
            ->all();
        if (empty($featureIdsUsed)) {
            return collect();
        }
        $categories = FeatureCategory::with([
            'features' => function ($q) use ($featureIdsUsed) {
                $q->select('id', 'feature_category_id', 'name')
                    ->whereIn('id', $featureIdsUsed)
                    ->orderBy('name');
            }
        ])
            ->whereHas('features', function ($q) use ($featureIdsUsed) {
                $q->whereIn('id', $featureIdsUsed);
            })
            ->orderBy('name')
            ->get();
        return $categories->map(function ($cat) use ($norm) {
            $rows = $cat->features->map(fn($f) => [
                'key'   => $norm($f->name),
                'label' => $f->name,
            ])->values();
            return [
                'title' => $cat->name,
                'rows'  => $rows,
            ];
        })->values();
    }
    private function topSpecsRows(): array
    {
        return [
            ['key' => 'price',        'label' => 'السعر'],
            ['key' => 'engine',       'label' => 'السعة اللترية'],
            ['key' => 'engine_type',  'label' => 'نوع المحرك'],
            ['key' => 'horse_power',  'label' => 'أقصى قدرة المحرك'],
            ['key' => 'torque',       'label' => 'أقصى عزم المحرك'],
            ['key' => 'gear_box',     'label' => 'ناقل حركة'],
        ];
    }
    private function normalize(?string $s): string
    {
        $s = $s ?? '';
        $s = preg_replace('/\s+/u', ' ', trim($s));
        return mb_strtolower($s, 'UTF-8');
    }
}
