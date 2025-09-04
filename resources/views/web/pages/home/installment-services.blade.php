<section class="services_section custom-section">
    <div class="container">
        <h2 class="custom_title text-center">
            <span> خدمات التوكيل </span>
        </h2>

        <div class="flex flex-wrap w-full -mx-2">
            @foreach ($installment_programs as $item)
            <div class="w-full md:w-1/2 mb-4 lg:mb-0 lg:w-1/3">
                <a href="#" class="services_section_card zoomIn">
                    <div class="services_section_card_header w-full">
                        <img class="w-full h-full object-cover" src="{{ $item->card_image_path }}" alt="{{ $item->name }}" />
                    </div>
                    <div class="services_section_card_body">
                        <h4 class="services_section_card_body_title">
                            {{ $item->name }}
                        </h4>
                        <p class="services_section_card_body_desc">
                            {{ $item->description }}
                        </p>
                        <button class="services_section_card_body_button">
                            اعرض خطط التقسيط
                        </button>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
