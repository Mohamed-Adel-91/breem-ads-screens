<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{
    Page,
    PageSection,
    SectionItem,
    Menu,
    MenuItem,
    Setting
};

class HomePageSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            /** ------------------------------------------------
             *  Page: Home
             * ------------------------------------------------*/
            $home = Page::updateOrCreate(
                ['slug' => 'home'],
                ['name' => 'الصفحة الرئيسية', 'is_active' => true]
            );

            /** ------------------------------------------------
             *  Header (Menu + Phone + Lang + Logo)
             * ------------------------------------------------*/
            $headerMenu = Menu::updateOrCreate(
                ['location' => 'header'],
                ['is_active' => true]
            );

            // Clear & reseed header menu items (optional)
            $headerMenu->items()->delete();

            $headerItems = [
                [
                    'order' => 1,
                    'label' => ['ar' => 'الرئيسية', 'en' => 'Home'],
                    'url'   => '/',
                    'target' => null,
                    'is_active' => true,
                ],
                [
                    'order' => 2,
                    'label' => ['ar' => 'من نحن', 'en' => 'About'],
                    'url'   => '/about',
                    'target' => null,
                    'is_active' => true,
                ],
                [
                    'order' => 3,
                    'label' => ['ar' => 'تواصل معنا', 'en' => 'Contact'],
                    'url'   => '/contact',
                    'target' => null,
                    'is_active' => true,
                ],
            ];

            foreach ($headerItems as $it) {
                MenuItem::create(array_merge($it, ['menu_id' => $headerMenu->id]));
            }

            // Header phone, lang switch, logo
            Setting::updateOrCreate(
                ['key' => 'site.phone'],
                ['value' => ['ar' => '99654334+', 'en' => '+99654334']]
            );

            Setting::updateOrCreate(
                ['key' => 'site.lang_switch'],
                ['value' => ['ar' => 'عربي', 'en' => 'EN']]
            );

            Setting::updateOrCreate(
                ['key' => 'header.logo'],
                ['value' => ['image_url' => 'img/logo.png', 'alt' => ['ar' => 'بريم', 'en' => 'Breem']]]
            );

            /** ------------------------------------------------
             *  Sidebar (4 أيقونات سوشيال/أكشن)
             *  نخزّنها كإعداد قابل للتوسّع
             * ------------------------------------------------*/
            Setting::updateOrCreate(
                ['key' => 'sidebar.icons'],
                ['value' => [
                    [
                        'svg_fill' => '#41A8A6',
                        'title'    => ['ar' => 'فيسبوك', 'en' => 'Facebook'],
                        'url'      => 'https://facebook.com/breem',
                    ],
                    [
                        'svg_fill' => '#41A8A6',
                        'title'    => ['ar' => 'تويتر', 'en' => 'Twitter/X'],
                        'url'      => 'https://x.com/breem',
                    ],
                    [
                        'svg_fill' => '#41A8A6',
                        'title'    => ['ar' => 'يوتيوب', 'en' => 'YouTube'],
                        'url'      => 'https://youtube.com/@breem',
                    ],
                    [
                        'svg_fill' => '#41A8A6',
                        'title'    => ['ar' => 'لينكدإن', 'en' => 'LinkedIn'],
                        'url'      => 'https://linkedin.com/company/breem',
                    ],
                ]]
            );

            /** ------------------------------------------------
             *  Section: Banner (video)
             * ------------------------------------------------*/
            $banner = PageSection::updateOrCreate(
                ['page_id' => $home->id, 'type' => 'banner'],
                [
                    'order' => 1,
                    'is_active' => true,
                    'settings' => [
                        'video_url' => '/img/showreel.mp4',
                        'autoplay'  => true,
                        'loop'      => true,
                        'muted'     => true,
                        'controls'  => true,
                        'playsinline' => true,
                    ],
                ]
            );

            /** ------------------------------------------------
             *  Section: Partners Slider (slider)
             * ------------------------------------------------*/
            $partners = PageSection::updateOrCreate(
                ['page_id' => $home->id, 'type' => 'partners'],
                ['order' => 2, 'is_active' => true, 'settings' => []]
            );

            // إعادة بناء العناصر
            SectionItem::where('section_id', $partners->id)->delete();
            $partnerImages = [
                'img/partener.png',
                'img/partener2.png',
                'img/partener3.png',
                'img/partener4.png',
                'img/partener5.png',
                'img/partener.png',
                'img/partener2.png',
                'img/partener3.png',
                'img/partener4.png',
                'img/partener5.png',
            ];
            foreach ($partnerImages as $i => $path) {
                SectionItem::create([
                    'section_id' => $partners->id,
                    'order' => $i + 1,
                    'data' => [
                        'image_url' => $path,
                        'alt' => ['ar' => 'شريك', 'en' => 'Partner'],
                    ],
                ]);
            }

            /** ------------------------------------------------
             *  Section: Knowmore (about)
             * ------------------------------------------------*/
            $about = PageSection::updateOrCreate(
                ['page_id' => $home->id, 'type' => 'about'],
                [
                    'order' => 3,
                    'is_active' => true,
                    'settings' => [
                        'title' => ['ar' => "تعرف على بريم", 'en' => "Know Breem"],
                        'desc'  => [
                            'ar' => "بريم تقدم لكم حلولاً تسويقية متكاملة تبدأ من إدارة حملات السوشيال ميديا باحترافية، مروراً بصناعة المحتوى الإبداعي والتصميمات الجذابة، وصولاً إلى شاشات الإعلانات التي تضمن وصول علامتكم التجارية إلى الجمهور في الأماكن الحيوية.",
                            'en' => "Breem offers integrated marketing solutions: expert social media campaigns, creative content and designs, and ad screens that put your brand in prime locations.",
                        ],
                        'readmore_link' => '#',
                    ],
                ]
            );

            /** ------------------------------------------------
             *  Section: Media Stats (media)
             * ------------------------------------------------*/
            $stats = PageSection::updateOrCreate(
                ['page_id' => $home->id, 'type' => 'stats'],
                ['order' => 4, 'is_active' => true, 'settings' => []]
            );

            SectionItem::where('section_id', $stats->id)->delete();
            $statsItems = [
                [
                    'icon_url' => 'img/pc.png',
                    'number'   => '٦٥۸+',
                    'label'    => ['ar' => 'شاشات الإعلانات', 'en' => 'Ad Screens'],
                ],
                [
                    'icon_url' => 'img/social.png',
                    'number'   => '۲۱٥+',
                    'label'    => ['ar' => 'اعلانات سوشيال', 'en' => 'Social Ads'],
                ],
                [
                    'icon_url' => 'img/screen.png',
                    'number'   => '۳٤۷+',
                    'label'    => ['ar' => 'تصوير إعلانات', 'en' => 'Ad Production'],
                ],
                [
                    'icon_url' => 'img/pc.png',
                    'number'   => '٦٥۸+',
                    'label'    => ['ar' => 'شاشات الإعلانات', 'en' => 'Ad Screens'],
                ],
            ];

            foreach ($statsItems as $i => $it) {
                SectionItem::create([
                    'section_id' => $stats->id,
                    'order' => $i + 1,
                    'data'  => $it
                ]);
            }

            /** ------------------------------------------------
             *  Section: Where Us (where_us) + brochure button
             * ------------------------------------------------*/
            $where = PageSection::updateOrCreate(
                ['page_id' => $home->id, 'type' => 'where_us'],
                [
                    'order' => 5,
                    'is_active' => true,
                    'settings' => [
                        'title' => ['ar' => 'أين تجدنا', 'en' => 'Where to find us'],
                        'brochure' => [
                            'text' => ['ar' => 'حمل الكتيب للمزيد', 'en' => 'Download brochure'],
                            'icon_url' => 'img/download.png',
                            'link_url' => '#', // ضع لينك الكتيب الحقيقي
                        ],
                    ],
                ]
            );

            SectionItem::where('section_id', $where->id)->delete();
            $whereSlides = [
                ['image_url' => 'img/first.png',  'overlay_text' => ['ar' => 'العربية',       'en' => 'Al Arabiya']],
                ['image_url' => 'img/second.png', 'overlay_text' => ['ar' => 'ميديا السعودية', 'en' => 'Media Saudi']],
                ['image_url' => 'img/where3.png', 'overlay_text' => ['ar' => 'نصف مليون',     'en' => 'Half Million']],
                ['image_url' => 'img/first.png',  'overlay_text' => ['ar' => 'العربية',       'en' => 'Al Arabiya']],
                ['image_url' => 'img/second.png', 'overlay_text' => ['ar' => 'ميديا السعودية', 'en' => 'Media Saudi']],
                ['image_url' => 'img/where3.png', 'overlay_text' => ['ar' => 'نصف مليون',     'en' => 'Half Million']],
            ];
            foreach ($whereSlides as $i => $slide) {
                SectionItem::create([
                    'section_id' => $where->id,
                    'order' => $i + 1,
                    'data' => $slide,
                ]);
            }

            /** ------------------------------------------------
             *  Section: CTA (your_ads)
             * ------------------------------------------------*/
            $cta = PageSection::updateOrCreate(
                ['page_id' => $home->id, 'type' => 'cta'],
                [
                    'order' => 6,
                    'is_active' => true,
                    'settings' => [
                        'title' => ['ar' => 'إعرض إعلانك الأن', 'en' => 'Run Your Ad Now'],
                        'text'  => [
                            'ar' => 'فريقنا المتخصص مستعد دائمًا للرد على استفساراتك وتلبية واحتياجاتك. نحن هنا لتحويل رؤيتك الإعلانية إلى واقع.',
                            'en' => 'Our specialist team is ready to answer your questions and turn your ad vision into reality.',
                        ],
                        'link_text' => ['ar' => 'تواصل معنا', 'en' => 'Contact Us'],
                        'link_url'  => '/contact',
                        'image_url' => 'img/screen_image.png',
                        'overlay_image_url' => 'img/ads.png',
                    ],
                ]
            );

            /** ------------------------------------------------
             *  Footer (Menu + Social + Logo + Map)
             * ------------------------------------------------*/
            $footerMenu = Menu::updateOrCreate(
                ['location' => 'footer'],
                ['is_active' => true]
            );

            $footerMenu->items()->delete();
            $footerItems = [
                [
                    'order' => 1,
                    'label' => ['ar' => 'الرئيسية', 'en' => 'Home'],
                    'url'   => '/',
                    'target' => null,
                    'is_active' => true,
                ],
                [
                    'order' => 2,
                    'label' => ['ar' => 'من نحن', 'en' => 'About'],
                    'url'   => '/about',
                    'target' => null,
                    'is_active' => true,
                ],
                [
                    'order' => 3,
                    'label' => ['ar' => 'تواصل معنا', 'en' => 'Contact'],
                    'url'   => '/contact',
                    'target' => null,
                    'is_active' => true,
                ],
            ];
            foreach ($footerItems as $it) {
                MenuItem::create(array_merge($it, ['menu_id' => $footerMenu->id]));
            }

            Setting::updateOrCreate(
                ['key' => 'footer.logo'],
                ['value' => ['image_url' => 'img/whitelogo.png', 'alt' => ['ar' => 'بريم', 'en' => 'Breem']]]
            );

            Setting::updateOrCreate(
                ['key' => 'social.links'],
                ['value' => [
                    'facebook' => 'https://facebook.com/breem',
                    'twitter'  => 'https://x.com/breem',
                    'youtube'  => 'https://youtube.com/@breem',
                    'linkedin' => 'https://linkedin.com/company/breem',
                ]]
            );

            Setting::updateOrCreate(
                ['key' => 'map.iframe'],
                ['value' =>
                ['embed' => <<<HTML
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3623.0167749443312!2d46.63770047514857!3d24.760613977994602!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3e2ee30015a7712b%3A0x54a1493cb03a0bdd!2z2YXYsdmD2LIg2KfZhNmF2YTZgyDYudio2K_Yp9mE2YTZhyDYp9mE2YXYp9mE2Yo!5e0!3m2!1sen!2seg!4v1756225530885!5m2!1sen!2seg"
                    width="400" height="300" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
                HTML
                ]]
            );
        });
    }
}
