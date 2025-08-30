<?php
namespace App\Subscriber;

use App\Event\OrderCreatedEvent;
use App\Event\OrderStatusChangedEvent;
use App\Message\OrderCreatedMessage;
use App\Message\OrderStatusChangedMessage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class DomainEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private MessageBusInterface $bus) {}

    public static function getSubscribedEvents(): array
    {
        return [
            OrderCreatedEvent::class => 'onOrderCreated',
            OrderStatusChangedEvent::class => 'onOrderStatusChanged',
        ];
    }

    /**
     * @throws ExceptionInterface
     */
    public function onOrderCreated(OrderCreatedEvent $e): void
    {
        $this->bus->dispatch(new OrderCreatedMessage($e->orderId));
    }

    /**
     * @throws ExceptionInterface
     */
    public function onOrderStatusChanged(OrderStatusChangedEvent $e): void
    {
        $this->bus->dispatch(new OrderStatusChangedMessage($e->orderId, $e->newStatus));
    }
}
