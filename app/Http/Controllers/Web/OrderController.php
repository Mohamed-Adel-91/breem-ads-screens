<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BookingService;
use App\Services\BookingColorService;
use App\Services\ConfirmBookingService;
use App\Http\Requests\BookingOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Traits\FileUploadTrait;
use App\Enums\OrderStatusEnum;

class OrderController extends Controller
{
    use FileUploadTrait;

    public function booking(Request $request, BookingService $service)
    {
        $data = $service->build(
            (int)$request->input('id'),
            (int)$request->input('term_id'),
            $request->input('color_id') ? (int)$request->input('color_id') : null
        );

        return view('web.pages.order.booking', $data);
    }

    public function updateColor(Request $request, BookingColorService $service)
    {
        $validated = $request->validate([
            'booking_clone_id' => ['required', 'integer', 'exists:booking_car_clones,id'],
            'color_id'         => ['required', 'integer', 'exists:colors,id'],
            'is_second'        => ['nullable', 'boolean'],
        ]);

        $res = $service->update(
            (int)$validated['booking_clone_id'],
            (int)$validated['color_id'],
            (bool)($validated['is_second'] ?? false)
        );

        return response()->json($res);
    }

    public function status(Order $order)
    {
        $steps = [
            ['title' => 'تم حجز السيارة', 'description' => 'تم تأكيد طلبك وجاري المتابعة.', 'completed' => true],
            ['title' => 'يتم شحن السيارة', 'description' => 'السيارة قيد الشحن حالياً.', 'completed' => false],
            ['title' => 'تم استلام السيارة من خلال الوكيل', 'description' => 'تم استلام السيارة من الوكيل.', 'completed' => false],
            ['title' => 'تخضع حالياً لإجراءات قانونية', 'description' => 'السيارة في مرحلة الإجراءات القانونية.', 'completed' => false],
        ];

        $completedSteps = match ($order->status) {
            OrderStatusEnum::PAID => 2,
            default => 1,
        };

        foreach ($steps as $index => &$step) {
            $step['completed'] = $index < $completedSteps;
        }

        return response()->json([
            'order_id' => $order->id,
            'steps' => $steps,
        ]);
    }

    public function confirmBooking(Order $order, ConfirmBookingService $service)
    {
        $viewData = $service->build($order, true);
        return view('web.pages.order.confirmbooking', $viewData);
    }

    public function store(BookingOrderRequest $request, OrderService $service)
    {
        $data = $request->validated();

        $mapFiles = function (array $keys) use ($request) {
            $out = [];
            foreach ($keys as $k) {
                $out[$k] = $request->file($k);
            }
            return $out;
        };

        $fileKeys = [
            'cash_front_national_id_image',
            'cash_back_national_id_image',
            'installment_front_national_id_image',
            'installment_back_national_id_image',
            'installment_bank_statement',
            'installment_hr_letter',
            'installment_commercial_registration_image',
            'installment_tax_card_image',
            'installment_company_bank_statement',
        ];
        $data += $mapFiles($fileKeys);

        $uploader = function ($files, $folders, $attrs, $model) {
            return $this->uploadFile($files, $folders, $attrs, $model);
        };

        $order = $service->createFromRequest($data, $uploader);

        return redirect()->route('web.confirm-booking', ['order' => $order->id]);
    }

    public function thanks(Order $order, ConfirmBookingService $service)
    {
        if (
            $order->provider_order_reference &&
            $order->provider_transaction_reference &&
            !$order->inventory_decremented &&
            $order->status->is(OrderStatusEnum::PAID())
        ) {
            if ($order->term->inventory > 0) {
                $order->term()->where('inventory', '=', 0)->decrement('inventory');
                $order->inventory_decremented = true;
                $order->save();
            } else {
                return redirect()->back()->with('error', 'المخزون غير متاح');
            }
        }
        $viewData = $service->build($order);
        return view('web.pages.order.thanks-page', $viewData);
    }
}
