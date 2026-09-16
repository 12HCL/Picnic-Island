{{--
    Shared by create and edit. $location is an empty MapLocation on create, so old()
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
        <label for="name" class="form-label small text-muted">Name</label>
        <input type="text" name="name" id="name" maxlength="150" required
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $location->name) }}">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Shown on the marker itself, so keep it short — "North Jetty", not a sentence.</div>
    </div>

    <div class="col-md-4">
        <label for="category" class="form-label small text-muted">Category</label>
        <select name="category" id="category" class="form-select @error('category') is-invalid @enderror">
            @foreach (['hotel' => 'Hotel', 'jetty' => 'Jetty', 'attraction' => 'Attraction', 'beach' => 'Beach', 'facility' => 'Facility'] as $value => $label)
                <option value="{{ $value }}" {{ old('category', $location->category) === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Sets the marker colour on the public map.</div>
    </div>

    <div class="col-12">
        <label for="description" class="form-label small text-muted">Description</label>
        <textarea name="description" id="description" rows="3" maxlength="2000"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $location->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Shown when a visitor selects this marker. Optional.</div>
    </div>

    <div class="col-12">
        <div class="alert alert-info small mb-0">
            <strong>Positions are percentages, not pixels.</strong>
            <code>0, 0</code> is the top-left corner of the map image and <code>100, 100</code>
            is the bottom-right. The centre is <code>50, 50</code>. Percentages are used so the
            markers stay on their landmark whether the map is shown full-width or on a phone.
        </div>
    </div>

    <div class="col-md-6">
        <label for="pos_x" class="form-label small text-muted">Horizontal position (% across)</label>
        <div class="input-group">
            <input type="number" name="pos_x" id="pos_x" step="0.01" min="0" max="100" required
                   class="form-control @error('pos_x') is-invalid @enderror"
                   value="{{ old('pos_x', $location->pos_x) }}">
            <span class="input-group-text">%</span>
            @error('pos_x')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text">0 is the left edge, 100 the right.</div>
    </div>

    <div class="col-md-6">
        <label for="pos_y" class="form-label small text-muted">Vertical position (% down)</label>
        <div class="input-group">
            <input type="number" name="pos_y" id="pos_y" step="0.01" min="0" max="100" required
                   class="form-control @error('pos_y') is-invalid @enderror"
                   value="{{ old('pos_y', $location->pos_y) }}">
            <span class="input-group-text">%</span>
            @error('pos_y')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-text">0 is the top edge, 100 the bottom.</div>
    </div>

    <div class="col-12">
        <div class="form-check form-switch">
            <input type="checkbox" name="is_visible" id="is_visible" value="1"
                   class="form-check-input"
                   {{ old('is_visible', $location->is_visible) ? 'checked' : '' }}>
            <label for="is_visible" class="form-check-label">Show this marker on the public map</label>
        </div>
        <div class="form-text">
            Unticking hides the marker from visitors without deleting the record, so any park
            activity placed here keeps its reference.
        </div>
    </div>
</div>
