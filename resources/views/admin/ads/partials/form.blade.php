<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('Title (English)') }}</label>
        <input type="text" name="title[en]" class="form-control" value="{{ old('title.en', $ad->getTranslation('title', 'en', false)) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Title (Arabic)') }}</label>
        <input type="text" name="title[ar]" class="form-control" value="{{ old('title.ar', $ad->getTranslation('title', 'ar', false)) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Description (English)') }}</label>
        <textarea name="description[en]" class="form-control" rows="3">{{ old('description.en', $ad->getTranslation('description', 'en', false)) }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Description (Arabic)') }}</label>
        <textarea name="description[ar]" class="form-control" rows="3">{{ old('description.ar', $ad->getTranslation('description', 'ar', false)) }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Creative file') }}</label>
        <input type="file" name="creative" class="form-control">
        @if ($ad->file_path)
            <small class="form-text text-muted">
                <a href="{{ $ad->file_url }}" target="_blank">{{ __('Current file') }}</a>
            </small>
        @endif
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('Duration (seconds)') }}</label>
        <input type="number" name="duration_seconds" class="form-control" min="0" value="{{ old('duration_seconds', $ad->duration_seconds) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('Status') }}</label>
        <select name="status" class="form-control">
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', optional($ad->status)->value ?? array_key_first($statuses)) === $value)>
                    {{ ucfirst($label) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Owner') }}</label>
        <select name="created_by" class="form-control">
            @foreach ($owners as $owner)
                <option value="{{ $owner->id }}" @selected(old('created_by', $ad->created_by) == $owner->id)>
                    {{ $owner->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Approved by (optional)') }}</label>
        <select name="approved_by" class="form-control">
            <option value="">-- {{ __('Not set') }} --</option>
            @foreach ($owners as $owner)
                <option value="{{ $owner->id }}" @selected(old('approved_by', $ad->approved_by) == $owner->id)>
                    {{ $owner->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('Start date') }}</label>
        <input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional($ad->start_date)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('End date') }}</label>
        <input type="date" name="end_date" class="form-control" value="{{ old('end_date', optional($ad->end_date)->format('Y-m-d')) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Screens') }}</label>
        @php($selectedScreens = collect(old('screens', $ad->screens?->pluck('id')->all() ?? [])))
        <select name="screens[]" class="form-control" multiple size="5">
            @foreach ($screens as $screen)
                <option value="{{ $screen->id }}" @selected($selectedScreens->contains($screen->id))>
                    {{ $screen->code }} @if($screen->place) - {{ $screen->place->getTranslation('name', app()->getLocale()) }} @endif
                </option>
            @endforeach
        </select>
        <small class="text-muted">{{ __('Hold CTRL or CMD to select multiple screens.') }}</small>
    </div>
    <div class="col-12">
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">{{ __('Playback order per screen') }}</h6>
                <p class="text-muted">{{ __('Optional: set the play order for each screen (defaults to 0).') }}</p>
                <div class="row g-2">
                    @foreach ($screens as $screen)
                        <div class="col-md-3">
                            <label class="form-label">{{ $screen->code }}</label>
                            @php($pivot = $ad->screens?->firstWhere('id', $screen->id))
                            <input type="number" class="form-control" name="play_order[{{ $screen->id }}]" min="0" value="{{ old('play_order.' . $screen->id, optional($pivot?->pivot)->play_order ?? 0) }}">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
