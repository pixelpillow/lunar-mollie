<?php

namespace Pixelpillow\LunarMollie\Tests\Unit\Managers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Lunar\Models\Transaction;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Refund;
use Pixelpillow\LunarMollie\Actions\SetPaymentIssuerOnCart;
use Pixelpillow\LunarMollie\Exceptions\MissingMetadataException;
use Pixelpillow\LunarMollie\Managers\MollieManager;
use Pixelpillow\LunarMollie\Tests\TestCase;
use Pixelpillow\LunarMollie\Tests\Utils\CartBuilder;

class MollieManagerTest extends TestCase
{
    use RefreshDatabase;

    public function testMollieManagerCanBeInstantiated()
    {
        $this->assertInstanceOf(MollieManager::class, $this->mollieManager);
    }

    public function testMollieApiClientIsAInstanceOfMollieApiClient()
    {
        $this->assertInstanceOf(MollieApiClient::class, $this->mollieApiClient);
    }

    /**
     * Test to see if an exception is thrown when there is no payment issuer is set on the cart.
     */
    public function testIfMissingPaymentIssuerAnExceptionIsThrown()
    {
        $this->expectException(MissingMetadataException::class);

        // Create a cart
        $cart = CartBuilder::build();
        $transaction = new Transaction();
        $transaction->id = uniqid();

        $this->mollieManager->createMolliePayment($cart->calculate(), $transaction, 100);
    }

    public function testPaymentIsCreated()
    {
        // Create a cart
        $cart = CartBuilder::build();

        // Set the payment issuer to ABN AMRO
        $payment_issuer = 'ideal_ABNANL2A';

        // Set the payment issuer on the cart
        App::make(SetPaymentIssuerOnCart::class)($cart, $payment_issuer);

        $payment = new Payment($this->mollieApiClient);
        $payment->id = uniqid('tr_');
        $payment->amount = [
            'value' => '100.00',
            'currency' => 'EUR',
        ];

        Http::fake([
            'https://api.mollie.com/*' => Http::response(json_encode($payment)),
        ]);

        $transaction = new Transaction();
        $transaction->id = uniqid();

        $response = $this->mollieManager->createMolliePayment(
            $cart->calculate(),
            $transaction,
            100
        );

        $this->assertEquals(
            $response->id,
            $payment->id
        );
    }

    public function testRefundIsCreated()
    {
        // Create a cart
        $cart = CartBuilder::build();

        // Set the payment issuer to ABN AMRO
        $payment_issuer = 'ideal_ABNANL2A';

        // Set the payment issuer on the cart
        App::make(SetPaymentIssuerOnCart::class)($cart, $payment_issuer);

        $mockPayment = new Payment($this->mollieApiClient);
        $mockPayment->id = uniqid('tr_');
        $mockPayment->amount = [
            'value' => '100.00',
            'currency' => 'EUR',
        ];

        $mockRefund = new Refund($this->mollieApiClient);
        $mockRefund->id = uniqid('re_');

        Http::fake([
            'https://api.mollie.com/v2/payments' => Http::response(json_encode($mockPayment)),
            'https://api.mollie.com/v2/payments/'.$mockPayment->id => Http::response(json_encode($mockPayment)),
            'https://api.mollie.com/v2/payments/'.$mockPayment->id.'/refunds' => Http::response(json_encode($mockRefund)),
        ]);

        $transaction = new Transaction();
        $transaction->id = uniqid();

        // Created a payment
        $response = $this->mollieManager->createMolliePayment(
            $cart->calculate(),
            $transaction,
            100
        );

        Http::fake([
            'https://api.mollie.com/v2/refunds/'.$mockRefund->id => Http::response(json_encode($mockRefund)),
        ]);

        $refund = $this->mollieManager->createMollieRefund(
            $response->id,
            100,
            'Test refund'
        );

        $this->assertEquals(
            $refund->id,
            $mockRefund->id
        );
    }

    public function testPaymentIssuersReponseIsCorrect()
    {
        $paymentIssuers = [
            'id' => 'ideal',
            'issuers' => [
                [
                    'id' => 'ideal_ABNANL2A',
                    'name' => 'ABN AMRO',
                    'image' => [
                        'size1x' => 'https://www.mollie.com/external/icons/payment-methods/ideal.png',
                        'size2x' => 'https://www.mollie.com/external/icons/payment-methods/ideal%402x.png',
                        'svg' => 'https://www.mollie.com/external/icons/payment-methods/ideal.svg',
                    ],
                ],
                [
                    'id' => 'ideal_ASNBNL21',
                    'name' => 'ASN Bank',
                    'image' => [
                        'size1x' => 'https://www.mollie.com/external/icons/payment-methods/ideal.png',
                        'size2x' => 'https://www.mollie.com/external/icons/payment-methods/ideal%402x.png',
                        'svg' => 'https://www.mollie.com/external/icons/payment-methods/ideal.svg',
                    ],
                ],
            ],
        ];

        Http::fake([
            'https://api.mollie.com/*' => Http::response(json_encode($paymentIssuers)),
        ]);

        $response = $this->mollieManager->getMolliePaymentIssuers();

        $this->assertEquals(
            $response->count(),
            2
        );

        $this->assertEquals(
            $response[0]->id,
            'ideal_ABNANL2A'
        );

        $this->assertEquals(
            $response[0]->name,
            'ABN AMRO'
        );

        $this->assertEquals(
            $response[0]->image->size1x,
            'https://www.mollie.com/external/icons/payment-methods/ideal.png'
        );
    }

    /**
     * Test to see if the amount is formatted correctly
     *
     * @return void
     */
    public function testNormalizeAmountToString()
    {
        $mollie = new MollieManager();

        $this->assertEquals(
            $mollie->normalizeAmountToString(100),
            '1.00'
        );

        $this->assertEquals(
            $mollie->normalizeAmountToString(1000),
            '10.00'
        );

        $this->assertEquals(
            $mollie->normalizeAmountToString(10000),
            '100.00'
        );

        $this->assertEquals(
            $mollie->normalizeAmountToString(100000),
            '1000.00'
        );

        $this->assertEquals(
            $mollie->normalizeAmountToString(1000000),
            '10000.00'
        );
    }

    /**
     * Test to see if the amount is formatted correctly
     *
     * @return void
     */
    public function testNormalizeAmountToInteger()
    {
        $mollie = new MollieManager();

        $this->assertEquals(
            $mollie->normalizeAmountToInteger('1.00'),
            100
        );

        $this->assertEquals(
            $mollie->normalizeAmountToInteger('10.00'),
            1000
        );

        $this->assertEquals(
            $mollie->normalizeAmountToInteger('100.00'),
            10000
        );

        $this->assertEquals(
            $mollie->normalizeAmountToInteger('1000.00'),
            100000
        );

        $this->assertEquals(
            $mollie->normalizeAmountToInteger('10000.00'),
            1000000
        );
    }
}
