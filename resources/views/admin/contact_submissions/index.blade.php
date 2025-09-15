@extends('admin.layouts.master')
@section('content')
<div class="page-wrapper">
    @include('admin.layouts.sidebar')
    <div class="page-content">
        @include('admin.layouts.page-header', ['pageName' => __('admin.contact_submissions.title')])
        <div class="main-container">
            @include('admin.layouts.alerts')
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('admin.contact_submissions.table.id') }}</th>
                                <th>{{ __('admin.contact_submissions.table.type') }}</th>
                                <th>{{ __('admin.contact_submissions.table.name') }}</th>
                                <th>{{ __('admin.contact_submissions.table.phone') }}</th>
                                <th>{{ __('admin.contact_submissions.table.email') }}</th>
                                <th>{{ __('admin.contact_submissions.table.created_at') }}</th>
                                <th>{{ __('admin.contact_submissions.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($data as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td><span class="badge bg-primary">{{ $item->type }}</span></td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->phone }}</td>
                                <td>{{ $item->email }}</td>
                                <td>{{ $item->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-info" onclick="showPayload({{ $item->id }})">{{ __('admin.contact_submissions.actions.view') }}</button>
                                        <form id="delete_form_{{ $item->id }}" action="{{ route('admin.contact_submissions.destroy', ['lang'=>app()->getLocale(),'submission'=>$item->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" onclick="checker(event, '{{ $item->id }}')">{{ __('admin.contact_submissions.actions.delete') }}</button>
                                        </form>
                                    </div>
                                    <script type="application/json" id="payload_{{ $item->id }}">@json($item->payload)</script>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">{{ __('admin.contact_submissions.messages.empty') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @include('admin.partials.pagination', ['data' => $data])
            </div>
        </div>
    </div>
</div>
@endsection

@push('custom-js-scripts')
<script>
function showPayload(id){
    try{
        const el = document.getElementById('payload_'+id);
        const json = JSON.parse(el.textContent || el.innerText || '{}');
        // Escape HTML to avoid XSS in values
        const escapeHtml = (value) => String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/`/g, '&#96;');

        // Flatten nested objects/arrays into dotted keys
        const flatten = (obj, prefix = '') => {
            const rows = [];
            const isObject = (v) => Object.prototype.toString.call(v) === '[object Object]';
            const isArray = Array.isArray;
            const add = (k, v) => rows.push([k, v]);

            const walk = (val, keyPath) => {
                if (isObject(val)) {
                    const keys = Object.keys(val);
                    if (keys.length === 0) add(keyPath, '{}');
                    keys.forEach(k => walk(val[k], keyPath ? keyPath + '.' + k : k));
                } else if (isArray(val)) {
                    if (val.length === 0) add(keyPath, '[]');
                    val.forEach((v, i) => walk(v, keyPath ? `${keyPath}[${i}]` : `[${i}]`));
                } else {
                    add(keyPath || '(root)', val == null ? '' : val);
                }
            };

            walk(obj, prefix);
            return rows;
        };

        const rows = flatten(json);
        let fieldLabel = @js(__('admin.contact_submissions.table.field'));
        let valueLabel = @js(__('admin.contact_submissions.table.value'));
        const missingPrefix = 'admin.contact_submissions.table.';
        if (typeof fieldLabel === 'string' && fieldLabel.indexOf(missingPrefix) === 0) fieldLabel = 'Field';
        if (typeof valueLabel === 'string' && valueLabel.indexOf(missingPrefix) === 0) valueLabel = 'Value';
        let table = '<div class="table-responsive">';
        table += '<table class="table table-sm table-striped table-bordered align-middle">';
        table += `<thead><tr><th class="text-muted" style="width:40%">${escapeHtml(fieldLabel)}</th><th class="text-muted">${escapeHtml(valueLabel)}</th></tr></thead>`;
        table += '<tbody>';
        if (rows.length === 0) {
            table += `<tr><td colspan="2" class="text-center text-muted">${escapeHtml(@js(__('admin.contact_submissions.messages.empty')))}</td></tr>`;
        } else {
            rows.forEach(([k, v]) => {
                const value = typeof v === 'boolean' ? (v ? 'true' : 'false') : (v === undefined ? '' : v);
                table += `<tr><td class="text-nowrap">${escapeHtml(k)}</td><td><code>${escapeHtml(value)}</code></td></tr>`;
            });
        }
        table += '</tbody></table></div>';

        Swal.fire({title: @js(__('admin.contact_submissions.actions.payload')), html: table, width: 800});
    }catch(e){
        Swal.fire({
            title: @js(__('admin.contact_submissions.messages.payload_error_title')),
            text: @js(__('admin.contact_submissions.messages.payload_error_text')),
            icon: 'error'
        });
    }
}
</script>
@endpush
