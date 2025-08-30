<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateOrderRequest
{
    #[Assert\NotBlank]
    public string $customerName;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $customerEmail;

    /** @var CreateOrderItemRequest[] */
    #[Assert\Count(min: 1, minMessage: 'Order must contain at least one item')]
    #[Assert\Valid]
    public array $items = [];
}
