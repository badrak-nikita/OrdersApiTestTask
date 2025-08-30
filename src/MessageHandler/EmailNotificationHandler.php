<?php

namespace App\MessageHandler;

use App\Message\OrderCreatedMessage;
use App\Message\OrderStatusChangedMessage;
use App\Entity\Order;
use App\Entity\Enum\OrderStatus;
use App\Entity\NotificationLog;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class EmailNotificationHandler
{
    public function __construct(
        private EmailService $emailService,
        private EntityManagerInterface $em
    ) {}

    public function __invoke(OrderCreatedMessage|OrderStatusChangedMessage $message): void
    {
        if ($message instanceof OrderCreatedMessage) {
            $this->handleOrderCreated($message);
        } elseif ($message instanceof OrderStatusChangedMessage) {
            $this->handleStatusChanged($message);
        }
    }

    private function handleOrderCreated(OrderCreatedMessage $msg): void
    {
        $order = $this->em->getRepository(Order::class)->find($msg->orderId);
        if (!$order) { return; }

        $subject = 'Дякуємо за замовлення #' . $order->getId();
        $body = sprintf("Вітаємо, %s! Ми отримали ваше замовлення на суму %s.", $order->getCustomerName(), $order->getTotalAmount());
        $this->safeSendAndLog($order->getId(), $order->getCustomerEmail(), 'created', $subject, $body);
    }

    private function handleStatusChanged(OrderStatusChangedMessage $msg): void
    {
        $order = $this->em->getRepository(Order::class)->find($msg->orderId);
        if (!$order) { return; }

        if ($msg->newStatus === OrderStatus::STATUS_SHIPPED) {
            $subject = 'Ваше замовлення відправлено #' . $order->getId();
            $body = 'Замовлення відправлено. Очікуйте на доставку.';
            $this->safeSendAndLog($order->getId(), $order->getCustomerEmail(), 'shipped', $subject, $body);
        } elseif ($msg->newStatus === OrderStatus::STATUS_DELIVERED) {
            $subject = 'Ваше замовлення доставлено #' . $order->getId();
            $body = 'Дякуємо за покупку! Гарного дня.';
            $this->safeSendAndLog($order->getId(), $order->getCustomerEmail(), 'delivered', $subject, $body);
        }
    }

    private function safeSendAndLog(int $orderId, string $recipient, string $type, string $subject, string $body): void
    {
        $log = new NotificationLog();
        $log->setOrderId($orderId)
            ->setRecipient($recipient)
            ->setSubject($subject)
            ->setBody($body)
            ->setSentAt(new \DateTimeImmutable())
            ->setType($type);

        try {
            $this->emailService->send($recipient, $subject, $body);
            $log->setStatus('sent');
        } catch (\Throwable $e) {
            $log->setStatus('error')->setError($e->getMessage());
        }

        $this->em->persist($log);
        $this->em->flush();
        $this->em->clear();
    }
}
