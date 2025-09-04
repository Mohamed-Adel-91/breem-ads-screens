<div class="container">
    <div class="Tobtitle">
        <h2>مميزات السيارة</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        @foreach ($car->infoFeatures as $feature)
            <div class="text-center">
                <div class="bg-gray-100 w-full  flex items-center justify-center box_iamge">
                    <img class="w-full object-cover" src="{{ $feature->image_path }}" alt="{{ $feature->title }}">
                </div>
                <h3 class="text-red-600 font-bold text-lg mt-4">{{ $feature->title }}</h3>
                <p class="text-gray-700 mt-2 text-justify">
                    {{ $feature->description }}
                </p>
            </div>
        @endforeach
    </div>
</div>
