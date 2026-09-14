{{--
    Shared by create and edit. $activity is an empty ParkActivity on create, so old()
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
               value="{{ old('name', $activity->name) }}">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="type" class="form-label small text-muted">Type</label>
        <select name="type" id="type" class="form-select @error('type') is-invalid @enderror">
            @foreach (['ride' => 'Ride', 'show' => 'Show', 'beach_event' => 'Beach event'] as $value => $label)
                <option value="{{ $value }}" {{ old('type', $activity->type) === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Beach events are a type here, not a separate listing.</div>
    </div>

    <div class="col-12">
        <label for="description" class="form-label small text-muted">Description</label>
        <textarea name="description" id="description" rows="3" maxlength="2000"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $activity->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="map_location_id" class="form-label small text-muted">Map location</label>
        <select name="map_location_id" id="map_location_id"
                class="form-select @error('map_location_id') is-invalid @enderror">
            <option value="">-- Not placed on the map --</option>
            @foreach ($mapLocations as $location)
                <option value="{{ $location->id }}"
                    {{ (int) old('map_location_id', $activity->map_location_id) === $location->id ? 'selected' : '' }}>
                    {{ $location->name }}
                </option>
            @endforeach
        </select>
        @error('map_location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="default_capacity" class="form-label small text-muted">Default capacity</label>
        <input type="number" name="default_capacity" id="default_capacity" min="1" max="65535" required
               class="form-control @error('default_capacity') is-invalid @enderror"
               value="{{ old('default_capacity', $activity->default_capacity) }}">
        @error('default_capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Copied onto each new event.</div>
    </div>

    <div class="col-md-3">
        <label for="base_price" class="form-label small text-muted">Base price (MVR)</label>
        <input type="number" name="base_price" id="base_price" step="0.01" min="0" required
               class="form-control @error('base_price') is-invalid @enderror"
               value="{{ old('base_price', $activity->base_price) }}">
        @error('base_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Does not reprice existing events.</div>
    </div>

    <div class="col-12">
        <div class="form-check">
            <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input"
                   {{ old('is_active', $activity->is_active) ? 'checked' : '' }}>
            <label for="is_active" class="form-check-label">
                Active — visible to visitors
            </label>
        </div>
    </div>
</div>
