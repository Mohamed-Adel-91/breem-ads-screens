@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST"
                    action="{{ isset($data) ? route('admin.car_terms.update', $data->id) : route('admin.car_terms.store') }}"
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
                                        {{ isset($data) ? __('admin.forms.edit') : __('admin.forms.create') }}
                                        {{ __('admin.forms.car_term') }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="car_model_id">{{ __('admin.forms.model') }}</label>
                                                <select class="form-control" id="car_model_id" name="car_model_id">
                                                    <option value="">{{ __('admin.forms.choose_model') }}</option>
                                                    @foreach ($models as $model)
                                                        <option value="{{ $model->id }}"
                                                            {{ old('car_model_id', $data->car_model_id ?? '') == $model->id ? 'selected' : '' }}>
                                                            {{ $model->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('car_model_id')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="term_name">{{ __('admin.forms.term_name') }}</label>
                                                <input type="text" class="form-control" id="term_name" name="term_name"
                                                    value="{{ old('term_name', $data->term_name ?? '') }}">
                                                @error('term_name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="price">{{ __('admin.forms.price') }}</label>
                                                <input type="number" step="any" class="form-control" id="price"
                                                    name="price" value="{{ old('price', $data->price ?? '') }}">
                                                @error('price')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="inventory">{{ __('admin.forms.inventory') }}</label>
                                                <input type="number" class="form-control" id="inventory" name="inventory"
                                                    value="{{ old('inventory', $data->inventory ?? '') }}">
                                                @error('inventory')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label
                                                    for="reservation_amount">{{ __('admin.forms.reservation_amount') }}</label>
                                                <input type="number" step="any" class="form-control"
                                                    id="reservation_amount" name="reservation_amount"
                                                    value="{{ old('reservation_amount', $data->reservation_amount ?? '') }}">
                                                @error('reservation_amount')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="form-group">
                                            <label for="features">{{ __('admin.sidebar.features') }}</label>
                                            <select class="form-control" id="features" name="features[]"
                                                multiple="multiple">
                                                @foreach ($features as $feature)
                                                    <option value="{{ $feature->id }}"
                                                        {{ in_array($feature->id, old('features', isset($data) ? $data->features->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                                                        {{ $feature->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="row gutters" id="features-wrapper">
                                            @foreach ($features as $feature)
                                                @php
                                                    $pivot = isset($data) ? $data->features->find($feature->id) : null;
                                                    $pivotValue = $pivot && $pivot->pivot ? $pivot->pivot->value : null;
                                                    $pivotPriority =
                                                        $pivot && $pivot->pivot ? $pivot->pivot->priority : null;
                                                    $pivotStatus =
                                                        $pivot && $pivot->pivot ? $pivot->pivot->status : false;
                                                @endphp
                                                <div class="col-md-12 feature-input mb-4"
                                                    data-feature="{{ $feature->id }}" style="display: none;">
                                                    <h5>{{ $feature->name }}</h5>
                                                    <div class="row gutters">
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>{{ __('admin.forms.details') }}</label>
                                                                <input type="text" class="form-control"
                                                                    name="feature_values[{{ $feature->id }}]"
                                                                    placeholder="في حالة عدم وجود تفاصيل خاصه بالميزة اتركه فارغا"
                                                                    value="{{ old('feature_values.' . $feature->id, $pivotValue) }}">
                                                            </div>
                                                        </div>
                                                        {{-- <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label>{{ __('admin.forms.priority') }}</label>
                                                                <input type="number" class="form-control"
                                                                    name="feature_priorities[{{ $feature->id }}]"
                                                                    value="{{ old('feature_priorities.' . $feature->id, $pivotPriority) }}">
                                                            </div>
                                                        </div> --}}
                                                        <div class="col-md-4 d-flex align-items-center">
                                                            <div class="form-group mt-4">
                                                                <div class="custom-control custom-checkbox">
                                                                    <input type="checkbox" class="custom-control-input"
                                                                        id="feature_status_{{ $feature->id }}"
                                                                        name="feature_statuses[{{ $feature->id }}]"
                                                                        value="1"
                                                                        {{ old('feature_statuses.' . $feature->id, $pivotStatus) ? 'checked' : '' }}>
                                                                    <label class="custom-control-label"
                                                                        for="feature_status_{{ $feature->id }}">{{ __('admin.forms.feature_case') }}</label>
                                                                    <small>- في حالة الاختيار بدون كتابه تفاصيل يظهر علامة
                                                                        صح فقط في المربع الخاص بالميزه في جدول
                                                                        المقارنات</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>{{ __('admin.forms.specs') }}</label>
                                                <div id="specs-wrapper">
                                                    @php
                                                        $oldSpecs = old(
                                                            'specs',
                                                            isset($data)
                                                                ? $data->specs->pluck('value', 'id')->toArray()
                                                                : [],
                                                        );
                                                    @endphp
                                                    @foreach ($oldSpecs as $id => $val)
                                                        <div class="input-group mb-2 spec-row"
                                                            data-id="{{ is_int($id) ? $id : '' }}">
                                                            <input type="text" class="form-control"
                                                                name="specs[{{ is_int($id) ? $id : 'new_' . $loop->index }}]"
                                                                placeholder="" value="{{ $val }}">
                                                            <button type="button"
                                                                class="btn btn-outline-danger remove-spec"><i
                                                                    class="icon-cancel"></i></button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <button type="button" class="btn btn-sm btn-secondary mt-2"
                                                    id="add-spec"><i class="icon-plus-circle"></i>
                                                    {{ __('admin.forms.add_spec') }}</button>
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
        document.addEventListener('DOMContentLoaded', function() {
            let index = {{ isset($data) ? $data->specs->count() : 0 }};
            const wrapper = document.getElementById('specs-wrapper');
            document.getElementById('add-spec').addEventListener('click', function() {
                const row = document.createElement('div');
                row.classList.add('input-group', 'mb-2', 'spec-row');
                row.innerHTML = `<input type="text" class="form-control" name="specs[new_${index}]">` +
                    `<button type="button" class="btn btn-outline-danger remove-spec"><i class="icon-cancel"></i></button>`;
                wrapper.appendChild(row);
                index++;
            });
            wrapper.addEventListener('click', function(e) {
                const target = e.target.closest('.remove-spec');
                if (target) {
                    const row = target.closest('.spec-row');
                    const id = row.dataset.id;
                    if (id) {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'delete_specs[]';
                        hidden.value = id;
                        wrapper.appendChild(hidden);
                    }
                    row.remove();
                }
            });

            const featureSelect = document.getElementById('features');
            const featuresWrapper = document.getElementById('features-wrapper');

            function toggleFeatureInputs() {
                const selected = Array.from(featureSelect.selectedOptions).map(o => o.value);
                featuresWrapper.querySelectorAll('.feature-input').forEach(div => {
                    if (selected.includes(div.dataset.feature)) {
                        div.style.display = 'block';
                    } else {
                        div.style.display = 'none';
                    }
                });
            }

            $('#features').multiselect({
                onChange: toggleFeatureInputs
            });
            toggleFeatureInputs();
        });
    </script>
@endpush
