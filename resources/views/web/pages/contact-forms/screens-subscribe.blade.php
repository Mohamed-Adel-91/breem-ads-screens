@php
    $formSection = $sections->firstWhere('type', 'contact_form_screens') ?? null;
    $current = $formSection?->getTranslation('section_data', app()->getLocale(), true) ?? [];
    $fallback = $formSection?->getTranslation('section_data', config('app.fallback_locale'), true) ?? [];
    if (!is_array($current)) { $current = []; }
    if (!is_array($fallback)) { $fallback = []; }
    $data = array_replace($fallback, $current);
@endphp

@if ($formSection)
<div class="col-12 col-md-6 col-lg-3">
    <div class="contact_box" data-bs-toggle="modal" data-bs-target="#advertiseModal2">
        <div class="image">
            <img class="imageone" src="{{ asset(media_path($data['card_image1']) ?? 'img/25.png') }}" alt="">
            <img class="imagetwo" src="{{ asset(media_path($data['card_image2']) ?? 'img/27.png') }}" alt="">
        </div>
        <div class="desc">
            <p class="text-center">{{ $data['card_text'] ?? '' }}</p>
        </div>
    </div>
    <div class="modal fade" id="advertiseModal2" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content p-4 position-relative">

                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"
                    aria-label="Close" style="z-index: 30;">X</button>

                <div class="modal-header border-0">
                    <h5 class="modal-title w-100 text-center text-teal fw-bold">{{ $data['modal_title'] ?? '' }}</h5>
                </div>

                <div class="modal-body">
                    <form method="POST" action="{{ route('web.contact.submit', ['lang' => app()->getLocale(), 'type' => 'screens']) }}">
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
                            <label class="form-label">{{ data_get($data, 'labels.screens_count') }}</label>
                            <input name="screens_count" type="text" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ data_get($data, 'labels.have_screens') }}</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="have_screens" value="yes" id="soption1">
                                    <label class="form-check-label" for="soption1">{{ data_get($data, 'radio.have_screens_yes') }}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="have_screens" value="no" id="soption2">
                                    <label class="form-check-label" for="soption2">{{ data_get($data, 'radio.have_screens_no') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ data_get($data, 'labels.branches_count') }} <span class="text-danger">*</span></label>
                                <input name="branches_count" type="number" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ data_get($data, 'labels.daily_customers_avg') }} <span class="text-danger">*</span></label>
                                <select name="daily_customers_avg" class="form-select">
                                    @foreach (data_get($data, 'options.daily_customers_avg', []) as $opt)
                                        <option>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </div>
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

