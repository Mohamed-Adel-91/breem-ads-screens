@extends('admin.layouts.master')
@section('content')
<div class="page-wrapper">
    @include('admin.layouts.sidebar')
    <div class="page-content">
        @include('admin.layouts.page-header', ['pageName' => $page->name])
        <div class="main-container">
            @include('admin.layouts.alerts')

            <div class="row">
                <div class="col-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-0">Page: {{ $page->name }} ({{ $page->slug }})</h5>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    @foreach($page->sections as $section)
                    <div class="card mb-3" id="section_{{ $section->id }}">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div>
                                <strong>#{{ $section->id }}</strong>
                                <span class="badge bg-secondary">Type: {{ $section->type ?? '-' }}</span>
                                <span class="badge bg-info">Order: <span class="sec-order">{{ $section->order }}</span></span>
                                <span class="badge bg-{{ $section->is_active ? 'success' : 'danger' }} sec-active">
                                    {{ $section->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" onclick="updateSectionOrder({{ $section->id }})">Update Order</button>
                                <button class="btn btn-sm btn-outline-warning" onclick="toggleSection({{ $section->id }})">Toggle</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteSection({{ $section->id }})">Delete</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                @php
                                    $secData = is_array($section->section_data) ? $section->section_data : (array)($section->section_data ?? []);
                                @endphp
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-2">Section Data ({{ app()->getLocale() }})</h6>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" onclick="formatSectionData({{ $section->id }})">Format</button>
                                        <button class="btn btn-outline-danger" onclick="clearSectionData({{ $section->id }})">Clear</button>
                                        <button class="btn btn-primary" onclick="saveSectionData({{ $section->id }})">Save</button>
                                    </div>
                                </div>

                                @if (empty($secData))
                                    <div class="alert alert-light border">No section data for this locale.</div>
                                @else
                                    <form id="secform_{{ $section->id }}" class="row g-3">
                                        @foreach ($secData as $key => $value)
                                            @php
                                                $label = \Illuminate\Support\Str::headline($key);
                                                $isBool = is_bool($value) || $value === 1 || $value === 0 || $value === '1' || $value === '0' || (is_string($value) && in_array(strtolower($value), ['true','false']));
                                                $boolChecked = is_bool($value) ? $value : in_array($value, [1, '1', 'true', 'TRUE', true], true);
                                            @endphp

                                            @if ($isBool)
                                                <div class="col-12">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input sec-checkbox" id="sec_{{ $section->id }}_{{ $key }}" data-key="{{ $key }}" @checked($boolChecked)>
                                                        <label for="sec_{{ $section->id }}_{{ $key }}" class="form-check-label">{{ $label }}</label>
                                                    </div>
                                                </div>
                                            @elseif (\Illuminate\Support\Str::contains($key, ['_path', '_url']))
                                                <div class="col-12">
                                                    @include('admin.layouts.components.media-upload', [
                                                        'label' => $label,
                                                        'name' => "uploads[$key]",
                                                        'inputId' => "upload_{$section->id}_{$key}",
                                                        'previewPath' => media_path($value),
                                                    ])
                                                    <input type="hidden" class="sec-current-value" data-key="{{ $key }}" value="{{ $value }}">
                                                </div>
                                            @elseif (is_string($value))
                                                <div class="col-12">
                                                    <label class="form-label" for="sec_{{ $section->id }}_{{ $key }}">{{ $label }}</label>
                                                    <textarea id="sec_{{ $section->id }}_{{ $key }}" class="form-control sec-text" data-key="{{ $key }}" rows="3">{{ $value }}</textarea>
                                                </div>
                                            @else
                                                <div class="col-12">
                                                    <label class="form-label">{{ $label }}</label>
                                                    <pre class="bg-light border rounded p-2 mb-0">{{ json_encode($value, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                        @endforeach
                                    </form>
                                @endif

                                <textarea id="secdata_{{ $section->id }}" class="form-control font-monospace mt-3" rows="8" style="display:none;">@json($section->section_data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)</textarea>
                                <small class="text-muted">Edit JSON for this section's data. Stored per locale.</small>
                            </div>

                            @if($section->items->count())
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Order</th>
                                            <th>Active</th>
                                            <th style="width: 240px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($section->items as $item)
                                        @php
                                            $itemActive = isset($item->is_active) ? (bool)$item->is_active : (bool)($item->data['is_active'] ?? true);
                                        @endphp
                                        <tr id="item_{{ $item->id }}">
                                            <td>{{ $item->id }}</td>
                                            <td><span class="itm-order">{{ $item->order }}</span></td>
                                            <td><span class="badge bg-{{ $itemActive ? 'success' : 'danger' }} itm-active">{{ $itemActive ? 'Active' : 'Inactive' }}</span></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="updateItemOrder({{ $item->id }})">Update Order</button>
                                                    <button class="btn btn-outline-warning" onclick="toggleItem({{ $item->id }})">Toggle</button>
                                                    <button class="btn btn-outline-danger" onclick="deleteItem({{ $item->id }})">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <em class="text-muted">No items in this section.</em>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('custom-js-scripts')
<script>
function toggleSection(id) {
    axios.patch(`/${'{{ app()->getLocale() }}'}/admin-panel/cms/sections/${id}/toggle`)
        .then(({data}) => {
            const card = document.getElementById(`section_${id}`);
            const badge = card.querySelector('.sec-active');
            badge.classList.remove('bg-success', 'bg-danger');
            badge.classList.add(data.is_active ? 'bg-success' : 'bg-danger');
            badge.innerText = data.is_active ? 'Active' : 'Inactive';
        })
        .catch(() => Swal.fire('Error', 'Could not toggle section', 'error'));
}
function updateSectionOrder(id) {
    Swal.fire({title: 'New Order', input: 'number', inputAttributes:{min:0}, showCancelButton:true})
        .then(res => { if(!res.isConfirmed) return; return axios.patch(`/${'{{ app()->getLocale() }}'}/admin-panel/cms/sections/${id}`, {order: parseInt(res.value||0)}) })
        .then(res => { if(!res) return; const card = document.getElementById(`section_${id}`); card.querySelector('.sec-order').innerText = res.data.section.order; })
        .catch(() => {});
}
function deleteSection(id) {
    Swal.fire({title: 'Delete section?', icon:'warning', showCancelButton:true}).then(r=>{
        if(!r.isConfirmed) return; axios.delete(`/${'{{ app()->getLocale() }}'}/admin-panel/cms/sections/${id}`)
            .then(()=>{ document.getElementById(`section_${id}`).remove(); })
            .catch(()=> Swal.fire('Error','Could not delete','error'));
    })
}
function saveSectionData(id) {
    const form = document.getElementById(`secform_${id}`);
    if (form) {
        const fd = new FormData();
        // Text fields
        form.querySelectorAll('.sec-text').forEach(el => {
            const key = el.dataset.key;
            fd.append(`section_data[${key}]`, el.value ?? '');
        });
        // Checkboxes (always send explicit 1/0)
        form.querySelectorAll('.sec-checkbox').forEach(el => {
            const key = el.dataset.key;
            fd.append(`section_data[${key}]`, el.checked ? 1 : 0);
        });
        // Existing path values (used when no new file selected)
        const currentPaths = {};
        form.querySelectorAll('.sec-current-value').forEach(el => {
            currentPaths[el.dataset.key] = el.value || '';
        });
        // Files for uploads[...] and fallbacks to current path
        const uploadInputs = form.querySelectorAll('input[type="file"][name^="uploads["]');
        uploadInputs.forEach(input => {
            // name pattern uploads[key]
            const m = input.name.match(/^uploads\[(.+)\]$/);
            const key = m ? m[1] : null;
            if (!key) return;
            if (input.files && input.files[0]) {
                fd.append(input.name, input.files[0]);
            } else {
                // Keep current path as string if no file selected
                if (Object.prototype.hasOwnProperty.call(currentPaths, key)) {
                    fd.append(`section_data[${key}]`, currentPaths[key] ?? '');
                }
            }
        });

        axios.patch(`/${'{{ app()->getLocale() }}'}/admin-panel/cms/sections/${id}`, fd)
            .then(()=> Swal.fire('Saved', 'Section data updated', 'success'))
            .catch(()=> Swal.fire('Error','Could not save section data','error'));
        return;
    }

    // Fallback: raw JSON textarea
    const ta = document.getElementById(`secdata_${id}`);
    let parsed;
    try { parsed = ta.value.trim() ? JSON.parse(ta.value) : {}; }
    catch(e){ Swal.fire('Invalid JSON', 'Fix JSON then try again.', 'error'); return; }
    axios.patch(`/${'{{ app()->getLocale() }}'}/admin-panel/cms/sections/${id}`, {section_data: parsed})
        .then(()=> Swal.fire('Saved', 'Section data updated', 'success'))
        .catch(()=> Swal.fire('Error','Could not save section data','error'));
}
function formatSectionData(id) {
    const ta = document.getElementById(`secdata_${id}`);
    try { const obj = ta.value.trim() ? JSON.parse(ta.value) : {}; ta.value = JSON.stringify(obj, null, 2); }
    catch(e){ Swal.fire('Invalid JSON','Cannot format invalid JSON','warning'); }
}
function clearSectionData(id) {
    Swal.fire({title:'Clear data?', text:'This will set {} for current locale', icon:'warning', showCancelButton:true})
        .then(r=>{ if(!r.isConfirmed) return; document.getElementById(`secdata_${id}`).value = '{}'; });
}
function toggleItem(id) {
    axios.patch(`/${'{{ app()->getLocale() }}'}/admin-panel/cms/items/${id}/toggle`).then(({data})=>{
        const row = document.getElementById(`item_${id}`);
        const b = row.querySelector('.itm-active');
        b.classList.remove('bg-success','bg-danger');
        b.classList.add(data.is_active ? 'bg-success' : 'bg-danger');
        b.innerText = data.is_active ? 'Active' : 'Inactive';
    }).catch(()=> Swal.fire('Error','Could not toggle item','error'));
}
function updateItemOrder(id) {
    Swal.fire({title: 'New Order', input: 'number', inputAttributes:{min:0}, showCancelButton:true})
        .then(res => { if(!res.isConfirmed) return; return axios.patch(`/${'{{ app()->getLocale() }}'}/admin-panel/cms/items/${id}`, {order: parseInt(res.value||0)}) })
        .then(res => { if(!res) return; const row = document.getElementById(`item_${id}`); row.querySelector('.itm-order').innerText = res.data.item.order; })
        .catch(()=>{});
}
function deleteItem(id) {
    Swal.fire({title: 'Delete item?', icon:'warning', showCancelButton:true}).then(r=>{
        if(!r.isConfirmed) return; axios.delete(`/${'{{ app()->getLocale() }}'}/admin-panel/cms/items/${id}`)
            .then(()=>{ document.getElementById(`item_${id}`).remove(); })
            .catch(()=> Swal.fire('Error','Could not delete','error'));
    })
}
</script>
@endpush
