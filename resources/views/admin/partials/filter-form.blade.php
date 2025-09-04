<div class="col-12 p-3" style="background-color: #f8f9fa; margin-bottom: 20px;">
    <form method="GET" action="{{ $action }}" id="filterForm">
        @csrf
        <div class="row">
            <div class="col-md-2">
                <div class="form-group">
                    <label for="from_date">{{ __('admin.table.created_at') }}</label>
                    <input type="date" class="form-control" id="from_date" name="from_date"
                        value="{{ request()->input('from_date', $filters['from_date'] ?? '') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="to_date">{{ __('admin.table.updated_at') }}</label>
                    <input type="date" class="form-control" id="to_date" name="to_date"
                        value="{{ request()->input('to_date', $filters['to_date'] ?? '') }}">
                </div>
            </div>
            {{-- Extra filters --}}
            {!! $extraFilters ?? '' !!}
            {{-- Checkbox options --}}
            <div class="col-md-2">
                <div class="form-group">
                    @if (!empty($checkboxes))
                        @foreach ($checkboxes as $name => $label)
                            <label>&nbsp;</label>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="{{ $name }}"
                                    name="{{ $name }}" value="1"
                                    {{ request()->input($name, $filters[$name] ?? false) ? 'checked' : '' }}>
                                <label class="custom-control-label"
                                    for="{{ $name }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="col-md-12 d-flex align-items-end" style="justify-content: flex-end;">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="d-flex" style="gap: 0.5rem;">
                        <button type="submit" class="btn btn-info mr-2">
                            <i class="icon-search"></i> {{ __('admin.buttons.filter') }}
                        </button>
                        <a href="{{ $resetUrl }}" class="btn btn-secondary mr-2">
                            <i class="icon-refresh"></i> {{ __('admin.buttons.reset') }}
                        </a>
                        <a href="{{ $exportUrl }}" class="btn btn-success">
                            <i class="icon-download"></i> {{ __('admin.buttons.export_excel') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
