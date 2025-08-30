<?php

namespace App\Entity\Enum;

enum OrderStatus: string
{
    case STATUS_PENDING = 'pending';
    case STATUS_PROCESSING = 'processing';
    case STATUS_SHIPPED = 'shipped';
    case STATUS_DELIVERED = 'delivered';
    case STATUS_CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_map(fn(self $c) => $c->value, self::cases());
    }
}
