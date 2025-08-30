<?php

namespace App\DTO;

use App\Entity\Order;

class ViewOrder
{
    public static function fromEntity(Order $order): array
    {
        return [
            'id'             => $order->getId(),
            'customer_name'  => $order->getCustomerName(),
            'customer_email' => $order->getCustomerEmail(),
            'total_amount'   => $order->getTotalAmount(),
            'status'         => $order->getStatus()->value,
            'created_at'     => $order->getCreatedAt()->format('d-m-Y H:i:s'),
            'updated_at'     => $order->getUpdatedAt()?->format('d-m-Y H:i:s'),
        ];
    }
}
