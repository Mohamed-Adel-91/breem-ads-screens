<?php

namespace App\Services;

use App\Models\CarModelColor;
use App\Models\Order;
use App\Enums\ApplicableToEnum;
use App\Enums\PaymentTypeEnum;

class ConfirmBookingService
{
    public function build(Order $order, bool $withPayment = false): array
    {
        $order->load('bookingCarClone', 'firstColor', 'secondColor', 'term.model.brand');

        $isCash = (int) $order->getRawOriginal('payment_type') === PaymentTypeEnum::CASH;
        $ctype = (int) $order->getRawOriginal('customer_type');
        $isIndiv = !$isCash && $ctype === ApplicableToEnum::INDIVIDUAL;
        $isComp = !$isCash && $ctype === ApplicableToEnum::COMPANY;

        $firstColorName = optional($order->firstColor)->name ?? ($order->bookingCarClone->color_name ?? '');
        $secondColorName = optional($order->secondColor)->name ?? ($order->bookingCarClone->second_color_name ?? '');
        $paymentLabel = $isCash ? 'نقدي' : 'تقسيط';
        $firstColorImage = null;
        $brandLogo = null;
        $firstColorId = $order->first_color_id ?? $order->bookingCarClone->color_id;
        if ($firstColorId) {
            $colorPivot = CarModelColor::where('car_model_id', $order->bookingCarClone->car_model_id)
                ->where('color_id', $firstColorId)
                ->first();
            $firstColorImage = $colorPivot?->image_path;
        }
        if ($order->bookingCarClone->car_brand_id) {
            $brandLogo = $order->term->model->brand->logo_path;
        }

        $data = [
            'order' => $order,
            'bookingCarClone' => $order->bookingCarClone,
            'isCash' => $isCash,
            'ctype' => $ctype,
            'isIndiv' => $isIndiv,
            'isComp' => $isComp,
            'paymentLabel' => $paymentLabel,
            'firstColorName' => $firstColorName,
            'secondColorName' => $secondColorName,
            'firstColorImage' => $firstColorImage,
            'brandLogo' => $brandLogo,
        ];

        if ($withPayment) {
            $kashierService = new KashierService();
            $data['kashierConfig'] = $kashierService->getPaymentConfig($order);
        }

        return $data;
    }
}
