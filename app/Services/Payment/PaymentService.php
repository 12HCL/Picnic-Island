<?php

namespace App\Services\Payment;

use App\Models\FerryTicket;
use App\Models\HotelBooking;
use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * The payments.method ENUM, MASTER_SCHEMA.md §17 — the only three values the
     * column accepts. Public so the three pay screens in BUILD_CONTRACT.md §3 can
     * build their method dropdown from one source instead of hard-coding the list
     * in four places.
     */
    public const METHODS = ['card', 'cash', 'transfer'];

    /**
     * Take a simulated payment for a hotel booking and record it.
     * BUILD_CONTRACT.md §6 seam 4. Called by Hotel\PaymentController@store.
     *
     * The booking's own status is deliberately NOT changed here. MASTER_SCHEMA.md §11
     * draws the line: hotel_bookings starts `pending` because it is a request awaiting
     * hotel staff confirmation (UC-11) — that is domain state, not payment state, and
     * BR-01 depends on the distinction. Confirming a booking is Raafil's, not this
     * service's.
     */
    public function payForHotelBooking(HotelBooking $booking, string $method): Payment
    {
        if (! in_array($method, self::METHODS, true)) {
            throw new \InvalidArgumentException("Unknown payment method [{$method}].");
        }

        return DB::transaction(function () use ($booking, $method) {
            // Generated first on purpose: nextReference() takes lockForUpdate() on
            // payments, and holding that lock from here to commit is what stops two
            // simultaneous confirmations both passing the already-paid check below.
            $reference = $this->nextReference();

            if (Payment::where('hotel_booking_id', $booking->id)->where('status', 'paid')->exists()) {
                throw new \DomainException("Hotel booking {$booking->reference} is already paid.");
            }

            return Payment::create([
                'user_id' => $booking->user_id,
                'hotel_booking_id' => $booking->id,
                'reference' => $reference,
                'amount' => $booking->total_amount,
                'method' => $method,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        });
    }

    /**
     * Take a simulated payment for a ferry ticket and record it.
     * BUILD_CONTRACT.md §6 seam 4. Payment state only: ticket creation, the
     * hotel-booking re-check and the schedule seat update belong to Naayif's
     * FerryTicketController@store transaction, which calls this method.
     */
    public function payForFerryTicket(FerryTicket $ticket, string $method): Payment
    {
        if (! in_array($method, self::METHODS, true)) {
            throw new \InvalidArgumentException("Unknown payment method [{$method}].");
        }

        return DB::transaction(function () use ($ticket, $method) {
            // Generated first on purpose: nextReference() takes lockForUpdate() on
            // payments, and holding that lock from here to commit is what stops two
            // simultaneous confirmations both passing the already-paid check below.
            $reference = $this->nextReference();

            if (Payment::where('ferry_ticket_id', $ticket->id)->where('status', 'paid')->exists()) {
                throw new \DomainException("Ferry ticket {$ticket->reference} is already paid.");
            }

            return Payment::create([
                'user_id' => $ticket->user_id,
                'ferry_ticket_id' => $ticket->id,
                'reference' => $reference,
                'amount' => $ticket->fare,
                'method' => $method,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        });
    }

    /**
     * Take a simulated payment for a park or beach admission and record it.
     * BUILD_CONTRACT.md §6 seam 4. Called from inside TicketSalesService::sell(), which
     * holds the outer transaction — Laravel nests this one as a savepoint, so a failure
     * here rolls the ticket and the seat increment back with it.
     *
     * user_id is copied from the ticket and stays null for an anonymous gate sale
     * (MASTER_SCHEMA.md §14, §17). The seller is recorded on tickets.sold_by, never here,
     * which is why revenue is reported by channel rather than by user_id.
     *
     * The amount is unit_price x quantity: a ticket admits `quantity` people and the
     * payment must match what was charged, not the price of one admission.
     */
    public function payForParkTicket(Ticket $ticket, string $method): Payment
    {
        if (! in_array($method, self::METHODS, true)) {
            throw new \InvalidArgumentException("Unknown payment method [{$method}].");
        }

        return DB::transaction(function () use ($ticket, $method) {
            // Generated first on purpose: nextReference() takes lockForUpdate() on
            // payments, and holding that lock from here to commit is what stops two
            // simultaneous confirmations both passing the already-paid check below.
            $reference = $this->nextReference();

            if (Payment::where('ticket_id', $ticket->id)->where('status', 'paid')->exists()) {
                throw new \DomainException("Park ticket {$ticket->reference} is already paid.");
            }

            return Payment::create([
                'user_id' => $ticket->user_id,
                'ticket_id' => $ticket->id,
                'reference' => $reference,
                'amount' => $ticket->total(),
                'method' => $method,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        });
    }

    /**
     * The next payments.reference in sequence — PIB-PM-000001, PIB-PM-000002, ...
     * MASTER_SCHEMA.md §17.
     *
     * Must be called inside the DB::transaction() that writes the row. The
     * lockForUpdate() holds the read until that transaction commits, so two
     * simultaneous payments cannot both read the same maximum id and generate
     * the same reference — the same read-then-write race BR-02 guards against
     * on ferry seats. MySQL honours the lock; SQLite ignores it, which is
     * harmless because the test suite is single threaded.
     */
    private function nextReference(): string
    {
        $lastId = Payment::query()->lockForUpdate()->max('id') ?? 0;

        return sprintf('PIB-PM-%06d', $lastId + 1);
    }
}
