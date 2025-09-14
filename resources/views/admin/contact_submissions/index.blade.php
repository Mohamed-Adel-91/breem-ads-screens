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
                        @forelse($submissions as $s)
                            <tr>
                                <td>{{ $s->id }}</td>
                                <td><span class="badge bg-primary">{{ $s->type }}</span></td>
                                <td>{{ $s->name }}</td>
                                <td>{{ $s->phone }}</td>
                                <td>{{ $s->email }}</td>
                                <td>{{ $s->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-info" onclick="showPayload({{ $s->id }})">View</button>
                                        <form id="delete_form_{{ $s->id }}" action="{{ route('admin.contact_submissions.destroy', ['lang'=>app()->getLocale(),'submission'=>$s->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" onclick="checker(event, '{{ $s->id }}')">Delete</button>
                                        </form>
                                    </div>
                                    <script type="application/json" id="payload_{{ $s->id }}">@json($s->payload)</script>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No submissions</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">{{ $submissions->links('admin.partials.pagination') }}</div>
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

