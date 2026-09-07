<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\User;
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
}
