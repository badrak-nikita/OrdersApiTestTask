<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateOrderItemRequest
{
    #[Assert\NotBlank]
    public string $productName;

    #[Assert\Positive]
    public int $quantity;

    #[Assert\Positive]
    public float $price;
}
