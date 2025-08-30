<?php

namespace App\Repository;

use App\Entity\Enum\OrderStatus;
use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @return array{data: Order[], total: int}
     * @throws \Exception
     */
    public function search(?string $status, ?string $dateFrom, ?string $dateTo, ?string $email, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.items', 'i')
            ->addSelect('i');

        if ($status) {
            if (!in_array($status, OrderStatus::values(), true)) {
                throw new \InvalidArgumentException('Invalid status');
            }
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        if ($email) {
            $qb->andWhere('LOWER(o.customerEmail) LIKE :email')
                ->setParameter('email', '%' . mb_strtolower($email) . '%');
        }

        if ($dateFrom) {
            $from = new \DateTimeImmutable($dateFrom . ' 00:00:00');
            $qb->andWhere('o.createdAt >= :from')->setParameter('from', $from);
        }
        if ($dateTo) {
            $to = new \DateTimeImmutable($dateTo . ' 23:59:59');
            $qb->andWhere('o.createdAt <= :to')->setParameter('to', $to);
        }

        $qb->orderBy('o.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb);
        $total = count($paginator);
        $data = iterator_to_array($paginator->getIterator());

        return ['data' => $data, 'total' => $total];
    }
}
