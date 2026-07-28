@php
    $colspan = $colspan ?? 1;
    $message = $message ?? __('admin.table.no_records');
    $icon = $icon ?? 'inbox';
@endphp

<tr>
    <td colspan="{{ $colspan }}">
        <div class="admin-empty-state">
            <i class="fe fe-{{ $icon }}" aria-hidden="true"></i>
            <span>{{ $message }}</span>
        </div>
    </td>
</tr>
