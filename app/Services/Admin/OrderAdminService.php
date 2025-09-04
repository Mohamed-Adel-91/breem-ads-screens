<?php

namespace App\Services\Admin;

use App\Enums\ApplicableToEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentTypeEnum;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderAdminService
{
    public function index(Request $request)
    {
        $query = Order::query()
            ->with([
                'user:id,full_name,mobile',
                'term:id,term_name',
                'firstColor:id,name',
                'secondColor:id,name',
                'bank:id,name',
                'bookingCarClone:id,order_id,car_brand_name,car_model_name,car_term_name',
            ])
            ->latest();

        if ($request->filled('status')) $query->where('status', $request->input('status'));
        if ($request->filled('payment')) $query->where('payment_type', $request->input('payment'));
        return $query->paginate(20)->through(function (Order $item) {
            $customerName =
                ($item->cash_full_name
                    ?? ($item->installment_full_name
                        ?? ($item->installment_company_name
                            ?? optional($item->user)->full_name))) ?: '-';

            $customerPhone =
                ($item->cash_phone_number
                    ?? ($item->installment_phone_number
                        ?? ($item->installment_legal_representative_phone_number
                            ?? optional($item->user)->mobile))) ?: '-';

            $carInfo = trim(collect([
                optional($item->bookingCarClone)->car_brand_name,
                optional($item->bookingCarClone)->car_model_name,
                optional($item->bookingCarClone)->car_term_name ?: optional($item->term)->term_name,
            ])->filter()->implode(' - '));

            $paymentVal = $item->payment_type?->value ?? (int)$item->payment_type;
            $isCash = ((int)$paymentVal === PaymentTypeEnum::CASH);
            $paymentLabel = $isCash ? 'نقدي' : 'تقسيط';

            $customerTypeLabel = '-';
            if (!$isCash && $item->customer_type !== null) {
                $ctype = $item->customer_type?->value ?? (int)$item->customer_type;
                $customerTypeLabel = match ($ctype) {
                    ApplicableToEnum::INDIVIDUAL => 'أفراد',
                    ApplicableToEnum::COMPANY    => 'شركات',
                    ApplicableToEnum::BOTH       => 'أفراد وشركات',
                    default                       => '-',
                };
            }

            $bankName = optional($item->bank)->name ?: '-';
            $tenor    = $item->installment_duration ? ($item->installment_duration . ' شهر') : '-';
            $price       = $item->price ? number_format($item->price) . ' ج.م' : '-';
            $reservation = $item->reservation_amount ? number_format($item->reservation_amount) . ' ج.م' : '-';

            $statusVal = $item->status?->value ?? (int)$item->status;
            $statusMap = [
                OrderStatusEnum::PENDING   => ['معلّق',  'badge-warning'],
                OrderStatusEnum::FAILED    => ['فشل',     'badge-dark'],
                OrderStatusEnum::PAID      => ['مؤكّد',  'badge-success'],
                OrderStatusEnum::CANCELED  => ['مرفوض',  'badge-danger'],
            ];
            $statusLabel = $statusMap[$statusVal][0] ?? '-';
            $statusClass = $statusMap[$statusVal][1] ?? 'badge-secondary';

            $color1 = optional($item->firstColor)->name ?: '-';
            $color2 = optional($item->secondColor)->name ?: '-';

            $ref = $item->reference_number ?: '-';

            $item->setAttribute('ref', $ref);
            $item->setAttribute('customer_name', $customerName);
            $item->setAttribute('customer_phone', $customerPhone);
            $item->setAttribute('car_info', $carInfo);
            $item->setAttribute('payment_label', $paymentLabel);
            $item->setAttribute('customer_type_label', $customerTypeLabel);
            $item->setAttribute('bank_name', $bankName);
            $item->setAttribute('tenor_label', $tenor);
            $item->setAttribute('price_fmt', $price);
            $item->setAttribute('reservation_fmt', $reservation);
            $item->setAttribute('status_label', $statusLabel);
            $item->setAttribute('status_class', $statusClass);
            $item->setAttribute('color1_name', $color1);
            $item->setAttribute('color2_name', $color2);

            return $item;
        });
    }

    public function show(Order $order): array
    {
        $order->load([
            'user:id,full_name,mobile,email',
            'term:id,term_name',
            'firstColor:id,name',
            'secondColor:id,name',
            'bookingCarClone:id,order_id,car_brand_name,car_model_name,car_term_name,price,reservation_amount',
            'bank:id,name',
        ]);

        $paymentVal = $order->payment_type?->value ?? (int)$order->payment_type;
        $isCash     = ((int)$paymentVal === PaymentTypeEnum::CASH);

        $data = [
            'ref'           => $order->reference_number ?: '-',
            'created_at'    => $order->created_at,
            'updated_at'    => $order->updated_at,
            'customer_name' => ($order->display_customer_name
                ?? $order->cash_full_name
                ?? $order->installment_full_name
                ?? $order->installment_company_name
                ?? optional($order->user)->full_name) ?: '-',
            'customer_phone' => ($order->display_customer_phone
                ?? $order->cash_phone_number
                ?? $order->installment_phone_number
                ?? $order->installment_legal_representative_phone_number
                ?? optional($order->user)->mobile) ?: '-',
            'car_info'      => trim(collect([
                optional($order->bookingCarClone)->car_brand_name,
                optional($order->bookingCarClone)->car_model_name,
                optional($order->bookingCarClone)->car_term_name ?: optional($order->term)->term_name,
            ])->filter()->implode(' - ')),
            'color1'        => optional($order->firstColor)->name ?: '-',
            'color2'        => optional($order->secondColor)->name ?: '-',
            'payment_label' => $isCash ? 'نقدي' : 'تقسيط',
            'customer_type_label' => $isCash ? '-' : match ($order->customer_type?->value ?? (int)$order->customer_type) {
                ApplicableToEnum::INDIVIDUAL => 'أفراد',
                ApplicableToEnum::COMPANY    => 'شركات',
                ApplicableToEnum::BOTH       => 'أفراد وشركات',
                default                       => '-',
            },
            'bank_name'     => optional($order->bank)->name ?: '-',
            'branch_name'   => $order->branch_name ?: '-',
            'tenor'         => $order->installment_duration ? ($order->installment_duration . ' شهر') : '-',
            'price_fmt'     => $order->price ? number_format($order->price) . ' ج.م' : '-',
            'reservation_fmt' => $order->reservation_amount ? number_format($order->reservation_amount) . ' ج.م' : '-',
            'down_payment_amount_fmt'  => $order->down_payment_amount ? number_format($order->down_payment_amount) . ' ج.م' : '-',
            'down_payment_percent_fmt' => $order->down_payment_percent !== null
                ? rtrim(rtrim(number_format((float)$order->down_payment_percent, 2, '.', ''), '0'), '.') . ' %'
                : '-',
            'interest_rate_fmt'        => $order->interest_rate !== null
                ? rtrim(rtrim(number_format((float)$order->interest_rate, 2, '.', ''), '0'), '.') . ' % سنوياً'
                : '-',
            'monthly_installment_fmt'  => $order->monthly_installment_amount
                ? number_format($order->monthly_installment_amount) . ' ج.م'
                : '-',
        ];

        $statusVal = $order->status?->value ?? (int)$order->status;
        $statusMap = [
            OrderStatusEnum::PENDING   => ['معلّق', 'badge-warning'],
            OrderStatusEnum::FAILED    => ['فشل', 'badge-dark'],
            OrderStatusEnum::PAID      => ['مؤكّد', 'badge-success'],
            OrderStatusEnum::CANCELED  => ['مرفوض', 'badge-danger'],
        ];
        $data['status_label'] = $statusMap[$statusVal][0] ?? '-';
        $data['status_class'] = $statusMap[$statusVal][1] ?? 'badge-secondary';

        $uploads = [];
        if ($isCash) {
            $uploads = [
                'صورة بطاقة (الوجه)' => $order->cash_front_national_id_image,
                'صورة بطاقة (الظهر)' => $order->cash_back_national_id_image,
            ];
        } else {
            $custVal = $order->customer_type?->value ?? (int)$order->customer_type;
            if ($custVal === ApplicableToEnum::INDIVIDUAL) {
                $uploads = [
                    'صورة بطاقة (الوجه)' => $order->installment_front_national_id_image,
                    'صورة بطاقة (الظهر)' => $order->installment_back_national_id_image,
                    'كشف حساب بنكي'      => $order->installment_bank_statement,
                    'خطاب موارد بشرية'   => $order->installment_hr_letter,
                ];
            } else {
                $uploads = [
                    'السجل التجاري'        => $order->installment_commercial_registration_image,
                    'البطاقة الضريبية'     => $order->installment_tax_card_image,
                    'كشف حساب بنكي للشركة'  => $order->installment_company_bank_statement,
                ];
            }
        }

        $data['uploads'] = collect($uploads)
            ->filter()
            ->map(fn($path, $label) => [
                'label'    => $label,
                'url'      => asset('upload/orders/' . $order->id . '/' . $path),
                'is_image' => preg_match('/\.(jpe?g|png|gif|webp)$/i', (string)$path) === 1,
                'filename' => basename((string)$path),
            ])
            ->values();

        return ['order' => $order, 'data' => $data];
    }
}
