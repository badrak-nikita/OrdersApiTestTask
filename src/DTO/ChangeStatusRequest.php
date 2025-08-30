<?php

namespace App\DTO;

use App\Entity\Enum\OrderStatus;
use Symfony\Component\Validator\Constraints as Assert;

class ChangeStatusRequest
{
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [OrderStatus::class, 'values'])]
    public string $status;
}
