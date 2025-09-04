<?php
namespace App\Exports;

use Spatie\Activitylog\Models\Activity;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class ActivityLogsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $fromDate;
    protected $toDate;
    protected $today;

    public function __construct(array $filters = [])
    {
        $this->fromDate = $filters['from_date'] ?? null;
        $this->toDate = $filters['to_date'] ?? null;
        $this->today = $filters['today'] ?? null;
    }

    public function query()
    {
        $query = Activity::with('causer');

        if ($this->today) {
            $query->whereDate('created_at', Carbon::today());
        } else {
            $from = $this->fromDate ? Carbon::parse($this->fromDate)->startOfDay() : null;
            $to = $this->toDate ? Carbon::parse($this->toDate)->endOfDay() : null;

            if ($from && $to) {
                if ($from->diffInDays($to) > 31) {
                    $to = $from->copy()->addMonth();
                }
                $query->whereBetween('created_at', [$from, $to]);
            } elseif ($from) {
                $to = $from->copy()->addMonth();
                $query->whereBetween('created_at', [$from, $to]);
            } elseif ($to) {
                $from = $to->copy()->subMonth();
                $query->whereBetween('created_at', [$from, $to]);
            }
        }
        return $query->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            '#',
            'Description',
            'Causer',
            'Properties',
            'Created At',
            'Updated At',
        ];
    }

    public function map($activity): array
    {
        return [
            $activity->id,
            $activity->description,
            optional($activity->causer)->first_name . ' ' . optional($activity->causer)->last_name,
            $activity->properties ? $activity->properties->toJson(JSON_UNESCAPED_UNICODE) : '',
            optional($activity->created_at)->format('Y-m-d H:i:s'),
            optional($activity->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
