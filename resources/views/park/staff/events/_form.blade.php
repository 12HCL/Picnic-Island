{{--
    Shared by create and edit. $event is an empty ParkEvent on create, possibly prefilled
    from the activity's default_capacity and base_price.
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
    <div class="col-md-6">
        <label for="park_activity_id" class="form-label small text-muted">Activity</label>
        <select name="park_activity_id" id="park_activity_id"
                class="form-select @error('park_activity_id') is-invalid @enderror" required>
            <option value="">-- Choose an activity --</option>
            @foreach ($activities as $activity)
                <option value="{{ $activity->id }}"
                    data-capacity="{{ $activity->default_capacity }}"
                    data-price="{{ $activity->base_price }}"
                    {{ (int) old('park_activity_id', $event->park_activity_id) === $activity->id ? 'selected' : '' }}>
                    {{ $activity->name }} ({{ ucfirst(str_replace('_', ' ', $activity->type)) }})
                </option>
            @endforeach
        </select>
        @error('park_activity_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="event_date" class="form-label small text-muted">Date</label>
        <input type="date" name="event_date" id="event_date" required
               class="form-control @error('event_date') is-invalid @enderror"
               value="{{ old('event_date', $event->event_date?->toDateString()) }}">
        @error('event_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="start_time" class="form-label small text-muted">Start time</label>
        <input type="time" name="start_time" id="start_time" required
               class="form-control @error('start_time') is-invalid @enderror"
               value="{{ old('start_time', $event->start_time ? \Illuminate\Support\Carbon::parse($event->start_time)->format('H:i') : '') }}">
        @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="capacity" class="form-label small text-muted">Capacity</label>
        <input type="number" name="capacity" id="capacity" min="1" max="65535" required
               class="form-control @error('capacity') is-invalid @enderror"
               value="{{ old('capacity', $event->capacity) }}">
        @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if ($event->exists && $event->seats_taken > 0)
            <div class="form-text">{{ $event->seats_taken }} already sold — cannot go below that.</div>
        @endif
    </div>

    <div class="col-md-3">
        <label for="price" class="form-label small text-muted">Price (MVR)</label>
        <input type="number" name="price" id="price" step="0.01" min="0" required
               class="form-control @error('price') is-invalid @enderror"
               value="{{ old('price', $event->price) }}">
        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Does not reprice tickets already sold.</div>
    </div>

    @if ($event->exists)
        <div class="col-md-3">
            <label for="status" class="form-label small text-muted">Status</label>
            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (['scheduled' => 'Scheduled', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                    <option value="{{ $value }}" {{ old('status', $event->status) === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endif
</div>

@unless ($event->exists)
    {{--
        Copies the catalogue defaults across when an activity is picked, which is what
        default_capacity and base_price are for. Staff can still override either.
    --}}
    <script>
        document.getElementById('park_activity_id').addEventListener('change', function () {
            const chosen = this.options[this.selectedIndex];
            const capacity = document.getElementById('capacity');
            const price = document.getElementById('price');

            if (!chosen.value) return;
            if (!capacity.value) capacity.value = chosen.dataset.capacity || '';
            if (!price.value) price.value = chosen.dataset.price || '';
        });
    </script>
@endunless
