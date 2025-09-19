<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\PageSection;
use Database\Seeders\ContactUsPageSeeder;
use Database\Seeders\HomePageSeeder;
use Database\Seeders\WhoWeArePageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CmsPagesTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HomePageSeeder::class);
        $this->seed(WhoWeArePageSeeder::class);
        $this->seed(ContactUsPageSeeder::class);

        $this->admin = Admin::create([
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'mobile' => '1234567890',
        ]);
    }

    protected function actingAsAdmin()
    {
        return $this->actingAs($this->admin, 'admin');
    }

    public function test_home_edit_screen_is_accessible(): void
    {
        $response = $this->actingAsAdmin()->get(route('admin.cms.home.edit', ['lang' => 'en']));

        $response->assertOk();
        $response->assertViewIs('admin.cms.home.edit');
    }

    public function test_home_page_sections_can_be_updated(): void
    {
        $bannerVideo = UploadedFile::fake()->create('banner.mp4', 1200, 'video/mp4');
        $partnerImage = UploadedFile::fake()->image('partner.png');
        $statIcon = UploadedFile::fake()->image('stat.png');
        $whereImage = UploadedFile::fake()->image('where.png');
        $brochurePdf = UploadedFile::fake()->create('brochure.pdf', 500, 'application/pdf');
        $brochureIcon = UploadedFile::fake()->image('icon.png');
        $ctaImage = UploadedFile::fake()->image('cta.png');
        $ctaOverlay = UploadedFile::fake()->image('cta-overlay.png');

        $partnerSection = PageSection::where('type', 'partners')->first();
        $partnerItems = $partnerSection->items()->orderBy('order')->get();
        $partnersPayload = [];
        foreach ($partnerItems as $index => $item) {
            $en = $item->getTranslation('data', 'en', true);
            $partnersPayload[$index] = [
                'id' => $item->id,
                'order' => $index + 1,
                'existing_image' => $en['image_path'] ?? null,
                'alt' => [
                    'en' => "Partner {$item->id}",
                    'ar' => "شريك {$item->id}",
                ],
            ];
            if ($index === 0) {
                $partnersPayload[$index]['image'] = $partnerImage;
            }
        }

        $statsSection = PageSection::where('type', 'stats')->first();
        $statsItems = $statsSection->items()->orderBy('order')->get();
        $statsPayload = [];
        foreach ($statsItems as $index => $item) {
            $en = $item->getTranslation('data', 'en', true);
            $statsPayload[$index] = [
                'id' => $item->id,
                'order' => $index + 5,
                'existing_icon' => $en['icon_path'] ?? null,
                'number' => [
                    'en' => "N{$item->id}",
                    'ar' => "ع{$item->id}",
                ],
                'label' => [
                    'en' => "Label {$item->id}",
                    'ar' => "عنوان {$item->id}",
                ],
            ];
            if ($index === 0) {
                $statsPayload[$index]['icon'] = $statIcon;
            }
        }

        $whereSection = PageSection::where('type', 'where_us')->first();
        $whereItems = $whereSection->items()->orderBy('order')->get();
        $wherePayload = [];
        foreach ($whereItems as $index => $item) {
            $en = $item->getTranslation('data', 'en', true);
            $wherePayload[$index] = [
                'id' => $item->id,
                'order' => $index + 1,
                'existing_image' => $en['image_path'] ?? null,
                'overlay' => [
                    'en' => "Overlay {$item->id}",
                    'ar' => "طبقة {$item->id}",
                ],
            ];
            if ($index === 0) {
                $wherePayload[$index]['image'] = $whereImage;
            }
        }

        $response = $this->actingAsAdmin()->put(route('admin.cms.home.update', ['lang' => 'en']), [
            'banner' => [
                'video' => $bannerVideo,
                'autoplay' => '1',
                'loop' => '1',
                'muted' => '0',
                'controls' => '1',
                'playsinline' => '1',
            ],
            'partners' => [
                'items' => array_values($partnersPayload),
            ],
            'about' => [
                'en' => [
                    'title' => 'About EN',
                    'desc' => 'English description',
                    'readmore_text' => 'Read EN',
                    'readmore_link' => '/en/about',
                ],
                'ar' => [
                    'title' => 'نبذة',
                    'desc' => 'وصف عربي',
                    'readmore_text' => 'اقرأ المزيد',
                    'readmore_link' => '/ar/about',
                ],
            ],
            'stats' => [
                'items' => array_values($statsPayload),
            ],
            'where_us' => [
                'title' => ['en' => 'Where EN', 'ar' => 'أين'],
                'brochure_text' => ['en' => 'Download EN', 'ar' => 'حمل بالعربي'],
                'brochure_icon' => $brochureIcon,
                'brochure_file' => $brochurePdf,
                'brochure_link' => 'https://example.com/brochure.pdf',
                'items' => array_values($wherePayload),
            ],
            'cta' => [
                'image' => $ctaImage,
                'overlay_image' => $ctaOverlay,
                'en' => [
                    'title' => 'CTA EN',
                    'text' => 'Call to action EN',
                    'link_text' => 'Contact EN',
                    'link_url' => '/contact-en',
                ],
                'ar' => [
                    'title' => 'CTA AR',
                    'text' => 'دعوة للعمل',
                    'link_text' => 'تواصل',
                    'link_url' => '/contact-ar',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.cms.home.edit', ['lang' => 'en']));
        $response->assertSessionHas('success');

        $bannerSection = PageSection::where('type', 'banner')->first();
        $bannerData = $bannerSection->getTranslation('section_data', 'en', true);
        $this->assertStringContainsString('cms/home/banner', $bannerData['video_path'] ?? '');
        $this->assertFileExists(public_path($bannerData['video_path']));
        $this->assertTrue((bool) ($bannerData['autoplay'] ?? false));
        $this->assertTrue((bool) ($bannerData['controls'] ?? false));

        $partnerSection->refresh();
        foreach ($partnerSection->items as $item) {
            $data = $item->getTranslation('data', 'en', true);
            $this->assertStringContainsString('Partner', $data['alt'] ?? '');
        }

        $statsSection->refresh();
        foreach ($statsSection->items as $item) {
            $data = $item->getTranslation('data', 'en', true);
            $this->assertStringContainsString('Label', $data['label'] ?? '');
        }

        $whereSection->refresh();
        $whereData = $whereSection->getTranslation('section_data', 'en', true);
        $this->assertEquals('Where EN', $whereData['title']);
        $this->assertStringContainsString('cms/home/where-us', $whereData['brochure']['icon_path'] ?? '');
        $this->assertFileExists(public_path($whereData['brochure']['icon_path']));
        $this->assertFileExists(public_path($whereData['brochure']['brochure_path']));

        $ctaSection = PageSection::where('type', 'cta')->first();
        $ctaData = $ctaSection->getTranslation('section_data', 'en', true);
        $this->assertEquals('CTA EN', $ctaData['title']);
        $this->assertFileExists(public_path($ctaData['image_path']));
        $this->assertFileExists(public_path($ctaData['overlay_image_path']));
    }

    public function test_whoweare_page_can_be_updated(): void
    {
        $bannerImage = UploadedFile::fake()->image('second-banner.png');
        $portImage = UploadedFile::fake()->image('port.png');

        $featuresSection = PageSection::where('type', 'who_we')->first();
        $items = $featuresSection->items()->orderBy('order')->get();
        $featuresPayload = [];
        foreach ($items as $index => $item) {
            $featuresPayload[$index] = [
                'id' => $item->id,
                'order' => $index + 1,
                'title' => [
                    'en' => "Feature {$item->id}",
                    'ar' => "ميزة {$item->id}",
                ],
                'text' => [
                    'en' => "Feature description {$item->id}",
                    'ar' => "وصف {$item->id}",
                ],
                'bullets' => [
                    'en' => "Point 1\nPoint 2",
                    'ar' => "نقطة 1\nنقطة 2",
                ],
            ];
        }

        $response = $this->actingAsAdmin()->put(route('admin.cms.whoweare.update', ['lang' => 'en']), [
            'banner' => ['image' => $bannerImage],
            'who_we' => [
                'en' => [
                    'title' => 'Who EN',
                    'description' => 'Who description EN',
                ],
                'ar' => [
                    'title' => 'من نحن',
                    'description' => 'وصف من نحن',
                ],
                'items' => array_values($featuresPayload),
            ],
            'port' => ['image' => $portImage],
        ]);

        $response->assertRedirect(route('admin.cms.whoweare.edit', ['lang' => 'en']));
        $response->assertSessionHas('success');

        $bannerSection = PageSection::where('type', 'second_banner')->first();
        $bannerData = $bannerSection->getTranslation('section_data', 'en', true);
        $this->assertFileExists(public_path($bannerData['image_path']));

        $featuresSection->refresh();
        $featuresData = $featuresSection->getTranslation('section_data', 'en', true);
        $this->assertEquals('Who EN', $featuresData['title']);
        foreach ($featuresSection->items as $item) {
            $data = $item->getTranslation('data', 'en', true);
            $this->assertEquals(['Point 1', 'Point 2'], $data['bullets'] ?? []);
        }

        $portSection = PageSection::where('type', 'port_image')->first();
        $portData = $portSection->getTranslation('section_data', 'en', true);
        $this->assertFileExists(public_path($portData['image_path']));
    }

    public function test_contact_page_can_be_updated(): void
    {
        $bannerImage = UploadedFile::fake()->image('contact-banner.png');
        $mapImage = UploadedFile::fake()->image('map.png');
        $bottomImage = UploadedFile::fake()->image('bottom.png');
        $adsImage1 = UploadedFile::fake()->image('ads1.png');
        $adsImage2 = UploadedFile::fake()->image('ads2.png');
        $screensImage1 = UploadedFile::fake()->image('screens1.png');
        $screensImage2 = UploadedFile::fake()->image('screens2.png');
        $createImage1 = UploadedFile::fake()->image('create1.png');
        $createImage2 = UploadedFile::fake()->image('create2.png');
        $faqImage1 = UploadedFile::fake()->image('faq1.png');
        $faqImage2 = UploadedFile::fake()->image('faq2.png');

        $forms = [
            'ads' => PageSection::where('type', 'contact_form_ads')->first(),
            'screens' => PageSection::where('type', 'contact_form_screens')->first(),
            'create' => PageSection::where('type', 'contact_form_create')->first(),
            'faq' => PageSection::where('type', 'contact_form_faq')->first(),
        ];

        $formPayload = [];
        foreach ($forms as $key => $section) {
            $dataEn = $section->getTranslation('section_data', 'en', true);
            $dataAr = $section->getTranslation('section_data', 'ar', true);
            $formPayload[$key] = [
                'card_image1' => null,
                'card_image2' => null,
                'en' => [
                    'card_text' => "Updated EN {$key}",
                    'modal_title' => "Modal EN {$key}",
                    'submit_text' => "Submit EN {$key}",
                    'labels' => array_map(fn () => 'Label EN', $dataEn['labels'] ?? []),
                    'radio' => array_map(fn () => 'Radio EN', $dataEn['radio'] ?? []),
                    'options' => array_map(fn ($values) => implode("\n", (array) $values), $dataEn['options'] ?? []),
                ],
                'ar' => [
                    'card_text' => "تحديث {$key}",
                    'modal_title' => "عنوان {$key}",
                    'submit_text' => "إرسال {$key}",
                    'labels' => array_map(fn () => 'حقل', $dataAr['labels'] ?? []),
                    'radio' => array_map(fn () => 'اختيار', $dataAr['radio'] ?? []),
                    'options' => array_map(fn ($values) => implode("\n", (array) $values), $dataAr['options'] ?? []),
                ],
            ];
        }

        $formPayload['ads']['card_image1'] = $adsImage1;
        $formPayload['ads']['card_image2'] = $adsImage2;
        $formPayload['screens']['card_image1'] = $screensImage1;
        $formPayload['screens']['card_image2'] = $screensImage2;
        $formPayload['create']['card_image1'] = $createImage1;
        $formPayload['create']['card_image2'] = $createImage2;
        $formPayload['faq']['card_image1'] = $faqImage1;
        $formPayload['faq']['card_image2'] = $faqImage2;

        $payload = [
            'banner' => ['image' => $bannerImage],
            'contact' => [
                'en' => ['title' => 'Contact EN', 'subtitle' => 'Subtitle EN'],
                'ar' => ['title' => 'تواصل', 'subtitle' => 'نص فرعي'],
            ],
            'map' => [
                'background_image' => $mapImage,
                'en' => [
                    'title' => 'Map EN',
                    'address' => 'Address EN',
                    'phone_label' => 'Phone EN',
                    'whatsapp_label' => 'WhatsApp EN',
                ],
                'ar' => [
                    'title' => 'خريطة',
                    'address' => 'عنوان',
                    'phone_label' => 'هاتف',
                    'whatsapp_label' => 'واتساب',
                ],
            ],
            'bottom' => ['image' => $bottomImage],
            'contact_forms' => [],
        ];

        foreach ($formPayload as $key => $form) {
            $payload['contact_forms'][$key] = [
                'card_image1' => $form['card_image1'],
                'card_image2' => $form['card_image2'],
                'en' => $form['en'],
                'ar' => $form['ar'],
            ];
        }

        $response = $this->actingAsAdmin()->put(route('admin.cms.contact.update', ['lang' => 'en']), $payload);

        $response->assertRedirect(route('admin.cms.contact.edit', ['lang' => 'en']));
        $response->assertSessionHas('success');

        $bannerSection = PageSection::where('type', 'second_banner')->first();
        $bannerData = $bannerSection->getTranslation('section_data', 'en', true);
        $this->assertFileExists(public_path($bannerData['image_path']));

        $mapSection = PageSection::where('type', 'map')->first();
        $mapData = $mapSection->getTranslation('section_data', 'en', true);
        $this->assertEquals('Map EN', $mapData['title']);
        $this->assertFileExists(public_path($mapData['background_image_path']));

        $adsSection = $forms['ads']->fresh();
        $adsData = $adsSection->getTranslation('section_data', 'en', true);
        $this->assertEquals('Updated EN ads', $adsData['card_text']);
        $this->assertFileExists(public_path($adsData['card_image1']));
        $this->assertFileExists(public_path($adsData['card_image2']));

        $faqSection = $forms['faq']->fresh();
        $faqData = $faqSection->getTranslation('section_data', 'en', true);
        $this->assertEquals('Updated EN faq', $faqData['card_text']);
    }
}
