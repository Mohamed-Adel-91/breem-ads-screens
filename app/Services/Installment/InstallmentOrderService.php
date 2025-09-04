<?php

namespace App\Services\Installment;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\CarTerm;
use App\Models\InstallmentOrder;
use App\Models\InstallmentProgram;
use App\Models\ServiceCenter;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstallmentOrderService
{
    public function __construct(
        private InstallmentCalculator $calculator,
        private FileService $fileService,
    ) {}

    public function create(Request $request): InstallmentOrder
    {
        return DB::transaction(function () use ($request) {
            $data = $request->all();

            $price    = (float) ($data['car_price'] ?? 0);
            $dpAmount = isset($data['down_payment']) ? max(0, (float)$data['down_payment']) : null;
            $dpPct    = isset($data['down_payment_percent']) ? max(0, min(100, (float)$data['down_payment_percent'])) : null;

            if ($price > 0) {
                if ($dpAmount === null && $dpPct !== null) {
                    $dpAmount = round(($dpPct / 100.0) * $price);
                } elseif ($dpAmount !== null && $dpPct === null) {
                    $dpPct = $price > 0 ? round(($dpAmount / $price) * 100) : 0.0;
                }
                $dpAmount = min($dpAmount ?? 0.0, $price);
                $dpPct    = min(max($dpPct ?? 0.0, 0.0), 100.0);
            } else {
                $dpAmount = $dpPct = null;
            }

            $branch  = ServiceCenter::select('id', 'name')->find($data['branch_id'] ?? null);
            $brand   = Brand::select('id', 'name')->find($data['brand_id'] ?? null);
            $model   = CarModel::select('id', 'name')->find($data['car_model_id'] ?? null);
            $term    = CarTerm::select('id', 'term_name')->find($data['term_id'] ?? null);
            $program = InstallmentProgram::with('bank:id,name')
                ->select('id', 'bank_id', 'name', 'interest_rate_per_year')
                ->find($data['program_id'] ?? null);

            $apr   = $program?->interest_rate_per_year ? (float)$program->interest_rate_per_year : 0.0;
            $tenor = (int) ($data['tenor_months'] ?? 0);

            $monthlyAtSubmission = null;
            $totalAtSubmission   = null;
            if ($price && $tenor) {
                $summary = $this->calculator->summarize($price, $dpAmount ?? 0.0, $tenor, $apr);
                $monthlyAtSubmission = $summary['monthly'] ?? null;
                $totalAtSubmission   = $summary['total'] ?? null;
            }

            $order = InstallmentOrder::create([
                'dealing_type' => (int) $data['dealing_type'],

                'branch_id'   => $branch?->id,
                'branch_name' => $branch?->name,

                'brand_id'    => $brand?->id,
                'brand_name'  => $brand?->name,

                'car_model_id'   => $model?->id,
                'car_model_name' => $model?->name,

                'term_id'    => $term?->id,
                'term_name'  => $term?->term_name,

                'program_id'   => $program?->id,
                'program_name' => $program?->name,
                'bank_name'    => $program?->bank?->name,
                'program_interest_rate_per_year' => $apr,

                'tenor_months'                  => $tenor,
                'car_price'                     => $price,
                'down_payment'                  => $dpAmount,
                'down_payment_percent'          => $dpPct,
                'monthly_payment_at_submission' => $monthlyAtSubmission,
                'total_payable_at_submission'   => $totalAtSubmission,

                'full_name'   => $data['full_name'] ?? null,
                'phone'       => $data['phone'] ?? null,
                'email'       => $data['email'] ?? null,
                'national_id' => $data['national_id'] ?? null,

                'company_name'                    => $data['company_name'] ?? null,
                'representative_phone'            => $data['representative_phone'] ?? null,
                'company_email'                   => $data['company_email'] ?? null,
                'commercial_registration_number'  => $data['commercial_registration_number'] ?? null,

                'car_owned_by_other' => $data['car_owned_by_other'] ?? null,
                'agreed_terms'       => $request->boolean('policy'),
            ]);

            $order->update([
                'reference_number' => sprintf(
                    'INST-%d-%s-%d-%04d',
                    $order->id,
                    now()->format('Ymd'),
                    (int) ($order->program_id ?? 0),
                    random_int(1000, 9999)
                ),
            ]);

            $personalFields = [
                'front_national_id_image',
                'back_national_id_image',
                'bank_statement',
                'hr_letter',
            ];

            $companyFields  = [
                'commercial_registration_image',
                'tax_card_image',
                'company_bank_statement',
            ];

            $isPersonal = ((int)$order->dealing_type === 1);
            $baseFolder = $isPersonal
                ? 'attachments/installments/personal'
                : 'attachments/installments/company';

            $this->fileService->storeFiles(
                $order,
                $request,
                $isPersonal ? $personalFields : $companyFields,
                $baseFolder
            );

            return $order->refresh();
        });
    }
}
