@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST"
                    action="{{ isset($data) ? route('admin.installment_programs.update', $data->id) : route('admin.installment_programs.store') }}"
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
                                        {{ isset($data) ? 'تعديل برنامج التقسيط' : 'إنشاء برنامج التقسيط' }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="bank_id">البنك</label>
                                                <select id="bank_id" name="bank_id" class="form-control">
                                                    <option value="" disabled
                                                        {{ old('bank_id', $data->bank_id ?? '') ? '' : 'selected' }}>اختر
                                                        البنك</option>
                                                    @foreach ($banks as $bank)
                                                        <option value="{{ $bank->id }}"
                                                            {{ (string) old('bank_id', $data->bank_id ?? '') === (string) $bank->id ? 'selected' : '' }}>
                                                            {{ $bank->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('bank_id')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">اسم البرنامج</label>
                                                <input type="text" id="name" name="name" class="form-control"
                                                    value="{{ old('name', $data->name ?? '') }}">
                                                @error('name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="interest_rate_per_year">نسبة الفائدة السنوية</label>
                                                <input type="number" step="0.01" id="interest_rate_per_year"
                                                    name="interest_rate_per_year" class="form-control"
                                                    value="{{ old('interest_rate_per_year', $data->interest_rate_per_year ?? '') }}">
                                                @error('interest_rate_per_year')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="applicable_to">مناسب لـ</label>
                                                <select id="applicable_to" name="applicable_to" class="form-control">
                                                    <option value="" disabled
                                                        {{ old('applicable_to', $data->applicable_to ?? '') ? '' : 'selected' }}>
                                                        اختر النوع</option>
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
                                        <div class="col-md-4">
                                            @include('admin.layouts.components.media-upload', [
                                                'label' => 'صورة الكارد في الصفحة الرئيسية',
                                                'name' => 'card_image',
                                                'oldFile' => $data->card_image ?? null,
                                                'previewPath' => $data->card_image_path ?? null,
                                            ])
                                            @error('card_image')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="description">الوصف</label>
                                                <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $data->description ?? '') }}</textarea>
                                                @error('description')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group" id="features-wrapper">
                                                <label>الميزات</label>

                                                <div id="features-list">
                                                    @foreach ($featuresForForm as $i => $feature)
                                                        <div class="row feature-item align-items-center mb-2">
                                                            <div class="col-md-5">
                                                                <input type="text"
                                                                    name="features[{{ $i }}][name]"
                                                                    class="form-control" placeholder="العنصر (مثال: اقصي مدة للتقسيط)"
                                                                    value="{{ $feature['name'] }}">
                                                            </div>
                                                            <div class="col-md-5">
                                                                <input type="text"
                                                                    name="features[{{ $i }}][value]"
                                                                    class="form-control"
                                                                    placeholder="القيمة (مثال: حتى 60 شهر)"
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
                            <input type="text" name="features[${idx}][name]" class="form-control" placeholder="العنصر (مثال: المدة المتاحة)">
                        </div>
                        <div class="col-md-5">
                            <input type="text" name="features[${idx}][value]" class="form-control" placeholder="القيمة (مثال: حتى 60 شهر)">
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
