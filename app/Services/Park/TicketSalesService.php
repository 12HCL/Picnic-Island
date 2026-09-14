<?php

namespace App\Services\Park;

use App\Models\ParkEvent;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Payment\PaymentService;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Module 4 — the one place a park or beach admission is sold.
 *
 * Both channels the brief asks for come through here: `online`, bought by a logged-in
 * visitor, and `gate`, sold by park staff to a walk-up buyer who may have no account.
 * They differ only in who the buyer is and who the seller is, so the rule that matters —
 * BR-06 — is written once rather than once per controller.
 *
 * BR-06: park_events.seats_taken may not exceed park_events.capacity.
 *
 * The rule is a check-then-act race, which is the whole reason this is a transaction and
 * not four statements. Two visitors buying the last seat at the same moment would both read
 * seats_taken = 39 against a capacity of 40, both decide there is room, and both write 40 —
 * overselling the event with no error anywhere. lockForUpdate() on the event row makes the
 * second read wait until the first transaction commits, so it sees 40 and is refused.
 *
 * MASTER_SCHEMA.md §14: a tickets row is written only at payment confirmation. There is no
 * pending_payment state to create a ticket into first, so the ticket, the seat increment and
 * the payments row are one atomic unit. If the payment fails the seat is never taken.
 */
class TicketSalesService
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    /**
     * Sell `quantity` admissions to `$event` and take payment for them.
     *
     * @param  User|null  $buyer   The purchasing visitor. Null for an anonymous gate sale.
     * @param  User|null  $seller  The park staff member. Null for an online purchase.
     *
     * @throws DomainException when the event cannot be sold, with a message fit to show.
     */
    public function sell(
        ParkEvent $event,
        int $quantity,
        string $channel,
        ?User $buyer,
        ?User $seller,
        string $method,
    ): Ticket {
        return DB::transaction(function () use ($event, $quantity, $channel, $buyer, $seller, $method) {
            // Re-read under a write lock. The $event passed in was read outside this
            // transaction and its seats_taken may already be stale — every decision below
            // is made against $locked, never against the argument.
            $locked = ParkEvent::whereKey($event->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'scheduled') {
                throw new DomainException('This event is no longer on sale.');
            }

            if ($locked->event_date->isPast() && ! $locked->event_date->isToday()) {
                throw new DomainException('This event has already taken place.');
            }

            // BR-06, enforced here and nowhere else that matters.
            if ($locked->seats_taken + $quantity > $locked->capacity) {
                $remaining = max(0, $locked->capacity - $locked->seats_taken);

                throw new DomainException(
                    $remaining === 0
                        ? 'This event is sold out.'
                        : "Only {$remaining} admission(s) left for this event.",
                );
            }

            $ticket = Ticket::create([
                'user_id' => $buyer?->id,
                'park_event_id' => $locked->id,
                'reference' => $this->nextReference(),
                'quantity' => $quantity,
                // Copied from the event, not joined at read time: repricing an event must
                // never rewrite what a past buyer was charged.
                'unit_price' => $locked->price,
                'channel' => $channel,
                'status' => 'valid',
                'sold_by' => $seller?->id,
            ]);

            $locked->increment('seats_taken', $quantity);

            // Nested as a savepoint inside this transaction: if it throws, the ticket and
            // the seat increment above roll back with it.
            $this->payments->payForParkTicket($ticket, $method);

            return $ticket;
        });
    }

    /**
     * Void a ticket and hand its seats back to the event.
     *
     * Cancelling is a status change, never a delete — BUILD_CONTRACT.md §3 gives tickets no
     * destroy route, because a deleted row disappears from the sales reports and from the
     * payment it is attached to.
     */
    public function cancel(Ticket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $fresh = Ticket::whereKey($ticket->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status !== 'valid') {
                throw new DomainException('Only a valid ticket can be cancelled.');
            }

            $fresh->update(['status' => 'cancelled']);

            // Returned under the same lock discipline the sale used, so a cancellation
            // racing a sale cannot lose either update.
            $event = ParkEvent::whereKey($fresh->park_event_id)->lockForUpdate()->firstOrFail();
            $event->decrement('seats_taken', min($fresh->quantity, $event->seats_taken));
        });
    }

    /**
     * The next tickets.reference in sequence — PIB-TK-000001, PIB-TK-000002, ...
     * MASTER_SCHEMA.md §14.
     *
     * Must be called inside the transaction that writes the row, for the same reason
     * PaymentService::nextReference() must be: the lockForUpdate() holds the read until
     * commit, so two simultaneous sales cannot generate the same reference. MySQL honours
     * the lock; SQLite ignores it, which is harmless because the test suite is single
     * threaded.
     */
    private function nextReference(): string
    {
        $lastId = Ticket::query()->lockForUpdate()->max('id') ?? 0;

        return sprintf('PIB-TK-%06d', $lastId + 1);
    }
}
