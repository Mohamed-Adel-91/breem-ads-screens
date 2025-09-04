<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstallmentServiceOrderRequest;
use App\Models\Brand;
use App\Models\InstallmentProgram;
use App\Models\InstallmentOrder;
use App\Models\ServiceCenter;
use App\Helpers\LocationParser;
use App\Services\Installment\InstallmentOrderService;
use App\Services\Installment\InstallmentPageService;
use Illuminate\Http\Request;

class InstallmentController extends Controller
{

    public function __construct(
        private InstallmentPageService $installmentPage,
        private InstallmentOrderService $orderService,
    ) {}

    public function installment(Request $request)
    {
        $brands = Brand::where('show_status', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        $installment_programs = InstallmentProgram::with('bank')->get();
        return view('web.pages.installment.installment')->with([
            'installment_programs' => $installment_programs,
            'brands' => $brands,
        ]);
    }

    public function installmentForm(Request $request, InstallmentPageService $page)
    {
        $payload = $page->build($request);
        $brandId = $request->query('brandId');   
        $payload['branches'] = ServiceCenter::with('brand')
            ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'brand_id', 'city'])
            ->map(function ($branch) {
                [$lat, $lng] = LocationParser::parse($branch->location);
                $branch->latitude = $lat;
                $branch->longitude = $lng;
                return $branch;
            });
        return view('web.pages.installment.installment-form', $payload);
    }

    public function submitInstallment(InstallmentServiceOrderRequest $request, InstallmentOrderService $orders)
    {
        $order = $orders->create($request);
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message'          => 'تم تقديم الطلب بنجاح!',
                'reference_number' => $order->reference_number,
                'redirect_url'     => route('web.installment.thanks', $order),
            ], 201);
        }
        return redirect()
            ->route('web.installment.thanks', $order)
            ->with('success', 'تم تقديم الطلب بنجاح. رقمك المرجعي: ' . $order->reference_number);
    }

    public function thanks(InstallmentOrder $installmentOrder)
    {
        return view('web.pages.installment.thanks-installment', [
            'order' => $installmentOrder,
        ]);
    }
}
