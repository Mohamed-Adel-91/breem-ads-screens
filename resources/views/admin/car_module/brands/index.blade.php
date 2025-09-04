@extends('admin.layouts.master')

@push('custom-css-scripts')
    <style>
        .form-check-input:not(:checked) {
            background-color: #d03b37;
            border-color: #d03b37;
        }

        .form-check-input:checked {
            background-color: #28a745;
            border-color: #28a745;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <div class="row gutters">
                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                        <div class="table-container">
                            @include('admin.partials.filter-form', [
                                'action' => route('admin.brands.index'),
                                'resetUrl' => route('admin.brands.index'),
                                'exportUrl' => '',
                                'filters' => $filters,
                                'extraFilters' => '',
                            ])
                            <div class="col-mb-12 p-0" style="margin: 15px;">
                                <div class="row d-flex justify-content-end p-0">
                                    <div class="col-md-2 d-flex justify-content-end p-0">
                                        <div class="col-md-6 d-flex justify-content-end  p-0">
                                            <button type="text" class="btn btn-primary" style="margin-top: 20px;">
                                                <i class="icon-plus-circle mr-1"></i>
                                                <a href="{{ route('admin.brands.create') }}" style="color: #fff;">New</a>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @include('admin.partials.results-summary', [
                                'data' => $data,
                                'label' => 'brand(s)',
                            ])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الاسم</th>
                                            <th>الشعار</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>تاريخ التحديث</th>
                                            <th>الحالة</th>
                                            <th>الخيارات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($data) && count($data) > 0)
                                            @foreach ($data as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $item->name ?? '-' }}</td>
                                                    <td>
                                                        @if ($item->logo)
                                                            @include(
                                                                'admin.layouts.components.image-cell',
                                                                [
                                                                    'src' => asset($item->logo_path),
                                                                    'dataSrc' => asset($item->logo_path),
                                                                ]
                                                            )
                                                        @endif
                                                    </td>

                                                    <td>{{ $item->created_at ?? '-' }}</td>
                                                    <td>{{ $item->updated_at ?? '-' }}</td>
                                                    <td>
                                                        <div class="form-check form-switch">
                                                            <span class="badge {{ $item->show_status ? 'badge-success' : 'badge-danger' }}">
                                                                {{ $item->show_status ? 'معروض علي الموقع' : 'غير معروض' }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="td-actions">
                                                            <a href="{{ route('admin.brands.edit', $item->id) }}"
                                                                class="icon bg-info" data-toggle="tooltip"
                                                                data-placement="top" title="Edit Row">
                                                                <i class="icon-edit"></i>
                                                            </a>
                                                            <form method="POST" id="delete_form_{{ $item->id }}"
                                                                class="d-inline delete_form"
                                                                action="{{ route('admin.brands.destroy', ['brand' => $item->id]) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="icon red"
                                                                    data-toggle="tooltip" data-placement="top"
                                                                    title="Delete Row"
                                                                    onclick="checker(event, {{ $item->id }})">
                                                                    <i class="icon-cancel"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>

                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="11" class="text-center">
                                                    <div class="alert alert-danger">
                                                        لا توجد سجلات مطابقة للمعايير.
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            @include('admin.partials.pagination')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('custom-js-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.status-toggle').forEach(function(el) {
                el.addEventListener('change', function() {
                    const id = this.dataset.id;
                    axios.post('/dashboard/brands/' + id + '/toggle-status')
                        .then(function(response) {
                            const newStatus = response.data.show_status ? 'نشط' : 'غير نشط';
                            Swal.fire('Success', 'Status updated', 'success',
                                confirmButtonColor: '#d03b37').then(() => {
                                el.nextElementSibling.textContent = newStatus;
                            });
                        })
                        .catch(() => {
                            el.checked = !el.checked;
                            Swal.fire('Error', 'Unable to update status', 'error',
                                confirmButtonColor: '#d03b37');
                        });
                });
            });
        });
    </script>
@endpush
