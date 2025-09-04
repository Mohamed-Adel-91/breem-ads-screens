<?php

namespace App\Services\Insurance;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\CarTerm;
use App\Models\Insurance;
use App\Models\InsuranceOrder;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InsuranceOrderService
{
    public function __construct(private FileService $files) {}

    public function create(array $data, Request $request): InsuranceOrder
    {
        return DB::transaction(function () use ($data, $request) {

            $brandId     = $data['brand_id']      ?? null;
            $modelId     = $data['car_model_id']  ?? null;
            $termId      = $data['car_term_id']   ?? null;
            $programId   = $data['insurance_id']  ?? null;
            $brand   = Brand::select('id', 'name')->find($data['brand_id']);
            $model   = CarModel::select('id', 'name')->find($data['car_model_id']);
            $term    = CarTerm::select('id', 'term_name')->find($data['car_term_id']);
            $program = Insurance::select('id', 'insurance_company', 'program_name', 'annual_price')
                ->find($data['insurance_id']);

            $annual_price_at_submission = (int)($data['annual_price_at_submission'] ?? ($program?->annual_price ?? 0));
            $pendingRef = 'INS-TMP-' . Str::uuid()->toString();
            $order = InsuranceOrder::create([
                'user_id'      => $data['user_id'] ?? auth()->id(),
                'brand_id'     => $brand?->id,
                'brand_name'   => $brand?->name,
                'car_model_id' => $model?->id,
                'car_model_name' => $model?->name,
                'car_term_id'  => $term?->id,
                'car_term_name' => $term?->term_name,

                'insurance_id'            => $program?->id,
                'insurance_program_name'  => $program ? ($program->insurance_company . ' - ' . $program->program_name) : null,
                'insurance_company_name'  => $program?->insurance_company,

                'car_price'                   => (float)($data['car_price'] ?? 0),
                'annual_price_at_submission'  => $annual_price_at_submission,

                'full_name'    => $data['full_name'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'individual_email' => $data['individual_email'] ?? null,
                'national_id'  => $data['national_id'] ?? null,

                'other_ownership' => (bool) ($data['other_ownership'] ?? false),
                'sale_blocked'    => (bool) ($data['sale_blocked'] ?? false),
                'chassis_number'  => $data['chassis_number'] ?? null,

                'agreed_terms' => (bool) ($data['agreed_terms'] ?? false),
                'reference_number' => $pendingRef,

            ]);

            $this->files->storeFiles($order, $request, [
                'front_national_id_image',
                'back_national_id_image',
                'car_license_image',
                'car_documentation_image',
            ], baseFolder: 'attachments/insurance');

            $ref = sprintf(
                'INSU-%d-%s-%d-%04d',
                $order->id,
                now()->format('Ymd'),
                (int)($order->insurance_id ?? 0),
                random_int(1000, 9999)
            );
            $order->update(['reference_number' => $ref]);

            return $order->refresh();
        });
    }
}
