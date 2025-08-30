<?php

namespace App\Message;

use App\Entity\Enum\OrderStatus;

class OrderStatusChangedMessage
{
    public function __construct(public int $orderId, public OrderStatus $newStatus) {}
}
