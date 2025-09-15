@extends('admin.layouts.master')
@section('content')
<div class="page-wrapper">
    @include('admin.layouts.sidebar')
    <div class="page-content">
        @include('admin.layouts.page-header', ['pageName' => 'Contact Submissions'])
        <div class="main-container">
            @include('admin.layouts.alerts')
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Created</th>
                                <th>Actions</th>
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
                                        <button class="btn btn-outline-info" onclick="showPayload({{ $item->id }})">View</button>
                                        <form id="delete_form_{{ $item->id }}" action="{{ route('admin.contact_submissions.destroy', ['lang'=>app()->getLocale(),'submission'=>$item->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" onclick="checker(event, '{{ $item->id }}')">Delete</button>
                                        </form>
                                    </div>
                                    <script type="application/json" id="payload_{{ $item->id }}">@json($item->payload)</script>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No submissions</td></tr>
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
        Swal.fire({title: 'Payload', html: html, width: 800});
    }catch(e){ Swal.fire('Error','Could not parse payload','error'); }
}
</script>
@endpush

