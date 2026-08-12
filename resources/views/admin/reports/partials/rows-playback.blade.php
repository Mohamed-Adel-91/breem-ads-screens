{{--
    Presentation for `playback` report rows, exactly as ReportController stores them
    in `reports.data.rows`. This is also the fallback layout for stored reports whose
    type the current generator no longer produces (see BACKEND DEFECTS DEFERRED) —
    `data_get` keeps every cell safe when a key is absent. Nothing is recomputed here
    and no query is executed.
--}}
<table class="table table-hover mb-0 admin-table">
    <thead>
        <tr>
            <th scope="col">{{ __('admin.reports.columns.ad_id') }}</th>
            <th scope="col">{{ __('admin.reports.columns.ad_title') }}</th>
            <th scope="col">{{ __('admin.reports.columns.plays') }}</th>
            <th scope="col">{{ __('admin.reports.columns.total_duration') }}</th>
            <th scope="col">{{ __('admin.reports.columns.screens') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            @php
                $rowScreens = data_get($row, 'screens', []);
                $rowScreens = is_array($rowScreens) ? $rowScreens : [$rowScreens];
            @endphp
            <tr>
                <td>{{ data_get($row, 'ad_id') ?? '—' }}</td>
                <td>{{ data_get($row, 'ad_title') ?? '—' }}</td>
                <td>{{ data_get($row, 'plays', 0) }}</td>
                <td>{{ data_get($row, 'total_duration', 0) }}</td>
                <td class="admin-wrap-anywhere">
                    {{ !empty($rowScreens) ? implode(', ', $rowScreens) : '—' }}
                </td>
            </tr>
        @empty
            @include('admin.partials.empty-state', [
                'colspan' => 5,
                'message' => __('admin.reports.show.data_empty'),
                'icon' => 'bar-chart-2',
            ])
        @endforelse
    </tbody>
</table>
