<?php

namespace App\Services;

use App\Models\CarModel;
use App\Models\KnowMore;

class CarInfoService
{
    public function build(int $id): array
    {
        $car = CarModel::with([
            'brand',
            'colors',
            'terms.specs',
            'knowMore',
            'knowMoreVideos',
            'knowMoreHeroVideo',
            'knowMoreImages',
            'knowMoreHeroImage',
            'exteriors',
            'heroExterior',
            'interiors',
            'heroInterior',
            'infoFeatures'
        ])->findOrFail($id);

        $canonical = \unicode_slug($car->name, '-');

        $heroVideoSrc = $car->knowMoreHeroVideo ? $this->toEmbed($car->knowMoreHeroVideo->video) : null;
        $noneHeroVideoSrc = $car->knowMoreVideo ? $this->toEmbed($car->knowMoreVideo->video) : null;

        $knowMoreNonHero = $car->knowMore->where('hero_section', false)->values();
        $knowMoreEmbedUrls = $car->knowMore->mapWithKeys(function ($item) {
            return [$item->id => $this->toEmbed($item->video)];
        });

        return [
            'car' => $car,
            'canonical' => $canonical,
            'heroVideoSrc' => $heroVideoSrc,
            'noneHeroVideoSrc' => $noneHeroVideoSrc,
            'knowMoreNonHero' => $knowMoreNonHero,
            'knowMoreEmbedUrls' => $knowMoreEmbedUrls,
        ];
    }

    private function toEmbed(?string $video): ?string
    {
        if (!$video) return null;
        $patterns = [
            '%youtube\.com/watch\?v=([A-Za-z0-9_-]{11})%i',
            '%youtube\.com/embed/([A-Za-z0-9_-]{11})%i',
            '%youtu\.be/([A-Za-z0-9_-]{11})%i',
            '%youtube\.com/shorts/([A-Za-z0-9_-]{11})%i',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $video, $m)) {
                return "https://www.youtube.com/embed/{$m[1]}";
            }
        }
        return asset(KnowMore::UPLOAD_FOLDER . $video);
    }
}
