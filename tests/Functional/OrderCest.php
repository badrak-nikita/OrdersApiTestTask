<?php

namespace App\Tests\Functional;

use Tests\Support\FunctionalTester;

class OrderCest
{
    public function testCreateOrderREST(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');

        $I->sendPOST('/orders', [
            'customerName' => 'Тест  Тестович',
            'customerEmail' => 'test@gmail.com',
            'totalAmount' => 1500,
            'items' => [
                ['productName' => 'testProduct', 'quantity' => 1, 'price' => 1500]
            ]
        ]);

        $I->seeResponseCodeIs(201);

        $I->seeResponseContainsJson([
            'customer_name' => 'Тест  Тестович',
            'customer_email' => 'test@gmail.com',
            'status' => 'pending'
        ]);

        $I->seeResponseMatchesJsonType([
            'id' => 'integer',
            'created_at' => 'string'
        ]);
    }

    /**
     * @throws \Exception
     */
    public function testCreateOrderAndGetDetails(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');

        $I->sendPOST('/orders', [
            'customerName' => 'Гришко',
            'customerEmail' => 'grisha@gmail.com',
            'totalAmount' => 500,
            'items' => [
                ['productName' => 'Стопарiк', 'quantity' => 2, 'price' => 50]
            ]
        ]);

        $I->seeResponseCodeIs(201);

        $I->seeResponseContainsJson([
            'customer_name' => 'Гришко',
            'customer_email' => 'grisha@gmail.com',
            'status' => 'pending'
        ]);

        $I->seeResponseMatchesJsonType([
            'id' => 'integer',
            'created_at' => 'string'
        ]);

        $orderId = $I->grabDataFromResponseByJsonPath('$.id')[0];

        $I->sendGET("/orders/{$orderId}");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => $orderId,
            'customer_name' => 'Гришко',
            'customer_email' => 'grisha@gmail.com'
        ]);
    }
}
