<?php

namespace App\Service;

use App\DTO\CreateOrderRequest;
use App\DTO\UpdateOrderRequest;
use App\Entity\Enum\OrderStatus;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Event\OrderCreatedEvent;
use App\Event\OrderStatusChangedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OrderService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventDispatcherInterface $dispatcher
    ) {}

    public function create(CreateOrderRequest $dto): Order
    {
        $order = new Order();
        $order->setStatus(OrderStatus::STATUS_PENDING)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->fillOrderFromDto($order, $dto);

        $this->em->persist($order);
        $this->em->flush();

        $this->dispatcher->dispatch(new OrderCreatedEvent($order->getId()));

        return $order;
    }

    public function update(Order $order, UpdateOrderRequest $dto): Order
    {
        $order->setUpdatedAt(new \DateTimeImmutable());
        $this->fillOrderFromDto($order, $dto, clearItems: true);

        $this->em->flush();

        return $order;
    }

    public function changeStatus(Order $order, OrderStatus $newStatus): Order
    {
        $prev = $order->getStatus();
        $order->setStatus($newStatus);
        $this->em->flush();

        if ($prev !== $newStatus) {
            $this->dispatcher->dispatch(new OrderStatusChangedEvent($order->getId(), $newStatus));
        }

        return $order;
    }

    /**
     * @param Order $order
     * @param object $dto
     * @param bool $clearItems
     */
    private function fillOrderFromDto(Order $order, object $dto, bool $clearItems = false): void
    {
        $order->setCustomerName($dto->customerName)
            ->setCustomerEmail($dto->customerEmail);

        if ($clearItems) {
            $order->clearItems();
        }

        $total = 0.0;
        foreach ($dto->items as $it) {
            $item = new OrderItem();
            $item->setProductName($it->productName)
                ->setQuantity($it->quantity)
                ->setPrice(number_format($it->price, 2, '.', ''));

            $order->addItem($item);
            $total += $it->price * $it->quantity;
        }

        $order->setTotalAmount(number_format($total, 2, '.', ''));
    }
}
