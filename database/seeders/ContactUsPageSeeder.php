<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{Page, PageSection};

class ContactUsPageSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $page = Page::updateOrCreate(
                ['slug' => 'contact-us'],
                ['name' => 'Contact Us / تواصل معنا', 'is_active' => true]
            );

            // Banner
            PageSection::updateOrCreate(
                ['page_id' => $page->id, 'type' => 'second_banner'],
                [
                    'order' => 1,
                    'is_active' => true,
                    'section_data' => [
                        'ar' => ['image_path' => 'img/contact.png'],
                        'en' => ['image_path' => 'img/contact.png'],
                    ],
                ]
            );

            // Contact heading/intro
            PageSection::updateOrCreate(
                ['page_id' => $page->id, 'type' => 'contact_us'],
                [
                    'order' => 2,
                    'is_active' => true,
                    'section_data' => [
                        'ar' => [
                            'title' => 'تواصل معنا',
                            'subtitle' => 'اختار الخدمة التى تناسبك.',
                        ],
                        'en' => [
                            'title' => 'Contact Us',
                            'subtitle' => 'Choose the service that fits you.',
                        ],
                    ],
                ]
            );

            // Map + contact info section
            PageSection::updateOrCreate(
                ['page_id' => $page->id, 'type' => 'map'],
                [
                    'order' => 3,
                    'is_active' => true,
                    'section_data' => [
                        'ar' => [
                            'background_image_path' => 'img/map.png',
                            'title' => 'موقعنا',
                            'address' => 'شارع بني تميم متفرع من الملك فهد – حي المروج، مبنى رقم 2174، الدور الخامس الرمز البريدي 12282 – الرياض، المملكة العربية السعودية.',
                            'phone_label' => 'رقم جوال : ۹۹٦٥٤۳۳٤+',
                            'whatsapp_label' => 'رقم الواتس اب : ۹۹٦٥٤۳۳٤+',
                        ],
                        'en' => [
                            'background_image_path' => 'img/map.png',
                            'title' => 'Our Location',
                            'address' => 'Bani Tamim St., from King Fahd – Al Muruj, Bldg. 2174, 5th Floor, Postal Code 12282 – Riyadh, KSA.',
                            'phone_label' => 'Mobile: +99654334',
                            'whatsapp_label' => 'WhatsApp: +99654334',
                        ],
                    ],
                ]
            );

            // Bottom banner
            PageSection::updateOrCreate(
                ['page_id' => $page->id, 'type' => 'bottom_banner'],
                [
                    'order' => 4,
                    'is_active' => true,
                    'section_data' => [
                        'ar' => ['image_path' => 'img/banner_sa.png'],
                        'en' => ['image_path' => 'img/banner_sa.png'],
                    ],
                ]
            );
        });
    }
}

