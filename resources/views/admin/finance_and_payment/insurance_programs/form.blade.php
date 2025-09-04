@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST"
                    action="{{ isset($data) ? route('admin.insurances.update', $data->id) : route('admin.insurances.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    @if (isset($data))
                        @method('PUT')
                    @endif
                    <div class="row gutters">
                        <div class="col-12">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="card-title">
                                        {{ isset($data) ? 'تعديل برنامج التأمين' : 'إنشاء برنامج التأمين' }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="insurance_company">شركة التأمين</label>
                                                <input type="text" id="insurance_company" name="insurance_company"
                                                    class="form-control"
                                                    value="{{ old('insurance_company', $data->insurance_company ?? '') }}">
                                                @error('insurance_company')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="program_name">اسم البرنامج</label>
                                                <input type="text" id="program_name" name="program_name"
                                                    class="form-control"
                                                    value="{{ old('program_name', $data->program_name ?? '') }}">
                                                @error('program_name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="coverage_rate">نسبة التغطية</label>
                                                <input type="number" step="0.01" id="coverage_rate" name="coverage_rate"
                                                    class="form-control"
                                                    value="{{ old('coverage_rate', $data->coverage_rate ?? '') }}">
                                                @error('coverage_rate')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="annual_price">السعر السنوي</label>
                                                <input type="number" step="0.01" id="annual_price" name="annual_price"
                                                    class="form-control"
                                                    value="{{ old('annual_price', $data->annual_price ?? '') }}">
                                                @error('annual_price')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="monthly_payment">القسط الشهري</label>
                                                <input type="number" step="0.01" id="monthly_payment"
                                                    name="monthly_payment" class="form-control"
                                                    value="{{ old('monthly_payment', $data->monthly_payment ?? '') }}">
                                                @error('monthly_payment')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="applicable_to">مناسب لـ</label>
                                                <select id="applicable_to" name="applicable_to" class="form-control">
                                                    @foreach (\App\Enums\ApplicableToEnum::asArrayWithDescriptions() as $key => $label)
                                                        <option value="{{ $key }}"
                                                            {{ (string) old('applicable_to', $data->applicable_to ?? '') === (string) $key ? 'selected' : '' }}>
                                                            {{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('applicable_to')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        @include('admin.layouts.components.media-upload', [
                                            'label' => __('admin.forms.logo'),
                                            'name' => 'company_logo',
                                            'oldFile' => $data->company_logo ?? null,
                                            'previewPath' => $data->company_logo_path ?? null,
                                        ])
                                        <div class="col-md-12">
                                            <div class="form-group" id="features-wrapper">
                                                <label>الميزات</label>

                                                <div id="features-list">
                                                    @foreach ($featuresForForm as $i => $feature)
                                                        <div class="row feature-item align-items-center mb-2">
                                                            <div class="col-md-5">
                                                                <input type="text"
                                                                    name="features[{{ $i }}][name]"
                                                                    class="form-control" placeholder="العنصر (مثال: اكبر شبكة تغطيه)"
                                                                    value="{{ $feature['name'] }}">
                                                            </div>
                                                            <div class="col-md-5">
                                                                <input type="text"
                                                                    name="features[{{ $i }}][value]"
                                                                    class="form-control"
                                                                    placeholder="القيمة (مثال: حتى 95%)"
                                                                    value="{{ $feature['value'] }}">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button type="button"
                                                                    class="btn btn-danger remove-feature">&times;</button>
                                                            </div>
                                                        </div>
                                                        @error('features.' . $i . '.name')
                                                            <span class="text-danger d-block">{{ $message }}</span>
                                                        @enderror
                                                        @error('features.' . $i . '.value')
                                                            <span class="text-danger d-block">{{ $message }}</span>
                                                        @enderror
                                                    @endforeach
                                                </div>


                                                <button type="button" id="add-feature" class="btn btn-secondary mt-2">إضافة
                                                    ميزة</button>
                                                @error('features')
                                                    <span class="text-danger d-block">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit"
                                        class="btn btn-primary">{{ __('admin.forms.save_button') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('custom-js-scripts')
    <script>
        const list = document.getElementById('features-list');
        let featureIndex = list.querySelectorAll('.feature-item').length;
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'add-feature') {
                const idx = featureIndex++;
                const row = document.createElement('div');
                row.className = 'row feature-item align-items-center mb-2';
                row.innerHTML = `
                        <div class="col-md-5">
                            <input type="text" name="features[${idx}][name]" class="form-control" placeholder="العنصر (مثال: اكبر شبكة تغطيه )">
                        </div>
                        <div class="col-md-5">
                            <input type="text" name="features[${idx}][value]" class="form-control" placeholder="القيمة (مثال: حتى 95%)">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-feature">&times;</button>
                        </div>`;
                list.appendChild(row);
            }

            if (e.target && e.target.classList.contains('remove-feature')) {
                e.target.closest('.feature-item').remove();
            }
        });
    </script>
@endpush
