<?php

namespace App\Controller;

use App\DTO\CreateOrderItemRequest;
use App\DTO\CreateOrderRequest;
use App\DTO\UpdateOrderRequest;
use App\DTO\ChangeStatusRequest;
use App\DTO\ViewOrder;
use App\Entity\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private OrderRepository $orders,
        private OrderService $service
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(Request $req): Response
    {
        $page = max(1, (int)$req->query->get('page', 1));
        $limit = min(100, max(1, (int)$req->query->get('limit', 20)));
        $status = $req->query->get('status');
        $dateFrom = $req->query->get('date_from');
        $dateTo = $req->query->get('date_to');
        $email = $req->query->get('email');

        try {
            $result = $this->orders->search($status, $dateFrom, $dateTo, $email, $page, $limit);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $data = array_map(fn($o) => ViewOrder::fromEntity($o), $result['data']);
        $meta = [
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
            'pages' => (int)ceil($result['total'] / $limit)
        ];

        return $this->json(['data' => $data, 'meta' => $meta], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_orders_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $order = $this->orders->find($id);

        if (!$order) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(ViewOrder::fromEntity($order), Response::HTTP_OK);
    }

    /**
     * @throws \JsonException
     */
    #[Route('', methods: ['POST'])]
    public function create(Request $req): Response
    {
        $dto = $this->handleRequestDto($req, CreateOrderRequest::class);

        if ($dto instanceof Response) {
            return $dto;
        }

        $order = $this->service->create($dto);

        return $this->json(
            ViewOrder::fromEntity($order),
            Response::HTTP_CREATED,
            ['Location' => $this->generateUrl('api_orders_show', ['id' => $order->getId()], 0)]
        );
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $req): Response
    {
        $order = $this->orders->find($id);

        if (!$order) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $dto = $this->handleRequestDto($req, UpdateOrderRequest::class);

        if ($dto instanceof Response) {
            return $dto;
        }

        $order = $this->service->update($order, $dto);

        return $this->json(ViewOrder::fromEntity($order), Response::HTTP_OK);
    }

    /**
     * @param Request $req
     * @param class-string<CreateOrderRequest|UpdateOrderRequest> $dtoClass
     * @return CreateOrderRequest|UpdateOrderRequest|Response
     */
    private function handleRequestDto(Request $req, string $dtoClass): CreateOrderRequest|UpdateOrderRequest|Response
    {
        try {
            $data = json_decode($req->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (!$data) {
            return $this->json(['error' => 'Empty request body'], Response::HTTP_BAD_REQUEST);
        }

        $dto = new $dtoClass();
        $dto->customerName = $data['customerName'] ?? '';
        $dto->customerEmail = $data['customerEmail'] ?? '';
        $dto->items = [];

        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $dtoItem = new CreateOrderItemRequest();
                $dtoItem->productName = $item['productName'] ?? '';
                $dtoItem->quantity = $item['quantity'] ?? 0;
                $dtoItem->price = $item['price'] ?? 0.0;
                $dto->items[] = $dtoItem;
            }
        }

        $validationResponse = $this->validateDto($dto);
        if ($validationResponse) {
            return $validationResponse;
        }

        return $dto;
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $order = $this->orders->find($id);
        if (!$order) return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);

        $em = $this->orders->getEntityManager();
        $em->remove($order);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/status', methods: ['PATCH'])]
    public function changeStatus(int $id, Request $req): Response
    {
        $order = $this->orders->find($id);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $content = $req->getContent();
        if (!$content) {
            return $this->json(['error' => 'Empty request body'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $dto = new ChangeStatusRequest();
        $dto->status = $data['status'] ?? '';

        $validationResponse = $this->validateDto($dto);
        if ($validationResponse) {
            return $validationResponse;
        }

        try {
            $status = OrderStatus::from($dto->status);
        } catch (\ValueError $e) {
            return $this->json(['error' => 'Invalid status value'], Response::HTTP_BAD_REQUEST);
        }

        $order = $this->service->changeStatus($order, $status);

        return $this->json(ViewOrder::fromEntity($order), Response::HTTP_OK);
    }

    /**
     * @param object $dto
     * @return Response|null
     */
    private function validateDto(object $dto): ?Response
    {
        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = $v->getPropertyPath() . ': ' . $v->getMessage();
            }
            return $this->json(['error' => 'Validation failed', 'details' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return null;
    }
}
