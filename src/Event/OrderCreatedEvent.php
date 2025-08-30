<?php

namespace App\Event;

class OrderCreatedEvent
{
    public function __construct(public int $orderId) {}
}
