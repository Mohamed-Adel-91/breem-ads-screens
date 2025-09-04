<?php
namespace App\Exports;

use App\Models\NewsLetter;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class NewsletterExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return NewsLetter::query();
    }

    public function headings(): array
    {
        return [
            '#',
            'Email',
            'Created At',
            'Updated At',
        ];
    }

    public function map($newsletter): array
    {
        return [
            $newsletter->id,
            $newsletter->email,
            $newsletter->created_at->format('Y-m-d H:i:s'),
            $newsletter->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

