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

            // Contact forms: Ads subscribe
            PageSection::updateOrCreate(
                ['page_id' => $page->id, 'type' => 'contact_form_ads'],
                [
                    'order' => 5,
                    'is_active' => true,
                    'section_data' => [
                        'en' => [
                            'card_image1' => 'img/pc.png',
                            'card_image2' => 'img/pc2.png',
                            'card_text'   => 'Subscribe for ad campaigns that fit your goals',
                            'modal_title' => 'Subscribe for Ad Campaigns',
                            'submit_text' => 'Send',
                            'labels' => [
                                'name' => 'Full name / Company',
                                'phone' => 'Mobile',
                                'email' => 'Email',
                                'ad_production' => 'Do you need us to produce the ad?',
                                'branches_count' => 'How many branches?',
                                'duration' => 'Campaign duration',
                                'business_type' => 'Business type',
                                'target_customers' => 'Target customers',
                                'places' => 'Preferred locations',
                                'details' => 'Additional details',
                            ],
                            'radio' => [
                                'produce' => 'Produce the ad for me',
                                'have' => 'I already have an ad',
                            ],
                            'options' => [
                                'duration' => [
                                    'Select duration', 'Week', 'Month', 'Quarter', '3 Months', '6 Months', 'Year'
                                ],
                                'target_customers' => [
                                    'Select range', '50,000 - 100,000', '100,000 - 500,000', '500,000 - 800,000', '800,000+'
                                ],
                                'places' => [
                                    'Airport', 'Mall', 'Hospital', 'University', 'Main Roads'
                                ],
                            ],
                        ],
                        'ar' => [
                            'card_image1' => 'img/pc.png',
                            'card_image2' => 'img/pc2.png',
                            'card_text'   => 'اشترك في حملات الإعلانات المناسبة لأهدافك',
                            'modal_title' => 'الاشتراك في حملات الإعلانات',
                            'submit_text' => 'إرسال',
                            'labels' => [
                                'name' => 'الاسم الكامل / الشركة',
                                'phone' => 'الجوال',
                                'email' => 'البريد الإلكتروني',
                                'ad_production' => 'هل تحتاج منا إنتاج الإعلان؟',
                                'branches_count' => 'عدد الفروع',
                                'duration' => 'مدة الحملة',
                                'business_type' => 'نوع النشاط',
                                'target_customers' => 'العملاء المستهدفون',
                                'places' => 'الأماكن المفضلة',
                                'details' => 'تفاصيل إضافية',
                            ],
                            'radio' => [
                                'produce' => 'نعم، نحتاج إنتاج الإعلان',
                                'have' => 'لدي إعلان جاهز',
                            ],
                            'options' => [
                                'duration' => [
                                    'اختر المدة', 'أسبوع', 'شهر', 'ربع سنة', '3 أشهر', '6 أشهر', 'سنة'
                                ],
                                'target_customers' => [
                                    'اختر النطاق', '50,000 - 100,000', '100,000 - 500,000', '500,000 - 800,000', '800,000+'
                                ],
                                'places' => [
                                    'المطار', 'المول', 'المستشفى', 'الجامعة', 'الطرق الرئيسية'
                                ],
                            ],
                        ],
                    ],
                ]
            );

            // Contact forms: Screens subscribe
            PageSection::updateOrCreate(
                ['page_id' => $page->id, 'type' => 'contact_form_screens'],
                [
                    'order' => 6,
                    'is_active' => true,
                    'section_data' => [
                        'en' => [
                            'card_image1' => 'img/25.png',
                            'card_image2' => 'img/27.png',
                            'card_text'   => 'Subscribe to advertise on our screens',
                            'modal_title' => 'Subscribe for Screens Advertising',
                            'submit_text' => 'Send',
                            'labels' => [
                                'name' => 'Full name / Company',
                                'phone' => 'Mobile',
                                'email' => 'Email',
                                'screens_count' => 'How many screens?',
                                'have_screens' => 'Do you have existing screens?',
                                'branches_count' => 'How many branches?',
                                'daily_customers_avg' => 'Average daily customers',
                                'details' => 'Additional details',
                            ],
                            'radio' => [
                                'have_screens_yes' => 'Yes, we have screens',
                                'have_screens_no' => 'No, we don\'t have screens',
                            ],
                            'options' => [
                                'daily_customers_avg' => [
                                    'Select range', '50,000 - 100,000', '100,000 - 500,000', '500,000 - 800,000', '800,000+'
                                ],
                            ],
                        ],
                        'ar' => [
                            'card_image1' => 'img/25.png',
                            'card_image2' => 'img/27.png',
                            'card_text'   => 'اشترك للإعلان على شاشاتنا',
                            'modal_title' => 'الاشتراك للإعلان على الشاشات',
                            'submit_text' => 'إرسال',
                            'labels' => [
                                'name' => 'الاسم الكامل / الشركة',
                                'phone' => 'الجوال',
                                'email' => 'البريد الإلكتروني',
                                'screens_count' => 'عدد الشاشات',
                                'have_screens' => 'هل لديك شاشات؟',
                                'branches_count' => 'عدد الفروع',
                                'daily_customers_avg' => 'متوسط العملاء اليومي',
                                'details' => 'تفاصيل إضافية',
                            ],
                            'radio' => [
                                'have_screens_yes' => 'نعم، لدينا شاشات',
                                'have_screens_no' => 'لا، لا توجد شاشات',
                            ],
                            'options' => [
                                'daily_customers_avg' => [
                                    'اختر النطاق', '50,000 - 100,000', '100,000 - 500,000', '500,000 - 800,000', '800,000+'
                                ],
                            ],
                        ],
                    ],
                ]
            );

            // Contact forms: Ad creating subscribe
            PageSection::updateOrCreate(
                ['page_id' => $page->id, 'type' => 'contact_form_create'],
                [
                    'order' => 7,
                    'is_active' => true,
                    'section_data' => [
                        'en' => [
                            'card_image1' => 'img/screen.png',
                            'card_image2' => 'img/screen2.png',
                            'card_text'   => 'Request ad creation tailored to your brand',
                            'modal_title' => 'Ad Creation Request',
                            'submit_text' => 'Send',
                            'labels' => [
                                'name' => 'Full name / Company',
                                'phone' => 'Mobile',
                                'email' => 'Email',
                                'business_type' => 'Business type',
                                'details' => 'Describe your request',
                            ],
                        ],
                        'ar' => [
                            'card_image1' => 'img/screen.png',
                            'card_image2' => 'img/screen2.png',
                            'card_text'   => 'اطلب إنشاء إعلان مناسب لهويتك',
                            'modal_title' => 'طلب إنشاء إعلان',
                            'submit_text' => 'إرسال',
                            'labels' => [
                                'name' => 'الاسم الكامل / الشركة',
                                'phone' => 'الجوال',
                                'email' => 'البريد الإلكتروني',
                                'business_type' => 'نوع النشاط',
                                'details' => 'صف طلبك',
                            ],
                        ],
                    ],
                ]
            );

            // Contact forms: FAQs
            PageSection::updateOrCreate(
                ['page_id' => $page->id, 'type' => 'contact_form_faq'],
                [
                    'order' => 8,
                    'is_active' => true,
                    'section_data' => [
                        'en' => [
                            'card_image1' => 'img/faqs.png',
                            'card_image2' => 'img/faqs2.png',
                            'card_text'   => 'Ask about our services and processes',
                            'modal_title' => 'Frequently Asked Questions',
                            'submit_text' => 'Send',
                            'labels' => [
                                'name' => 'Full name / Company',
                                'phone' => 'Mobile',
                                'email' => 'Email',
                                'question' => 'Your question',
                            ],
                        ],
                        'ar' => [
                            'card_image1' => 'img/faqs.png',
                            'card_image2' => 'img/faqs2.png',
                            'card_text'   => 'اسأل عن خدماتنا وآليات العمل',
                            'modal_title' => 'الأسئلة الشائعة',
                            'submit_text' => 'إرسال',
                            'labels' => [
                                'name' => 'الاسم الكامل / الشركة',
                                'phone' => 'الجوال',
                                'email' => 'البريد الإلكتروني',
                                'question' => 'سؤالك',
                            ],
                        ],
                    ],
                ]
            );

        });
    }
}
