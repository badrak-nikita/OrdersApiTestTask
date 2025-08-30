<?php

namespace App\Event;

use App\Entity\Enum\OrderStatus;

class OrderStatusChangedEvent
{
    public function __construct(public int $orderId, public OrderStatus $newStatus) {}
}
