<?php

namespace Tests\Leos\UI\RestBundle\Controller\Wallet;

use Lakion\ApiTestCase\JsonApiTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tests\Leos\UI\RestBundle\Controller\Security\SecurityTrait;

/**
 * Class WalletControllerTest
 * 
 * @package Leos\UI\RestBundle\Controller\Wallet
 */
class WalletControllerTest extends JsonApiTestCase
{
    use SecurityTrait;

    private $databaseLoaded = false;

    public function setUp()
    {
        if (!$this->client) {

            $this->setUpClient();
        }

        if (!$this->databaseLoaded) {

            $this->setUpDatabase();
            $this->databaseLoaded = true;
        }
    }

    /**
     * @group integration
     */
    public function testCreateWalletAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $userId = $this->users['jorge']->uuid()->__toString();

        $this->client->request('POST', '/api/v1/wallet.json', [
            'userId' => $userId
        ]);

        $response = $this->client->getResponse();

        self::assertEquals($response->getStatusCode(), 201);

        $this->redirect($response->headers->get('Location'), 'Wallet/get_wallet', 200);
    }

    /**
     * @group integration
     */
    public function testCreateWalletWithWrongCurrencyAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $userId = $this->users['jorge']->uuid()->__toString();

        $this->client->request('POST', '/api/v1/wallet.json', [
            'userId' => $userId,
            'currency' => 'EURAZO'
        ]);

        self::assertEquals(400, $this->client->getResponse()->getStatusCode());
        self::assertContains('currency', $this->client->getResponse()->getContent());
    }

    /**
     * @group integration
     */
    public function testGetWalletActionNotFound()
    {
        $this->loginClient('jorge', 'iyoque123');

        $this->client->request('GET', '/api/v1/wallet/0.json');

        self::assertEquals($this->client->getResponse()->getStatusCode(), 404);
    }

    /**
     * @group integration
     */
    public function testDepositAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $userId = $this->users['jorge']->uuid()->__toString();

        $getWalletRoute = $this->createWallet($userId);
        $this->client->request('POST', $getWalletRoute . '/deposit.json', [
            'real' => 100,
            'provider' => 'paypal'
        ]);

        $response = $this->client->getResponse();
        self::assertResponse($response, "Wallet/deposit", 202);
    }

    /**
     * @group integration
     */
    public function testDepositBadUUIDAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $this->client->request('POST',  '/api/v1/wallet/404/deposit.json', [
            'real' => 5,
            'bonus' => 5,
            'provider' => 'paypal'
        ]);

        self::assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @group integration
     */
    public function testDeposit400WrongCurrencyAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $this->client->request('POST',  '/api/v1/wallet/0cb00000-646e-11e6-a5a2-0000ac1b0000/deposit.json', [
            'real' => 5,
            'bonus' => 5,
            'currency' => 'LIBRAS',
            'provider' => 'paypal'
        ]);

        self::assertEquals(400, $this->client->getResponse()->getStatusCode());
        self::assertContains('currency', $this->client->getResponse()->getContent());
    }

    /**
     * @group integration
     */
    public function testWithdrawalAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $userId = $this->users['jorge']->uuid()->__toString();

        $getWalletRoute = $this->createWallet($userId);

        $this->client->request('POST', $getWalletRoute . '/deposit.json', [
            'real' => 50,
            'provider' => 'paypal'
        ]);

        $this->client->request('POST', $getWalletRoute . '/withdrawal.json', [
            'real' => 5,
            'provider' => 'paypal'
        ]);

        self::assertResponse($this->client->getResponse(), "Wallet/withdrawal", 202);
    }

    /**
     * @group integration
     */
    public function testWithdrawalShouldFailWhenMinAmountAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $userId = $this->users['jorge']->uuid()->__toString();

        $getWalletRoute = $this->createWallet($userId);

        $this->client->request('POST', $getWalletRoute . '/deposit.json', [
            'real' => 50,
            'provider' => 'paypal'
        ]);

        $this->client->request('POST', $getWalletRoute . '/withdrawal.json', [
            'real' => 0,
            'provider' => 'paypal'
        ]);

        self::assertEquals($this->client->getResponse()->getStatusCode(), 400);
        self::assertContains('amount_must_be_higher_than_0', $this->client->getResponse()->getContent());
    }

    /**
     * @group integration
     */
    public function testDepositWrongCurrencyAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $userId = $this->users['jorge']->uuid()->__toString();

        $getWalletRoute = $this->createWallet($userId);

        $this->client->request('POST', $getWalletRoute . '/deposit.json', [
            'real' => 50,
            'currency' => 'LIBRAS',
            'provider' => 'paypal'
        ]);

        self::assertEquals(400, $this->client->getResponse()->getStatusCode());
        self::assertContains('currency', $this->client->getResponse()->getContent());
    }


    /**
     * @group integration
     */
    public function testDepositWrongAmountAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $userId = $this->users['jorge']->uuid()->__toString();

        $getWalletRoute = $this->createWallet($userId);

        $this->client->request('POST', $getWalletRoute . '/deposit.json', [
            'real' => 0,
            'currency' => 'EUR',
            'provider' => 'paypal'
        ]);

        self::assertEquals(400, $this->client->getResponse()->getStatusCode());
        self::assertContains('amount', $this->client->getResponse()->getContent());
    }


    /**
     * @group integration
     */
    public function testDeposit404Action()
    {
        $this->loginClient('jorge', 'iyoque123');

        $this->client->request('POST',  '/api/v1/wallet/0cb00000-646e-11e6-a5a2-0000ac1b0000/deposit.json', [
            'real' => 5,
            'provider' => 'paypal'
        ]);

        self::assertEquals(404, $this->client->getResponse()->getStatusCode());
    }


    /**
     * @group integration
     */
    public function testWithdrawal400WrongCurrencyAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $this->client->request('POST',  '/api/v1/wallet/0cb00000-646e-11e6-a5a2-0000ac1b0000/withdrawal.json', [
            'real' => 5,
            'bonus' => 5,
            'currency' => 'LIBRAS',
            'provider' => 'paypal'
        ]);

        self::assertEquals(400, $this->client->getResponse()->getStatusCode());
        self::assertContains('currency', $this->client->getResponse()->getContent());
    }

    /**
     * @group integration
     */
    public function testWithdrawal409Action()
    {
        $this->loginClient('jorge', 'iyoque123');

        $userId = $this->users['jorge']->uuid()->__toString();

        $this->client->request('POST', '/api/v1/wallet.json', [
            'userId' => $userId
        ]);

        $response = $this->client->getResponse();

        $this->client->request('POST', $response->headers->get('location') . '/withdrawal.json', [
            'real' => 60,
            'bonus' => 5,
            'provider' => 'paypal'
        ]);

        self::assertEquals(409, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @group integration
     */
    public function testWithdrawalBadUUIDAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $this->client->request('POST',  '/api/v1/wallet/404/withdrawal.json', [
            'real' => 5,
            'bonus' => 5,
            'provider' => 'paypal'
        ]);

        self::assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @group integration
     */
    public function testWithdrawal404Action()
    {
        $this->loginClient('jorge', 'iyoque123');
        
        $this->client->request('POST',  '/api/v1/wallet/0cb00000-646e-11e6-a5a2-0000ac1b0000/withdrawal.json', [
            'real' => 5,
            'bonus' => 5,
            'provider' => 'paypal'
        ]);

        self::assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @group integration
     */
    public function testTransferBadSenderUUIDAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $this->client->request('POST',  '/api/v1/wallet/404/transfer.json', [
            'receiverWalledUid' => '0cb00000-646e-11e6-a5a2-0000ac1b0000',
            'real' => 5,
            'currency' => "EUR",
            'provider' => 'paypal'
        ]);

        self::assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @group integration
     */
    public function testTransferBadReceiverUUIDAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $this->client->request('POST',  '/api/v1/wallet/0cb00000-646e-11e6-a5a2-0000ac1b0000/transfer.json', [
            'receiverWalletUuid' => '404',
            'real' => 5,
            'currency' => "EUR",
            'provider' => 'paypal'
        ]);

        self::assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @group integration
     */
    public function testTransferInvalidAmountAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $this->client->request('POST',  '/api/v1/wallet/0cb00000-646e-11e6-a5a2-0000ac1b0000/transfer.json', [
            'receiverWalletUuid' => '0cb00000-646e-11e6-a5a2-0000ac1b0001',
            'real' => 0,
            'currency' => "EUR",
            'provider' => 'paypal'
        ]);

        self::assertEquals(400, $this->client->getResponse()->getStatusCode());
        self::assertContains('amount', $this->client->getResponse()->getContent());
    }

    /**
     * @group integration
     */
    public function testTransferInvalidCurrencyAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $this->client->request('POST',  '/api/v1/wallet/0cb00000-646e-11e6-a5a2-0000ac1b0000/transfer.json', [
            'receiverWalletUuid' => '0cb00000-646e-11e6-a5a2-0000ac1b0001',
            'real' => 5,
            'currency' => "EURASDAS",
            'provider' => 'paypal'
        ]);

        self::assertEquals(400, $this->client->getResponse()->getStatusCode());
        self::assertContains('currency', $this->client->getResponse()->getContent());
    }

    /**
     * @group integration
     */
    public function testTransferAction()
    {
        $this->loginClient('jorge', 'iyoque123');

        $userId = $this->users['jorge']->uuid()->__toString();

        $firstWalletRoute = $this->createWallet($userId);
        $firstWalletUuid = substr($firstWalletRoute, strripos($firstWalletRoute, "/") + 1);
        $this->client->request('POST', $firstWalletRoute . '/deposit.json', [
            'real' => 50,
            'provider' => 'paypal'
        ]);

        $secondWalletRoute = $this->createWallet($userId);
        $secondWalletUuid = substr($secondWalletRoute, strripos($secondWalletRoute, "/") + 1);
        $this->client->request('POST',  $firstWalletRoute . '/transfer.json', [
            'receiverWalletUuid' => $secondWalletUuid,
            'real' => 5,
            'currency' => "EUR",
            'provider' => 'paypal'
        ]);

        $sfResponse = $this->client->getResponse();

        $this->assertResponseCode($sfResponse, 202);
        $this->assertJson($sfResponse->getContent());

        $responseData = json_decode($sfResponse->getContent(), true);

        self::assertResponse(
            new JsonResponse($responseData[$firstWalletUuid], $sfResponse->getStatusCode(), $sfResponse->headers->all()),
            "Wallet/transfer_sender",
            202
        );

        self::assertResponse(
            new JsonResponse($responseData[$secondWalletUuid], $sfResponse->getStatusCode(), $sfResponse->headers->all()),
            "Wallet/transfer_receiver",
            202
        );
    }

    /**
     * @group integration
     */
    public function testWalletCollectionAction()
    {
        $this->loadFixturesFromDirectory('wallet');

        $this->loginClient('jorge', 'iyoque123', false);

        $this->client->request('GET',  '/api/v1/wallet.json');

        self::assertResponse($this->client->getResponse(), "Wallet/cget_wallet", 200);
    }

    /**
     * @group integration
     */
    public function testWalletCollectionFilterAction()
    {
        $this->loadFixturesFromDirectory('wallet');

        $this->loginClient('jorge', 'iyoque123', false);

        $this->client->request('GET',  '/api/v1/wallet.json?filterParam[]=real.amount&filterOp[]=eq&filterValue[]=50');

        self::assertResponse($this->client->getResponse(), "Wallet/cget_wallet_filter_50", 200);
    }

    /**
     * @param string $location
     * @param string $responseFile
     * @param int $code
     */
    private function redirect(string $location, string $responseFile, int $code)
    {
        $this->client->request('GET', $location);

        self::assertResponse($this->client->getResponse(), $responseFile, $code);
    }

    private function createWallet(string $userId): string
    {
        $this->client->request('POST', '/api/v1/wallet.json', [
            'userId' => $userId
        ]);

        $response = $this->client->getResponse();
        self::assertEquals(201, $response->getStatusCode());

        return $response->headers->get('location');
    }
}
