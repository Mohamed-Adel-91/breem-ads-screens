<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\InstallmentProgram;
use App\Models\News;
use App\Models\Video;

class PagesService
{
    public function home(): array
    {
        $cars = CarModel::with(['brand', 'colors', 'terms.specs'])
            ->where('show_status', true)
            ->where('is_home', true)
            ->get();

        $brands = Brand::with(['cars' => function ($q) {
            $q->where('show_status', true);
        }])
            ->where('show_status', true)
            ->get();

        $news = News::with('writer')
            ->published()
            ->orderBy('scheduale_date', 'desc')
            ->take(3)
            ->get();

        $videos = Video::where('hidden', false)
            ->where('home', true)
            ->orderByDesc('ord')
            ->skip(1)
            ->take(6)
            ->get();

        $last_video = Video::where('hidden', false)
            ->where('home', true)
            ->orderByDesc('ord')
            ->first();

        $installment_programs = InstallmentProgram::latest('id')->take(3)->get();

        return [
            'cars' => $cars,
            'brands' => $brands,
            'news' => $news,
            'videos' => $videos,
            'last_video' => $last_video,
            'installment_programs' => $installment_programs,
        ];
    }
    public function search(): array
    {
        $cars = CarModel::with(['brand', 'colors', 'terms.specs'])
            ->where('show_status', true)
            ->get();
        $brands = Brand::with(['cars' => function ($q) {
            $q->where('show_status', true);
        }])
            ->where('show_status', true)
            ->get();
        return [
            'cars' => $cars,
            'brands' => $brands,
        ];
    }

    public function videos(): array
    {
        $videos = Video::where('hidden', false)
            ->where('id', '!=', Video::where('hidden', false)->orderByDesc('ord')->first()->id)
            ->orderByDesc('ord')
            ->paginate(6);

        $last_video = Video::where('hidden', false)
            ->orderByDesc('ord')
            ->first();

        return [
            'videos' => $videos,
            'last_video' => $last_video,
        ];
    }

    public function serviceCenters(): array
    {
        $brands = Brand::where('show_status', true)
            ->with('serviceCenters')
            ->get();

        return ['brands' => $brands];
    }
}
