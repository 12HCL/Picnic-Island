<?php

namespace App\Services\Payment;

use App\Models\FerryTicket;
use App\Models\HotelBooking;
use App\Models\Payment;
use App\Models\Ticket;

class PaymentService
{
    public function payForHotelBooking(HotelBooking $booking, string $method): Payment
    {
        throw new \LogicException('Hotel payment is not implemented yet.');
    }

    public function payForFerryTicket(FerryTicket $ticket, string $method): Payment
    {
        throw new \LogicException('FerryTicket payment is not implemented yet.');
    }

    public function payForParkTicket(Ticket $ticket, string $method): Payment
    {
        throw new \LogicException('ParkTicket payment is not implemented yet.');
    }
}
