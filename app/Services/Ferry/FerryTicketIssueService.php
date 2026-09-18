<?php

namespace App\Services\Ferry;

use App\Models\FerrySchedule;
use App\Models\FerryTicket;
use App\Models\User;
use App\Services\Hotel\HotelBookingGateway;
use App\Services\Payment\PaymentService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Module 3 — the one place a ferry ticket is issued.
 *
 * Both channels the brief asks for come through here: an online purchase by the visitor
 * themselves (UC-05) and counter issuance by a ferry operator (UC-14). They differ only in
 * who is buying and whether an operator is recorded, so the two rules that matter are
 * written once rather than once per controller.
 *
 * BR-01: a ferry ticket may only be issued to a passenger holding a hotel booking with
 *        status confirmed or checked_in whose date range covers the sailing date.
 * BR-02: a ticket may not be issued when ferry_schedules.seats_taken >= vessels.capacity.
 *
 * Both are check-then-act races, which is why this is one transaction rather than five
 * statements. Two visitors buying the last seat at the same moment would both read
 * seats_taken = 119 against a capacity of 120, both decide there is room, and both write
 * 120 — overselling the sailing with no error anywhere. lockForUpdate() on the schedule row
 * makes the second read wait for the first to commit, so it sees 120 and is refused.
 *
 * BR-01 has the same shape and it is the more serious of the two: without the re-check under
 * a lock, a hotel booking can be cancelled between the eligibility read on the booking page
 * and the insert here, and a ticket is issued against a booking that no longer authorises
 * it. HotelBookingGateway::assertAuthorises() takes its own lockForUpdate() on the
 * hotel_bookings row and must be called inside this transaction for that to mean anything.
 *
 * MASTER_SCHEMA.md §11: a ferry_tickets row is written only at payment confirmation. There
 * is no pending_payment state to create a ticket into first, so the ticket, the seat
 * increment and the payments row are one atomic unit. If the payment fails, no ticket exists
 * and no seat is consumed.
 */
class FerryTicketIssueService
{
    public function __construct(
        private readonly HotelBookingGateway $bookings,
        private readonly PaymentService $payments,
    ) {
    }

    /**
     * Issue one ferry ticket for `$schedule` to `$passenger` and take payment for it.
     *
     * @param  User|null  $operator  The ferry operator, for a counter sale (UC-14).
     *                               Null for an online purchase, which leaves issued_by
     *                               and issued_at null — that difference is the only thing
     *                               distinguishing the two channels on the row itself.
     *
     * @throws DomainException when the ticket cannot be issued, with a message fit to show.
     */
    public function issue(
        FerrySchedule $schedule,
        int $hotelBookingId,
        User $passenger,
        ?User $operator,
        string $method,
    ): FerryTicket {
        return DB::transaction(function () use ($schedule, $hotelBookingId, $passenger, $operator, $method) {
            // Re-read under a write lock. The $schedule passed in was read outside this
            // transaction and its seats_taken may already be stale — every decision below
            // is made against $locked, never against the argument.
            $locked = FerrySchedule::whereKey($schedule->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $locked->load('route', 'vessel');

            if ($locked->status !== 'scheduled') {
                throw new DomainException('This sailing is no longer open for booking.');
            }

            if ($locked->departure_date->isPast() && ! $locked->departure_date->isToday()) {
                throw new DomainException('This sailing has already departed.');
            }

            // BR-01, re-checked at write time under its own lock. The booking page already
            // asked the same question, but that answer is only as fresh as the page.
            $booking = $this->authorisingBooking(
                $hotelBookingId,
                $passenger,
                $locked->departure_date->toDateString(),
            );

            // BR-02. Capacity is read from the vessel, never stored on the sailing.
            if ($locked->seats_taken >= $locked->vessel->capacity) {
                throw new DomainException('This sailing is full. Please choose another crossing.');
            }

            $ticket = FerryTicket::create([
                'user_id' => $passenger->id,
                'ferry_schedule_id' => $locked->id,
                // BR-01 on the row itself. This column is NOT NULL, so a ticket cannot
                // physically exist in the database without the booking that authorised it.
                'hotel_booking_id' => $booking->id,
                'reference' => $this->nextReference(),
                // Copied from the route, not joined at read time: repricing a crossing must
                // never rewrite what a past passenger was charged.
                'fare' => $locked->route->base_fare,
                'status' => 'issued',
                'issued_by' => $operator?->id,
                'issued_at' => $operator !== null ? now() : null,
            ]);

            $locked->increment('seats_taken');

            // Nested as a savepoint inside this transaction: if it throws, the ticket and
            // the seat increment above roll back with it.
            $this->payments->payForFerryTicket($ticket, $method);

            return $ticket;
        });
    }

    /**
     * Cancel a ticket and hand its seat back to the sailing.
     *
     * Cancelling is a status change, never a delete — BUILD_CONTRACT.md §3 gives ferry
     * tickets no destroy route, for the same reason park tickets have none: a deleted row
     * disappears from the manifest and from the payment attached to it.
     *
     * @throws DomainException
     */
    public function cancel(FerryTicket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $fresh = FerryTicket::whereKey($ticket->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status !== 'issued') {
                throw new DomainException('Only an issued ticket can be cancelled.');
            }

            $fresh->update(['status' => 'cancelled']);

            // Returned under the same lock discipline the sale used, so a cancellation
            // racing an issue cannot lose either update.
            $schedule = FerrySchedule::whereKey($fresh->ferry_schedule_id)
                ->lockForUpdate()
                ->firstOrFail();

            $schedule->decrement('seats_taken', min(1, $schedule->seats_taken));
        });
    }

    /**
     * BR-01. Delegates to Raafil's gateway, which owns the definition of a booking that
     * authorises travel and takes lockForUpdate() on the row.
     *
     * The gateway aborts with a 422 HttpException, which is the right answer for a hand
     * posted form. Both ferry controllers show the refusal on the page instead, so the
     * exception is converted here and the message is carried through unchanged.
     */
    private function authorisingBooking(int $hotelBookingId, User $passenger, string $sailingDate)
    {
        try {
            return $this->bookings->assertAuthorises($hotelBookingId, $passenger, $sailingDate);
        } catch (HttpException $e) {
            throw new DomainException($e->getMessage(), $e->getStatusCode(), $e);
        }
    }

    /**
     * The next ferry_tickets.reference in sequence — PIB-FT-000001, PIB-FT-000002, ...
     * MASTER_SCHEMA.md §11.
     *
     * Must be called inside the transaction that writes the row, for the same reason
     * PaymentService::nextReference() must be: the lockForUpdate() holds the read until
     * commit, so two simultaneous issues cannot generate the same reference. MySQL honours
     * the lock; SQLite ignores it, which is harmless because the test suite is single
     * threaded.
     */
    private function nextReference(): string
    {
        $lastId = FerryTicket::query()->lockForUpdate()->max('id') ?? 0;

        return sprintf('PIB-FT-%06d', $lastId + 1);
    }
}
