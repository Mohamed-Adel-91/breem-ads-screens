<div class="tab-content booking  content">

    <div class="tables ">
        <div class="container relative">
            <h2 class="booking_title">يمكنك متابعة حجزك</h2>

            <div class="overflow-x-auto w-full mb-12">
                <table class="w-full border-collapse text-center">
                    <thead>
                        <tr class="bg-black-600 text-white">
                            <th class="p-3">تاريخ الطلب</th>
                            <th class="p-3">الرقم المرجعي </th>
                            <th class="p-3"> نوع السيارات</th>
                            <th class="p-3">فئة السيارة</th>
                            <th class="p-3">المبلغ المدفوع</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse ($bookingOrders as $order)
                            <tr>
                                <td class="p-3">{{ $order->created_at?->format('d/m/Y') }}</td>
                                <td class="p-3">{{ $order->reference_number }}</td>
                                <td class="p-3">
                                    {{ $order->bookingCarClone->car_model_name ?? $order->term?->model?->name }}</td>
                                <td class="p-3">
                                    {{ $order->bookingCarClone->car_term_name ?? $order->term?->term_name }}</td>
                                <td class="p-3">{{ number_format($order->reservation_amount) }} جنيه مصري</td>
                                <td class="p-3">
                                    @if (empty($order->provider_order_reference))
                                        <a href="{{ route('web.confirm-booking', $order->id) }}"
                                            class="form-button mx-auto inline-block text-center">
                                            استكمال الدفع
                                        </a>
                                    @else
                                        <button type="button" class="form-button mx-auto request" data-tab="request"
                                            data-order-id="{{ $order->id }}"
                                            data-status-url="{{ route('web.orders.status', $order->id) }}">
                                            متابعة الطلب
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @emptyٍ
                            <tr>
                                <td colspan="6" class="p-3">لا توجد حجوزات متاحة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<div class="tab-content request  content">
    <div class="max-w-6xl relative mx-auto pt- pb-4">
        <h2 class="request_title">يمكنك متابعة سيارتك</h2>
        <!-- <button class="profileTab goBack py-2" data-tab="booking">رجوع</button> -->

        <div class="steps" id="order-status-steps"></div>
        <div id="order-status-details" class="grid lg:grid-cols-2 gap-4 overflow-hidden mt-4"></div>
        <div id="order-status-details" class="grid lg:grid-cols-2 gap-4 overflow-hidden stepDetails active">
            <!-- Car Image -->
            <div class="flex items-center justify-center bg-black img">
                <img src="{{ $firstColorImage ?? ($order->term->model->image_path ?? 'img/profile/1.png') }}"
                    alt="السيارة" class="w-full h-auto object-cover">
            </div>
            <!-- Text Content -->
            <div class="p-6 flex flex-col justify-center text">
                <div class="flex items-center justify-between mb-4">
                    <span class="font-bold flex items-center gap-2">
                        <span
                            class="bg-black text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">1</span>
                        تم حجز السيارة
                    </span>
                    <span class=" flex items-center gap-1"><span class="text-red-500">✔</span>
                        تم
                        الحجز</span>
                </div>
                <p class="text-gray-700 leading-relaxed">
                    بناءً على بيان الحجز الخاص بكم، نود أن نعلمكم أنه قد تم طلب طراز السيارة
                    الخاصة من مصنع الشركة الأم بكم على ذات المواصفات الخاصة بكم.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts-js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.request').forEach(btn => {
                btn.addEventListener('click', function() {
                    const url = this.dataset.statusUrl;
                    fetch(url)
                        .then(resp => resp.json())
                        .then(data => {
                            const stepsContainer = document.getElementById(
                                'order-status-steps');
                            const detailsContainer = document.getElementById(
                                'order-status-details');
                            stepsContainer.innerHTML = '';
                            detailsContainer.innerHTML = '';

                            const completed = data.steps.filter(s => s.completed).length;

                            data.steps.forEach((step, idx) => {
                                const stepDiv = document.createElement('div');
                                stepDiv.className =
                                    'steps_step flex flex-col items-center text-center' +
                                    (step.completed ? ' active' : '');
                                stepDiv.innerHTML =
                                    `<div class="steps_step_number">${idx + 1}</div><p>${step.title}</p>`;
                                stepsContainer.appendChild(stepDiv);
                                const DEFAULT_IMG = @json($firstColorImage ?? ($order->term->model->image_path ?? 'img/profile/1.png'));

                                const detailDiv = document.createElement('div');
                                detailDiv.className = 'stepDetails' + (idx ===
                                    completed - 1 ? ' active' : '');
                                // detailDiv.innerHTML =
                                //                 `
                            //                 <div class="flex items-center justify-center bg-black img">
                            //                     <img src="" alt="السيارة" class="w-full h-auto object-cover">
                            //                 </div>
                            //                 <div class="p-6 flex flex-col justify-center text">
                            //                     <div class="flex items-center justify-between mb-4">
                            //                         <span class="font-bold flex items-center gap-2">
                            //                             <span class="bg-black text-white rounded-full w-6 h-6 flex items-center justify-center text-sm">${idx + 1}</span>
                            //                             <span class="step-desc-strong"></span>
                            //                         </span>
                            //                         <span class="flex items-center gap-1">
                            //                             <span class="text-red-500">✔</span>
                            //                             <span class="step-desc-plain"></span>
                            //                         </span>
                            //                     </div>
                            //                     <p class="text-gray-700 leading-relaxed">
                            //                         بناءً على بيان الحجز الخاص بكم، نود أن نعلمكم أنه قد تم طلب طراز السيارة
                            //                         الخاصة من مصنع الشركة الأم بكم على ذات المواصفات الخاصة بكم.
                            //                     </p>
                            //                 </div>
                            //                 `;
                                detailsContainer.appendChild(detailDiv);
                            });
                        });
                });
            });
        });
    </script>
@endpush
