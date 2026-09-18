{{--
    Shared by create and edit. $promotion is an empty Promotion on create, so old()
    falls back to the model's own values on edit and to the column defaults on create.
--}}

@if ($errors->any())
    <div class="alert alert-danger">
        <p class="mb-1 fw-semibold">Please correct the following:</p>
        <ul class="mb-0 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-8">
        <label for="title" class="form-label small text-muted">Title</label>
        <input type="text" name="title" id="title" maxlength="150" required
               class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $promotion->title) }}">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">The headline on the banner, so keep it short.</div>
    </div>

    <div class="col-md-4">
        <label for="module" class="form-label small text-muted">Module</label>
        <select name="module" id="module" class="form-select @error('module') is-invalid @enderror">
            @foreach (['general' => 'General', 'hotel' => 'Hotel', 'ferry' => 'Ferry', 'park' => 'Theme park'] as $value => $label)
                <option value="{{ $value }}" {{ old('module', $promotion->module ?? 'general') === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('module')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Which part of the island this promotes.</div>
    </div>

    <div class="col-12">
        <label for="body" class="form-label small text-muted">Body</label>
        <textarea name="body" id="body" rows="4" maxlength="5000"
                  class="form-control @error('body') is-invalid @enderror">{{ old('body', $promotion->body) }}</textarea>
        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">The detail shown under the headline. Optional.</div>
    </div>

    <div class="col-md-6">
        <label for="starts_on" class="form-label small text-muted">Starts on</label>
        <input type="date" name="starts_on" id="starts_on" required
               class="form-control @error('starts_on') is-invalid @enderror"
               value="{{ old('starts_on', $promotion->starts_on?->toDateString()) }}">
        @error('starts_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="ends_on" class="form-label small text-muted">Ends on</label>
        <input type="date" name="ends_on" id="ends_on" required
               class="form-control @error('ends_on') is-invalid @enderror"
               value="{{ old('ends_on', $promotion->ends_on?->toDateString()) }}">
        @error('ends_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Must not be before the start date.</div>
    </div>

    <div class="col-12">
        <label for="image" class="form-label small text-muted">Banner image</label>
        <input type="file" name="image" id="image" accept="image/*"
               class="form-control @error('image') is-invalid @enderror">
        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">
            Optional, 2 MB maximum.
            @if ($promotion->image_path)
                Leaving this empty keeps the current image; choosing a new one replaces it.
            @endif
        </div>

        @if ($promotion->image_path)
            <img src="{{ Storage::url($promotion->image_path) }}" alt=""
                 class="img-thumbnail mt-2" style="max-height: 8rem;">
        @endif
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input type="checkbox" name="is_published" id="is_published" value="1"
                   class="form-check-input"
                   {{ old('is_published', $promotion->is_published) ? 'checked' : '' }}>
            <label for="is_published" class="form-check-label">Publish this promotion</label>
        </div>
        <div class="form-text">
            Publishing is the intent to show it; the dates above decide when it actually
            appears. Unticking hides it without deleting the record.
        </div>
    </div>
</div>
