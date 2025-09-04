<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\Admin\OrderAdminService;
use Illuminate\Http\Request;
use App\Models\Order;
class OrderController extends Controller
{
    public function __construct(private OrderAdminService $service) {}
    public function index(Request $request)
    {
        $data = $this->service->index($request);
        return view('admin.finance_and_payment.booking-car-orders.index')->with([
            'pageName' => 'قائمة الطلبات',
            'data'     => $data,
            'filters'  => [],
        ]);
    }
    public function show(Order $order)
    {
        $payload = $this->service->show($order);
        return view('admin.finance_and_payment.booking-car-orders.show')->with([
            'pageName' => 'عرض الطلبات',
            'order'    => $payload['order'],
            'data' => $payload['data'],
        ]);
    }
}
