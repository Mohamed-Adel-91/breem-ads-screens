<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\InsuranceOrderRequest;
use App\Models\Brand;
use App\Models\Insurance;
use App\Models\InsuranceOrder;
use App\Services\Insurance\InsuranceOrderService;
use App\Services\Insurance\InsurancePageService;
use Illuminate\Http\Request;

class InsuranceController extends Controller
{
    public function insurance(Request $request)
    {
        $brands = Brand::where('show_status', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        $insurance_programs = Insurance::select([
            'id',
            'insurance_company',
            'program_name',
            'company_logo',
            'coverage_rate',
            'annual_price',
            'features',
        ])
            ->orderBy('insurance_company')
            ->orderBy('program_name')
            ->get();
        return view('web.pages.insurance.insurance', [
            'brands' => $brands,
            'insurance_programs' => $insurance_programs,
        ]);
    }


    public function insuranceForm(Request $request, InsurancePageService $page)
    {
        $data = $page->build($request);
        return view('web.pages.insurance.insurance-form', $data);
    }

    public function submitInsurance(InsuranceOrderRequest $request, InsuranceOrderService $service)
    {
        $order = $service->create($request->validated(), $request);

        return response()->json([
            'message'   => 'تم تقديم طلب التأمين الخاص بك بنجاح',
            'reference' => $order->reference_number,
            'redirect'  => route('web.insurance.thanks', $order),
        ]);
    }

    public function thanks(InsuranceOrder $insuranceOrder)
    {
        return view('web.pages.insurance.thanks-insurance', [
            'order' => $insuranceOrder,
        ]);
    }
}
