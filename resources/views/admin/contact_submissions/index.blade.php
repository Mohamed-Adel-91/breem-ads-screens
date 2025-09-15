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
        const html = `<pre class="text-start">${JSON.stringify(json, null, 2)}</pre>`;
        Swal.fire({title: @js(__('admin.contact_submissions.actions.payload')), html: html, width: 800});
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

