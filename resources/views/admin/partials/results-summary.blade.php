<!-- Results Summary -->
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info">
            <strong>{{ __('admin.results_summary.heading') }}</strong> {{ $data->total() }} {{ $label ?? __('admin.results_summary.records') }} {{ __('admin.results_summary.available') }}
            @if (collect(request()->except('page'))->filter()->isNotEmpty())
                {{ __('admin.results_summary.filtered') }}
            @endif
        </div>
    </div>
</div>
