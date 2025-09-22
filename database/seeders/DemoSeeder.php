<?php

namespace Database\Seeders;

use App\Enums\AdStatus;
use App\Enums\PlaceType;
use App\Enums\ScreenStatus;
use App\Models\Ad;
use App\Models\AdSchedule;
use App\Models\Place;
use App\Models\Screen;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Seed demo data for places, screens, and ads.
     */
    public function run(): void
    {
        $place = Place::updateOrCreate(
            ['name->en' => 'Test Place'],
            [
                'name' => ['en' => 'Test Place'],
                'address' => ['en' => '123 Demo Street'],
                'type' => PlaceType::Other,
            ]
        );

        $screen = Screen::updateOrCreate(
            ['code' => 'SCR-001'],
            [
                'place_id' => $place->id,
                'device_uid' => null,
                'status' => ScreenStatus::Online,
            ]
        );

        $user = User::updateOrCreate(
            ['email' => 'demo.advertiser@example.com'],
            [
                'name' => 'Demo Advertiser',
                'password' => Hash::make('password'),
            ]
        );

        $now = Carbon::now();

        $ad = Ad::updateOrCreate(
            ['file_path' => 'upload/ads/demo-ad.mp4'],
            [
                'title' => ['en' => 'Demo Campaign'],
                'description' => ['en' => 'Sample approved advertisement for demo screens.'],
                'file_type' => 'video',
                'duration_seconds' => 30,
                'status' => AdStatus::Active,
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'start_date' => $now->copy()->subDay(),
                'end_date' => $now->copy()->addMonth(),
            ]
        );

        $ad->screens()->syncWithoutDetaching([
            $screen->id => ['play_order' => 1],
        ]);

        AdSchedule::updateOrCreate(
            [
                'ad_id' => $ad->id,
                'screen_id' => $screen->id,
            ],
            [
                'start_time' => $now->copy()->subDay(),
                'end_time' => $now->copy()->addMonth(),
                'is_active' => true,
            ]
        );
    }
}
