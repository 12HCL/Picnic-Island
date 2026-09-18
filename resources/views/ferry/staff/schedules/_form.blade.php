{{--
    Shared by create and edit. $schedule is an empty FerrySchedule on create, so old()
    falls back to the model's own values on edit and to the column defaults on create.
--}}

<div class="row g-3">
    <div class="col-md-6">
        <label for="ferry_route_id" class="form-label small text-muted">Crossing</label>
        <select name="ferry_route_id" id="ferry_route_id" required
                class="form-select @error('ferry_route_id') is-invalid @enderror">
            @foreach ($routes as $route)
                <option value="{{ $route->id }}"
                    @selected(old('ferry_route_id', $schedule->ferry_route_id) == $route->id)>
                    {{ $route->origin }} to {{ $route->destination }}
                    ({{ $route->duration_minutes }} min)
                </option>
            @endforeach
        </select>
        @error('ferry_route_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="vessel_id" class="form-label small text-muted">Vessel</label>
        <select name="vessel_id" id="vessel_id" required
                class="form-select @error('vessel_id') is-invalid @enderror">
            @foreach ($vessels as $vessel)
                <option value="{{ $vessel->id }}"
                    @selected(old('vessel_id', $schedule->vessel_id) == $vessel->id)>
                    {{ $vessel->name }} — carries {{ $vessel->capacity }}
                </option>
            @endforeach
        </select>
        @error('vessel_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">
            Only active vessels are listed. Capacity belongs to the vessel, so it is never
            typed in here — BR-02 compares seats taken against whichever boat is assigned.
        </div>
    </div>

    <div class="col-md-6">
        <label for="departure_date" class="form-label small text-muted">Departure date</label>
        <input type="date" name="departure_date" id="departure_date" required
               class="form-control @error('departure_date') is-invalid @enderror"
               value="{{ old('departure_date', $schedule->departure_date?->toDateString()) }}">
        @error('departure_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="departure_time" class="form-label small text-muted">Departure time</label>
        <input type="time" name="departure_time" id="departure_time" required
               class="form-control @error('departure_time') is-invalid @enderror"
               value="{{ old('departure_time', $schedule->departure_time ? substr($schedule->departure_time, 0, 5) : '') }}">
        @error('departure_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @if ($schedule->exists)
        <div class="col-md-6">
            <label for="status" class="form-label small text-muted">Status</label>
            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                @foreach (['scheduled' => 'Scheduled', 'departed' => 'Departed', 'cancelled' => 'Cancelled'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $schedule->status) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6 d-flex align-items-end">
            <p class="form-text mb-0">
                {{ $schedule->seats_taken }} seat(s) already taken on this sailing. Seats are
                counted as passes are issued and cannot be edited by hand.
            </p>
        </div>
    @endif
</div>
