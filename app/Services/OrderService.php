<?php

namespace App\Services;

use App\Models\Order;
use App\Models\BookingCarClone;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentTypeEnum;
use App\Enums\ApplicableToEnum;
use Illuminate\Support\Facades\Auth;

class OrderService
{
    public function createFromRequest(array $data, callable $uploader): Order
    {
        $userId = Auth::guard('user')->id() ?? Auth::id();
        $isCash = (int)$data['payment_type'] === PaymentTypeEnum::CASH;

        $payload = [
            'user_id'              => $userId,
            'car_term_id'          => (int)$data['car_term_id'],
            'first_color_id'       => $data['first_color_id'] ?? null,
            'second_color_id'      => $data['second_color_id'] ?? null,
            'payment_type'         => (int)$data['payment_type'],
            'branch_name'          => $data['branch_name'],
            'price'                => (int)$data['price'],
            'reservation_amount'   => (int)$data['reservation_amount'],
            'down_payment_percent' => $data['down_payment_percent'] ?? null,
            'down_payment_amount'  => $data['down_payment_amount'] ?? null,
            'bank_id'              => $data['bank_id'] ?? null,
            'installment_duration' => $data['installment_duration'] ?? null,
            'agreed_terms'         => (bool)($data['agreed_terms'] ?? false),
            'status'               => OrderStatusEnum::PENDING,
        ];

        if ($isCash) {
            $payload['customer_type'] = ApplicableToEnum::INDIVIDUAL;
            $payload += [
                'cash_full_name'        => $data['cash_full_name'],
                'cash_phone_number'     => $data['cash_phone_number'],
                'cash_individual_email' => $data['cash_individual_email'] ?? null,
                'cash_national_id'      => $data['cash_national_id'],
            ];
        } else {
            $payload['customer_type'] = (int)$data['customer_type'];
            if ((int)$data['customer_type'] === ApplicableToEnum::INDIVIDUAL) {
                $payload += [
                    'installment_full_name'        => $data['installment_full_name'],
                    'installment_phone_number'     => $data['installment_phone_number'],
                    'installment_individual_email' => $data['installment_individual_email'] ?? null,
                    'installment_national_id'      => $data['installment_national_id'],
                ];
            } else {
                $payload += [
                    'installment_company_name'                      => $data['installment_company_name'],
                    'installment_legal_representative_phone_number' => $data['installment_legal_representative_phone_number'],
                    'installment_company_email'                     => $data['installment_company_email'] ?? null,
                    'installment_commercial_registration_number'    => $data['installment_commercial_registration_number'],
                    'installment_tax_card_number'                   => $data['installment_tax_card_number'],
                ];
            }
        }

        $order = Order::create($payload);

        [$files, $attrs] = $this->collectFiles($data);
        if (!empty(array_filter($files))) {
            $folder = 'upload/orders/' . $order->id;
            $uploaded = $uploader(
                $files,
                array_fill(0, count($files), $folder),
                $attrs,
                $order
            );
            foreach ($attrs as $i => $attr) {
                if (!empty($uploaded[$i])) {
                    $order->{$attr} = $uploaded[$i];
                }
            }
            $order->save();
        }

        $clone = BookingCarClone::findOrFail((int)$data['booking_car_clone_id']);
        $clone->order_id = $order->id;
        $clone->save();

        return $order;
    }

    protected function collectFiles(array $data): array
    {
        $isCash = (int)$data['payment_type'] === PaymentTypeEnum::CASH;

        if ($isCash) {
            $files = [
                $data['cash_front_national_id_image'] ?? null,
                $data['cash_back_national_id_image'] ?? null,
            ];
            $attrs = ['cash_front_national_id_image', 'cash_back_national_id_image'];
            return [$files, $attrs];
        }

        if ((int)$data['customer_type'] === ApplicableToEnum::INDIVIDUAL) {
            $files = [
                $data['installment_front_national_id_image'] ?? null,
                $data['installment_back_national_id_image'] ?? null,
                $data['installment_bank_statement'] ?? null,
                $data['installment_hr_letter'] ?? null,
            ];
            $attrs = [
                'installment_front_national_id_image',
                'installment_back_national_id_image',
                'installment_bank_statement',
                'installment_hr_letter',
            ];
            return [$files, $attrs];
        }

        $files = [
            $data['installment_commercial_registration_image'] ?? null,
            $data['installment_tax_card_image'] ?? null,
            $data['installment_company_bank_statement'] ?? null,
        ];
        $attrs = [
            'installment_commercial_registration_image',
            'installment_tax_card_image',
            'installment_company_bank_statement',
        ];
        return [$files, $attrs];
    }
}
