{{--
    Presentation for `screen-uptime` report rows, exactly as ReportController
    stores them in `reports.data.rows`. Nothing is recomputed here and no query is
    executed — the stored JSON is the only source.
--}}
<table class="table table-hover mb-0 admin-table">
    <thead>
        <tr>
            <th scope="col">{{ __('admin.reports.columns.screen_id') }}</th>
            <th scope="col">{{ __('admin.reports.columns.screen_code') }}</th>
            <th scope="col">{{ __('admin.reports.columns.place') }}</th>
            <th scope="col">{{ __('admin.reports.columns.online_events') }}</th>
            <th scope="col">{{ __('admin.reports.columns.offline_events') }}</th>
            <th scope="col">{{ __('admin.reports.columns.last_status') }}</th>
            <th scope="col">{{ __('admin.reports.columns.period_start') }}</th>
            <th scope="col">{{ __('admin.reports.columns.period_end') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                <td>{{ data_get($row, 'screen_id') ?? '—' }}</td>
                <td>
                    @if (data_get($row, 'screen_code'))
                        <code>{{ data_get($row, 'screen_code') }}</code>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ data_get($row, 'place') ?? '—' }}</td>
                <td>{{ data_get($row, 'online_events', 0) }}</td>
                <td>{{ data_get($row, 'offline_events', 0) }}</td>
                <td>
                    @include('admin.screens.partials.status-badge', ['status' => data_get($row, 'last_status')])
                </td>
                <td dir="ltr">{{ data_get($row, 'period_start') ?? '—' }}</td>
                <td dir="ltr">{{ data_get($row, 'period_end') ?? '—' }}</td>
            </tr>
        @empty
            @include('admin.partials.empty-state', [
                'colspan' => 8,
                'message' => __('admin.reports.show.data_empty'),
                'icon' => 'bar-chart-2',
            ])
        @endforelse
    </tbody>
</table>
