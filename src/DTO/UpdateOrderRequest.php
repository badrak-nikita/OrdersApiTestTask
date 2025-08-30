<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateOrderRequest
{
    #[Assert\NotBlank]
    public string $customerName;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $customerEmail;

    /** @var CreateOrderItemRequest[] */
    #[Assert\Count(min: 1)]
    #[Assert\Valid]
    public array $items = [];
}
