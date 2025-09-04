<!-- Results Summary -->
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info">
            <strong>النتائج: </strong> {{ $data->total() }} {{ $label ?? 'سجلات' }} موجوده
            @if (collect(request()->except('page'))->filter()->isNotEmpty())
                (تمت تصفيتها)
            @endif
        </div>
    </div>
</div>
