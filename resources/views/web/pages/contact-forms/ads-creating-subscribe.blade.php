@php
    $formSection = $sections->firstWhere('type', 'contact_form_create') ?? null;
    $current = $formSection?->getTranslation('section_data', app()->getLocale(), true) ?? [];
    $fallback = $formSection?->getTranslation('section_data', config('app.fallback_locale'), true) ?? [];
    if (!is_array($current)) { $current = []; }
    if (!is_array($fallback)) { $fallback = []; }
    $data = array_replace($fallback, $current);
@endphp

@if ($formSection)
<div class="col-12 col-md-6 col-lg-3">
    <div class="contact_box" data-bs-toggle="modal" data-bs-target="#advertiseModal3">
        <div class="image">
            <img class="imageone" src="{{ asset(media_path($data['card_image1']) ?? 'img/screen.png') }}" alt="">
            <img class="imagetwo" src="{{ asset(media_path($data['card_image2']) ?? 'img/screen2.png') }}" alt="">
        </div>
        <div class="desc">
            <p class="text-center">{{ $data['card_text'] ?? '' }}</p>
        </div>
    </div>
    <div class="modal fade" id="advertiseModal3" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content p-4 position-relative">

                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"
                    aria-label="Close" style="z-index: 30;">X</button>

                <div class="modal-header border-0">
                    <h5 class="modal-title w-100 text-center text-teal fw-bold">{{ $data['modal_title'] ?? '' }}</h5>
                </div>

                <div class="modal-body">
                    <form method="POST" action="{{ route('web.contact.submit', ['lang' => app()->getLocale(), 'type' => 'create']) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ data_get($data, 'labels.name') }} <span class="text-danger">*</span></label>
                            <input name="name" type="text" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ data_get($data, 'labels.phone') }} <span class="text-danger">*</span></label>
                            <input name="phone" type="tel" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ data_get($data, 'labels.email') }} <span class="text-danger">*</span></label>
                            <input name="email" type="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ data_get($data, 'labels.business_type') }}</label>
                            <input name="business_type" type="text" class="form-control">
                        </div>
                        <div class="mb-3 d-flex flex-column">
                            <label class="form-label">{{ data_get($data, 'labels.details') }}</label>
                            <textarea id="w3review" name="details" rows="4" cols="50"></textarea>
                        </div>
                        <div class="text-center mt-4">
                            <button type="submit" class="btn px-5" style="background:#41A8A6; color:white; border-radius:10px;">{{ $data['submit_text'] ?? 'Send' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

