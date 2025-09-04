<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\KashierService;
use App\Enums\OrderStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function success(Request $request)
    {
        $kashierService = new KashierService();
        $merchantOrderId = $request->get('merchantOrderId');
        $transactionId = $request->get('transactionId');
        $paymentStatus = $request->get('paymentStatus');
        $orderId = $request->get('orderId');

        // to be removed later
        Log::info('Payment Success Redirect', [
            'merchantOrderId' => $merchantOrderId,
            'transactionId' => $transactionId,
            'paymentStatus' => $paymentStatus,
            'kashier_orderId' => $orderId,
            'all_params' => $request->all()
        ]);

        // Validate signature first
        if (!$kashierService->validateSignature($request->all())) {
            return redirect()->route('web.payment.failed')->with('error', 'توقيع الدفع غير صحيح');
        }

        $order = Order::where('reference_number', $merchantOrderId)->first();
        if (!$order) {
            return redirect()->route('web.payment.failed')->with('error', 'الطلب غير موجود');
        }

        if ($paymentStatus === 'SUCCESS' && $order->status !== OrderStatusEnum::PAID) {
            $order->update([
                'status' => OrderStatusEnum::PAID,
                'provider_order_reference' => $orderId, // Kashier internal order ID
                'provider_transaction_reference' => $transactionId, // Kashier transaction ID
            ]);
        } elseif ($paymentStatus !== 'SUCCESS') {
            return redirect()->route('web.payment.failed')->with('error', 'فشل في عملية الدفع');
        }
        return redirect()->route('web.thanks', ['order' => $order->id])
            ->with('success', 'تم الدفع بنجاح');
    }

    public function failed(Request $request)
    {
        $kashierService = new KashierService();
        $merchantOrderId = $request->get('merchantOrderId');
        $transactionId = $request->get('transactionId');
        $paymentStatus = $request->get('paymentStatus');
        $orderId = $request->get('orderId');

        Log::info('Payment Failed Redirect', [
            'merchantOrderId' => $merchantOrderId,
            'transactionId' => $transactionId,
            'paymentStatus' => $paymentStatus,
            'kashier_orderId' => $orderId,
            'all_params' => $request->all()
        ]);

        if (!$kashierService->validateSignature($request->all())) {
            Log::warning('Invalid signature in payment failed redirect', [
                'merchantOrderId' => $merchantOrderId,
                'params' => $request->all()
            ]);
        }

        $order = null;
        if ($merchantOrderId) {
            $order = Order::find($merchantOrderId);
        }

        if (!$order && $orderId) {
            $order = Order::where('provider_order_reference', $orderId)->first();
        }

        if (!$order) {
            Log::error('Order not found in failed redirect', [
                'merchantOrderId' => $merchantOrderId,
                'kashier_orderId' => $orderId
            ]);
            return view('web.pages.order.payment-failed', [
                'order' => null,
                'message' => 'فشل في عملية الدفع. الطلب غير موجود.'
            ]);
        }

        if ($order->status !== OrderStatusEnum::FAILED && $order->status !== OrderStatusEnum::PAID) {
            $updateData = [
                'status' => OrderStatusEnum::FAILED
            ];
            if ($orderId && !$order->provider_order_reference) {
                $updateData['provider_order_reference'] = $orderId;
            }
            if ($transactionId && !$order->transaction_reference) {
                $updateData['transaction_reference'] = $transactionId;
            }
            $order->update($updateData);
        }

        return view('web.pages.order.payment-failed', [
            'order' => $order,
            'message' => 'فشل في عملية الدفع. يرجى المحاولة مرة أخرى.'
        ]);
    }

    public function webhook(Request $request)
    {
        //TODO: M.Adel to implement webhook processing logic
        return response()->json([
            'status' => 'success',
            'message' => 'Webhook processed successfully'
        ]);
    }
}
