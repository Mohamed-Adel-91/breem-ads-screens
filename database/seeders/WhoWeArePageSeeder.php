<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{Page, PageSection, SectionItem};

class WhoWeArePageSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $page = Page::updateOrCreate(
                ['slug' => 'whoweare'],
                ['name' => 'Who We Are / من نحن', 'is_active' => true]
            );

            // Section: second_banner
            $banner = PageSection::updateOrCreate(
                ['page_id' => $page->id, 'type' => 'second_banner'],
                [
                    'order' => 1,
                    'is_active' => true,
                    'section_data' => [
                        'ar' => ['image_path' => 'img/banner2.png'],
                        'en' => ['image_path' => 'img/banner2.png'],
                    ],
                ]
            );

            // Section: who_we (intro + features items)
            $who = PageSection::updateOrCreate(
                ['page_id' => $page->id, 'type' => 'who_we'],
                [
                    'order' => 2,
                    'is_active' => true,
                    'section_data' => [
                        'ar' => [
                            'title' => 'من نحن',
                            'description' => 'بريم تقدم لكم حلولاً تسويقية متكاملة تبدأ من إدارة حملات السوشيال ميديا باحترافية، مروراً بصناعة المحتوى الإبداعي والتصميمات الجذابة، وصولاً إلى شاشات الإعلانات التي تضمن وصول علامتكم التجارية إلى الجمهور في الأماكن الحيوية. نحن نؤمن بأن التسويق الفعّال هو مفتاح نجاح أي علامة تجارية. في "بريم"، نقدم لك مجموعة من الحلول التسويقية المتكاملة التي تضمن لك الوصول إلى جمهورك المستهدف بأعلى فعالية. خدماتنا تشمل:'
                        ],
                        'en' => [
                            'title' => 'Who We Are',
                            'description' => 'Breem provides integrated marketing solutions from professional social media campaign management and creative content production to impactful ad screens in key locations.'
                        ],
                    ],
                ]
            );

            // Clear items to avoid duplicates on reseed
            SectionItem::where('section_id', $who->id)->delete();

            $features = [
                [
                    'title' => ['ar' => 'إدارة حملات السوشيال ميديا باحترافية', 'en' => 'Professional Social Media Campaigns'],
                    'text'  => ['ar' => 'ندير حملاتك على منصات التواصل الاجتماعي من خلال استراتيجيات مدروسة ومحتوى مخصص لزيادة الوعي بالعلامة التجارية.', 'en' => 'We manage your social campaigns with tailored strategies and content to increase brand awareness.'],
                    'bullets' => [
                        ['ar' => 'تحليل الأداء: متابعة مستمرة لنتائج الحملات لضمان الوصول الأمثل.', 'en' => 'Performance analysis: continuously monitoring results to maximize reach.'],
                        ['ar' => 'التفاعل مع الجمهور: إدارة التعليقات والردود لخلق علاقة متينة مع المتابعين.', 'en' => 'Audience engagement: managing comments and replies to build connections.'],
                    ],
                ],
                [
                    'title' => ['ar' => 'صناعة المحتوى الإبداعي', 'en' => 'Creative Content Production'],
                    'text'  => ['ar' => 'فريقنا يصمم محتوى مميز يعكس هوية علامتك التجارية عبر فيديوهات ومقالات ورسومات وصور.', 'en' => 'Our team crafts content that reflects your brand across videos, articles, graphics, and images.'],
                    'bullets' => [
                        ['ar' => 'محتوى مرن: يتناسب مع مختلف الأذواق والأنماط.', 'en' => 'Flexible content: fits different tastes and styles.'],
                        ['ar' => 'استهداف دقيق: استخدام أدوات التحليل للوصول إلى جمهورك المثالي.', 'en' => 'Precise targeting: analytics-driven to reach your ideal audience.'],
                    ],
                ],
                [
                    'title' => ['ar' => 'تصميمات جذابة وبصرية', 'en' => 'Engaging Visual Designs'],
                    'text'  => ['ar' => 'نقدم تصميمات عالية الجودة تعزز الهوية البصرية لعلامتك التجارية وتجذب الانتباه.', 'en' => 'High-quality designs that strengthen visual identity and capture attention.'],
                    'bullets' => [
                        ['ar' => 'تسويق مرئي قوي: تصميم شعارات ومنشورات وفيديوهات تفاعلية.', 'en' => 'Strong visual marketing: logos, posts, and interactive videos.'],
                        ['ar' => 'تنسيق إبداعي: مزج الألوان والخطوط بما يناسب توجهك التجاري.', 'en' => 'Creative composition: colors and typography aligned to your brand.'],
                    ],
                ],
            ];

            foreach ($features as $i => $f) {
                SectionItem::create([
                    'section_id' => $who->id,
                    'order' => $i + 1,
                    'data' => [
                        'ar' => [
                            'title' => $f['title']['ar'],
                            'text' => $f['text']['ar'],
                            'bullets' => array_column($f['bullets'], 'ar'),
                        ],
                        'en' => [
                            'title' => $f['title']['en'],
                            'text' => $f['text']['en'],
                            'bullets' => array_column($f['bullets'], 'en'),
                        ],
                    ],
                ]);
            }

            // Section: port_image
            $port = PageSection::updateOrCreate(
                ['page_id' => $page->id, 'type' => 'port_image'],
                [
                    'order' => 3,
                    'is_active' => true,
                    'section_data' => [
                        'ar' => ['image_path' => 'img/port.png'],
                        'en' => ['image_path' => 'img/port.png'],
                    ],
                ]
            );
        });
    }
}

