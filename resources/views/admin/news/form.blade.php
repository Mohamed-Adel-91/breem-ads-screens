@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST"
                    action="{{ isset($data) ? route('admin.news.update', $data->id) : route('admin.news.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    @if (isset($data))
                        @method('PUT')
                    @endif
                    <div class="row gutters">
                        <div class="col-12">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="card-title">{{ isset($data) ? 'تعديل خبر' : 'إنشاء خبر' }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="writer_id">الكاتب</label>
                                                <select name="writer_id" id="writer_id" class="form-control">
                                                    <option value="">-- اختر كاتب --</option>
                                                    @foreach ($writers as $writer)
                                                        <option value="{{ $writer->id }}"
                                                            {{ old('writer_id', $data->writer_id ?? '') == $writer->id ? 'selected' : '' }}>
                                                            {{ $writer->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('writer_id')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="title">العنوان</label>
                                                <input type="text" id="title" name="title" class="form-control"
                                                    value="{{ old('title', $data->title ?? '') }}">
                                                @error('title')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="short_desc">الوصف المختصر</label>
                                                <textarea id="short_desc" name="short_desc" class="form-control" rows="3">{{ old('short_desc', $data->short_desc ?? '') }}</textarea>
                                                @error('short_desc')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="details">التفاصيل</label>
                                                <textarea id="details" name="details" class="form-control summernote" data-maxlength="10000" rows="8">{!! old('details', $data->details ?? '') !!}</textarea>
                                                @error('details')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="added_date">تاريخ الإضافة</label>
                                                <input type="date" id="added_date" name="added_date" class="form-control"
                                                    value="{{ old('added_date', $data->added_date ?? '') }}">
                                                @error('added_date')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="scheduale_date">تاريخ النشر</label>
                                                <input type="date" id="scheduale_date" name="scheduale_date"
                                                    class="form-control"
                                                    value="{{ old('scheduale_date', $data->scheduale_date ?? '') }}">
                                                @error('scheduale_date')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="related_tags">الوسوم</label>
                                                <input type="text" id="related_tags" name="related_tags"
                                                    class="form-control"
                                                    value="{{ old('related_tags', $data->related_tags ?? '') }}">
                                                @error('related_tags')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        @include('admin.layouts.components.media-upload', [
                                            'label' => 'صورة الرئيسية',
                                            'name' => 'home_img',
                                            'oldFile' => $data->home_img ?? null,
                                            'previewPath' =>
                                                isset($data) && $data->home_img
                                                    ? ($data->is_old
                                                        ? $data->home_img
                                                        : $data->home_img_path)
                                                    : null,
                                        ])
                                        @include('admin.layouts.components.media-upload', [
                                            'label' => 'صورة مصغرة',
                                            'name' => 'thumb_img',
                                            'oldFile' => $data->thumb_img ?? null,
                                            'previewPath' =>
                                                isset($data) && $data->thumb_img
                                                    ? ($data->is_old
                                                        ? $data->thumb_img
                                                        : $data->thumb_img_path)
                                                    : null,
                                        ])
                                        <div class="form-group">
                                            <label for="number_of_reads">عدد مرات القراءة</label>
                                            <input type="number" name="number_of_reads" id="number_of_reads"
                                                value="{{ old('number_of_reads', $data->number_of_reads ?? 0) }}"
                                                class="form-control" {{ isset($data) ? 'readonly' : '' }}>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="home">عرض في الرئيسية</label>
                                                <select id="home" name="home" class="form-control">
                                                    <option value="1"
                                                        {{ old('home', $data->home ?? '') == 1 ? 'selected' : '' }}>نعم
                                                    </option>
                                                    <option value="0"
                                                        {{ old('home', $data->home ?? '') == 0 ? 'selected' : '' }}>لا
                                                    </option>
                                                </select>
                                                @error('home')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="altText">Alt Text</label>
                                                <input type="text" id="altText" name="altText" class="form-control"
                                                    value="{{ old('altText', $data->altText ?? '') }}">
                                                @error('altText')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="hidden">مخفي</label>
                                                <select id="hidden" name="hidden" class="form-control">
                                                    <option value="0"
                                                        {{ old('hidden', $data->hidden ?? 0) == 0 ? 'selected' : '' }}>لا
                                                    </option>
                                                    <option value="1"
                                                        {{ old('hidden', $data->hidden ?? 0) == 1 ? 'selected' : '' }}>نعم
                                                    </option>
                                                </select>
                                                @error('hidden')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="is_old">خبر قديم</label>
                                                <select id="is_old" name="is_old" class="form-control">
                                                    <option value="0"
                                                        {{ old('is_old', $data->is_old ?? 0) == 0 ? 'selected' : '' }}>لا
                                                    </option>
                                                    <option value="1"
                                                        {{ old('is_old', $data->is_old ?? 0) == 1 ? 'selected' : '' }}>نعم
                                                    </option>
                                                </select>
                                                @error('is_old')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">حفظ</button>
                                    </div>
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
        $(document).ready(function() {
            new tempusDominus.TempusDominus(document.getElementById('added_date'), {
                localization: {
                    format: 'yyyy-MM-dd'
                }
            });
            new tempusDominus.TempusDominus(document.getElementById('scheduale_date'), {
                localization: {
                    format: 'yyyy-MM-dd'
                }
            });
            $('#related_tags').tagsinput();
            $('#details').summernote();
        });
    </script>
@endpush
