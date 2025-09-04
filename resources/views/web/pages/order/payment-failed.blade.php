@extends('web.layouts.master')

@push('meta')
    <title>فشل في الدفع | التوكيل</title>
    <meta name="description" content="فشل في عملية الدفع" />
@endpush

@section('content')
    <main>
        <section class="custom-section payment_failed">
            <div class="container">
                <div class="text-center py-20">
                    <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-8">
                        <div class="mb-6">
                            <svg class="w-16 h-16 text-red-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>

                        <h2 class="text-2xl font-bold text-gray-800 mb-4">فشل في عملية الدفع</h2>

                        <p class="text-gray-600 mb-6">
                            {{ $message ?? 'عذراً، لم تتم عملية الدفع بنجاح. يرجى المحاولة مرة أخرى.' }}
                        </p>

                        @if(isset($order))
                            <p class="text-sm text-gray-500 mb-6">
                                رقم المرجع: {{ $order->reference_number }}
                            </p>
                        @endif

                        <div class="space-y-3">
                            @if(isset($order))
                                <a href="{{ route('web.confirm-booking', $order) }}"
                                   class="inline-block w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200">
                                    إعادة المحاولة
                                </a>
                            @endif

                            <a href="{{ route('web.home') }}"
                               class="inline-block w-full bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition duration-200">
                                العودة للصفحة الرئيسية
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
