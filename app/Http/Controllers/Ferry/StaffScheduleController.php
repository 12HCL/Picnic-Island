<?php

namespace App\Http\Controllers\Ferry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ferry\StoreFerryScheduleRequest;
use App\Http\Requests\Ferry\UpdateFerryScheduleRequest;
use App\Models\FerryRoute;
use App\Models\FerrySchedule;
use App\Models\Vessel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Module 3 — the operator's timetable, and scheduling. UC-13.
 *
 * The visitor-facing list in FerryScheduleController shows only future sailings that are
 * still open. This one shows every sailing in every status, because an operator needs to see
 * what departed and what was cancelled, not only what is on sale.
 *
 * SCOPE NOTE. BUILD_CONTRACT.md §3 gives module 3 only `GET /staff/ferry/schedules`, so
 * create, edit and cancel are an extension of that table rather than a line from it. They are
 * here because REQUIREMENTS.md, role 3, asks the ferry operator to "manage ferry schedules and
 * availability" and UC-13 documents the flows in detail — read-only, the module could not
 * satisfy either, and a sailing could only ever be created by a seeder. Recorded on the daily
 * log so the contract can be corrected rather than silently diverged from.
 */
class StaffScheduleController extends Controller
{
    /**
     * GET /staff/ferry/schedules.
     */
    public function index(Request $request): View
    {
        // Same reason as the public timetable: $request->date() throws on input that
        // filled() happily accepts, so ?date=abc was a 500 rather than an unfiltered list.
        $request->validate([
            'status' => ['nullable', 'in:scheduled,departed,cancelled'],
            'date' => ['nullable', 'date'],
        ]);

        $schedules = FerrySchedule::query()
            ->with('route', 'vessel')
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->when(
                $request->filled('date'),
                fn ($query) => $query->whereDate('departure_date', $request->date('date')),
            )
            ->orderByDesc('departure_date')
            ->orderBy('departure_time')
            ->paginate(20)
            ->withQueryString();

        return view('ferry.staff.schedules.index', ['schedules' => $schedules]);
    }

    /**
     * GET /staff/ferry/schedules/create — UC-13 main flow, step 3.
     */
    public function create(): View
    {
        return view('ferry.staff.schedules.create', [
            'schedule' => new FerrySchedule(),
        ] + $this->formOptions());
    }

    /**
     * POST /staff/ferry/schedules.
     */
    public function store(StoreFerryScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($clash = $this->vesselClash($data)) {
            return back()->withInput()->with('error', $clash);
        }

        $schedule = FerrySchedule::create($data + ['status' => 'scheduled']);

        return redirect()
            ->route('ferry.staff.schedules.index')
            ->with('success', 'Sailing scheduled for '
                .$schedule->departure_date->format('j M Y').'.');
    }

    /**
     * GET /staff/ferry/schedules/{schedule}/edit.
     */
    public function edit(FerrySchedule $schedule): View
    {
        return view('ferry.staff.schedules.edit', [
            'schedule' => $schedule,
        ] + $this->formOptions());
    }

    /**
     * PUT /staff/ferry/schedules/{schedule}.
     */
    public function update(UpdateFerryScheduleRequest $request, FerrySchedule $schedule): RedirectResponse
    {
        $data = $request->validated();

        if ($clash = $this->vesselClash($data, $schedule)) {
            return back()->withInput()->with('error', $clash);
        }

        // Reassigning to a smaller vessel cannot strand passengers who already hold a pass.
        $capacity = Vessel::whereKey($data['vessel_id'])->value('capacity');

        if ($capacity !== null && $schedule->seats_taken > $capacity) {
            return back()->withInput()->with(
                'error',
                "That vessel carries {$capacity} passengers but {$schedule->seats_taken} "
                    .'passes have already been issued for this sailing.',
            );
        }

        $schedule->update($data);

        return redirect()
            ->route('ferry.staff.schedules.index')
            ->with('success', 'Sailing updated.');
    }

    /**
     * POST /staff/ferry/schedules/{schedule}/cancel — UC-13 A2, and E1.
     *
     * Cancelling is a status change, never a delete: the sailing is referenced by every pass
     * issued against it, and ferry_tickets.ferry_schedule_id is ON DELETE RESTRICT, so the
     * database would refuse a delete anyway.
     */
    public function cancel(FerrySchedule $schedule): RedirectResponse
    {
        // UC-13 E1: a sailing with passengers on it cannot simply be cancelled here. Those
        // tickets have to be dealt with first, which is a decision, not a side effect.
        $issued = $schedule->tickets()->where('status', 'issued')->count();

        if ($issued > 0) {
            return back()->with(
                'error',
                "This sailing cannot be cancelled: {$issued} pass(es) have been issued to "
                    .'passengers. Cancel those first.',
            );
        }

        if ($schedule->status === 'cancelled') {
            return back()->with('error', 'That sailing is already cancelled.');
        }

        $schedule->update(['status' => 'cancelled']);

        return back()->with('success', 'Sailing cancelled.');
    }

    /**
     * UC-13 E2: one vessel cannot be on two sailings at the same moment, on this route or
     * any other. The unique index covers only (route, date, time), so this has no database
     * equivalent and lives here.
     *
     * A crossing occupies its vessel for the route's duration, so an overlap is any sailing
     * of the same vessel that day whose window contains this departure, or whose departure
     * falls inside this one's window.
     */
    private function vesselClash(array $data, ?FerrySchedule $ignore = null): ?string
    {
        $duration = FerryRoute::whereKey($data['ferry_route_id'])->value('duration_minutes') ?? 0;

        $start = \Illuminate\Support\Carbon::parse(
            $data['departure_date'].' '.$data['departure_time'],
        );
        $end = $start->copy()->addMinutes($duration);

        $sameDay = FerrySchedule::query()
            ->with('route')
            ->where('vessel_id', $data['vessel_id'])
            ->where('status', '!=', 'cancelled')
            ->whereDate('departure_date', $data['departure_date'])
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->get();

        foreach ($sameDay as $other) {
            $otherStart = \Illuminate\Support\Carbon::parse(
                $other->departure_date->toDateString().' '.$other->departure_time,
            );
            $otherEnd = $otherStart->copy()->addMinutes($other->route->duration_minutes);

            if ($start < $otherEnd && $otherStart < $end) {
                return 'That vessel is already sailing '
                    .$other->route->origin.' to '.$other->route->destination
                    .' at '.$otherStart->format('H:i').' that day.';
            }
        }

        return null;
    }

    /**
     * @return array{routes: \Illuminate\Support\Collection, vessels: \Illuminate\Support\Collection}
     */
    private function formOptions(): array
    {
        return [
            'routes' => FerryRoute::orderBy('origin')->orderBy('destination')->get(),
            // Only active vessels can be assigned; maintenance and retired are excluded here
            // as well as in the form request.
            'vessels' => Vessel::where('status', 'active')->orderBy('name')->get(),
        ];
    }
}
