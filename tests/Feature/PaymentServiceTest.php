<?php

namespace Tests\Feature;

use App\Models\FerryRoute;
use App\Models\FerrySchedule;
use App\Models\FerryTicket;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\User;
use App\Models\Vessel;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;
    private User $user;
    private Hotel $hotel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentService();
        $this->user = User::factory()->create();
        $this->hotel = Hotel::create([
            'name' => 'Coral Bay Resort',
            'description' => 'Beachside hotel',
            'address' => 'North Shore, Picnic Island',
            'star_rating' => 5,
        ]);
    }

    /**
     * A pending hotel booking to pay for. Status is deliberately `pending`,
     * which is where MASTER_SCHEMA.md §11 says a booking starts.
     */
    private function booking(string $reference, float $total = 500.00): HotelBooking
    {
        return HotelBooking::create([
            'user_id' => $this->user->id,
            'hotel_id' => $this->hotel->id,
            'reference' => $reference,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-15',
            'guests' => 2,
            'total_amount' => $total,
            'status' => 'pending',
        ]);
    }

    /**
     * An issued ferry ticket to pay for. PaymentService records only the payment;
     * Naayif's controller owns ticket issuance and the schedule seat counter.
     */
    private function ferryTicket(string $reference, float $fare = 75.00): FerryTicket
    {
        $route = FerryRoute::create([
            'origin' => 'Male',
            'destination' => 'Picnic Island',
            'duration_minutes' => 45,
            'base_fare' => $fare,
        ]);
        $vessel = Vessel::create([
            'name' => 'Island Voyager',
            'capacity' => 80,
            'status' => 'active',
        ]);
        $schedule = FerrySchedule::create([
            'ferry_route_id' => $route->id,
            'vessel_id' => $vessel->id,
            'departure_date' => '2026-09-12',
            'departure_time' => '09:00:00',
            'seats_taken' => 1,
            'status' => 'scheduled',
        ]);

        return FerryTicket::create([
            'user_id' => $this->user->id,
            'ferry_schedule_id' => $schedule->id,
            'hotel_booking_id' => $this->booking('PIB-HB-'.$reference)->id,
            'reference' => $reference,
            'fare' => $fare,
            'status' => 'issued',
            'issued_at' => now(),
        ]);
    }

    public function test_paying_records_a_paid_payment_against_the_booking(): void
    {
        $booking = $this->booking('PIB-HB-000001', 450.00);

        $payment = $this->service->payForHotelBooking($booking, 'card');

        $this->assertSame($this->user->id, $payment->user_id);
        $this->assertSame($booking->id, $payment->hotel_booking_id);
        $this->assertSame('PIB-PM-000001', $payment->reference);
        $this->assertEquals('450.00', $payment->amount);
        $this->assertSame('card', $payment->method);
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertDatabaseCount('payments', 1);
    }

    /**
     * BR-03: exactly one of the three target columns is set. The CHECK constraint
     * enforces this on MySQL, but the suite runs on SQLite, so assert it here too.
     */
    public function test_only_the_hotel_booking_target_is_set(): void
    {
        $payment = $this->service->payForHotelBooking($this->booking('PIB-HB-000001'), 'card');

        $this->assertNotNull($payment->hotel_booking_id);
        $this->assertNull($payment->ferry_ticket_id);
        $this->assertNull($payment->ticket_id);
    }

    public function test_payment_references_increment_in_sequence(): void
    {
        $first = $this->service->payForHotelBooking($this->booking('PIB-HB-000001'), 'card');
        $second = $this->service->payForHotelBooking($this->booking('PIB-HB-000002'), 'cash');

        $this->assertSame('PIB-PM-000001', $first->reference);
        $this->assertSame('PIB-PM-000002', $second->reference);
    }

    /**
     * MASTER_SCHEMA.md §11: confirming a booking is UC-11 staff domain state, not
     * payment state, and BR-01 turns on the distinction. Paying must not confirm.
     */
    public function test_paying_does_not_confirm_the_booking(): void
    {
        $booking = $this->booking('PIB-HB-000001');

        $this->service->payForHotelBooking($booking, 'transfer');

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_an_unknown_payment_method_is_refused(): void
    {
        $booking = $this->booking('PIB-HB-000001');

        try {
            $this->service->payForHotelBooking($booking, 'crypto');
            $this->fail('An unknown payment method should have been refused.');
        } catch (\InvalidArgumentException $e) {
            // Expected — 'crypto' is not in PaymentService::METHODS.
        }

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_booking_cannot_be_paid_twice(): void
    {
        $booking = $this->booking('PIB-HB-000001');
        $this->service->payForHotelBooking($booking, 'card');

        try {
            $this->service->payForHotelBooking($booking, 'cash');
            $this->fail('A booking that is already paid should have been refused.');
        } catch (\DomainException $e) {
            // Expected — the already-paid guard rolled the transaction back.
        }

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_paying_records_a_paid_payment_against_the_ferry_ticket(): void
    {
        $ticket = $this->ferryTicket('PIB-FT-000001', 85.00);

        $payment = $this->service->payForFerryTicket($ticket, 'cash');

        $this->assertSame($this->user->id, $payment->user_id);
        $this->assertSame($ticket->id, $payment->ferry_ticket_id);
        $this->assertSame('PIB-PM-000001', $payment->reference);
        $this->assertEquals('85.00', $payment->amount);
        $this->assertSame('cash', $payment->method);
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertDatabaseCount('payments', 1);
    }

    /**
     * BR-03: SQLite does not enforce the MySQL CHECK or deferred foreign key,
     * so prove directly that only the ferry target is populated.
     */
    public function test_only_the_ferry_ticket_target_is_set(): void
    {
        $payment = $this->service->payForFerryTicket($this->ferryTicket('PIB-FT-000001'), 'card');

        $this->assertNull($payment->hotel_booking_id);
        $this->assertNotNull($payment->ferry_ticket_id);
        $this->assertNull($payment->ticket_id);
    }

    public function test_payment_references_continue_in_sequence_across_payment_types(): void
    {
        $hotelPayment = $this->service->payForHotelBooking($this->booking('PIB-HB-000001'), 'card');
        $ferryPayment = $this->service->payForFerryTicket($this->ferryTicket('PIB-FT-000001'), 'cash');

        $this->assertSame('PIB-PM-000001', $hotelPayment->reference);
        $this->assertSame('PIB-PM-000002', $ferryPayment->reference);
    }

    public function test_paying_does_not_change_the_ferry_ticket_or_schedule_state(): void
    {
        $ticket = $this->ferryTicket('PIB-FT-000001');

        $this->service->payForFerryTicket($ticket, 'transfer');

        $this->assertSame('issued', $ticket->fresh()->status);
        $this->assertSame(1, $ticket->schedule->fresh()->seats_taken);
    }

    public function test_an_unknown_ferry_payment_method_is_refused(): void
    {
        $ticket = $this->ferryTicket('PIB-FT-000001');

        try {
            $this->service->payForFerryTicket($ticket, 'crypto');
            $this->fail('An unknown payment method should have been refused.');
        } catch (\InvalidArgumentException $e) {
            // Expected — 'crypto' is not in PaymentService::METHODS.
        }

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_a_ferry_ticket_cannot_be_paid_twice(): void
    {
        $ticket = $this->ferryTicket('PIB-FT-000001');
        $this->service->payForFerryTicket($ticket, 'card');

        try {
            $this->service->payForFerryTicket($ticket, 'cash');
            $this->fail('A ferry ticket that is already paid should have been refused.');
        } catch (\DomainException $e) {
            // Expected — the already-paid guard rolled the transaction back.
        }

        $this->assertDatabaseCount('payments', 1);
    }
}
