<?php

namespace Pixelpillow\LunarMollie\Tests\Unit\Managers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Lunar\Models\Transaction;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Refund;
use Mollie\Api\Types\PaymentMethod;
use Pixelpillow\LunarMollie\Actions\SetPaymentIssuerOnCart;
use Pixelpillow\LunarMollie\Actions\SetPaymentMethodOnCart;
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

        $this->mollieManager->createMolliePayment(
            $cart->calculate(),
            $transaction,
            100,
            'bancontact',
            null
        );
    }

    public function testIfWrongPaymentMethodAnExceptionIsThrown()
    {
        $this->expectException(MissingMetadataException::class);
        $this->expectExceptionMessage('Payment method xxxxx is not a valid Mollie payment method');

        // Create a cart
        $cart = CartBuilder::build();
        $transaction = new Transaction();
        $transaction->id = uniqid();

        $this->mollieManager->createMolliePayment(
            $cart->calculate(),
            $transaction,
            100,
            'xxxxx', );

    }

    public function testIfPaymentMethodIsIdealAnExceptionIsThrownWhenThereIsNoIssuerDefined()
    {
        $this->expectException(MissingMetadataException::class);
        $this->expectExceptionMessage('Payment issuer is missing.');

        // Create a cart
        $cart = CartBuilder::build();
        $transaction = new Transaction();
        $transaction->id = uniqid();

        $this->mollieManager->createMolliePayment(
            $cart->calculate(),
            $transaction,
            100,
            PaymentMethod::IDEAL,
            null
        );

    }

    public function testPaymentIsCreated()
    {
        // Create a cart
        $cart = CartBuilder::build();

        // Set the payment issuer to ABN AMRO
        $paymentIssuer = 'ideal_ABNANL2A';

        // Set the payment method to ideal
        $paymentMethod = 'ideal';

        // Set the payment issuer on the cart
        App::make(SetPaymentIssuerOnCart::class)($cart, $paymentIssuer);

        // Set the payment method on the cart
        App::make(SetPaymentMethodOnCart::class)($cart, $paymentMethod);

        $payment = new Payment($this->mollieApiClient);
        $payment->id = uniqid('tr_');
        $payment->amount = [
            'value' => '100.00',
            'currency' => 'EUR',
        ];

        $responseJsonRaw = file_get_contents(__DIR__.'/../../Stubs/MollieResponses/PaymentSuccessResponse.json');
        $responseJson = json_decode($responseJsonRaw, true);

        $responseJson['id'] = $payment->id;

        Http::fake([
            'https://api.mollie.com/*' => Http::response($responseJson),
        ]);

        $transaction = new Transaction();
        $transaction->id = uniqid();

        $response = $this->mollieManager->createMolliePayment(
            $cart->calculate(),
            $transaction,
            100,
            'ideal',
            'ideal_ABNANL2A'
        );

        $checkoutUrl = $response->getCheckoutUrl();

        $this->assertEquals(
            $response->id,
            $payment->id
        );

        $this->assertEquals(
            $response->_links->checkout->href,
            $checkoutUrl
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
            100,
            'ideal',
            'ideal_ABNANL2A'
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

    public function testCanListPaymentMethods()
    {
        $paymentMethods = [
            'count' => 13,
            '_embedded' => [
                'methods' => [
                    [
                        'resource' => 'method',
                        'id' => 'ideal',
                        'description' => 'iDEAL',
                        'minimumAmount' => [
                            'value' => '0.01',
                            'currency' => 'EUR',
                        ],
                        'maximumAmount' => [
                            'value' => '50000.00',
                            'currency' => 'EUR',
                        ],
                        'image' => [
                            'size1x' => 'https://mollie.com/external/icons/payment-methods/ideal.png',
                            'size2x' => 'https://mollie.com/external/icons/payment-methods/ideal%402x.png',
                            'svg' => 'https://mollie.com/external/icons/payment-methods/ideal.svg',
                        ],
                        'status' => 'activated',
                        'pricing' => [
                            [
                                'description' => 'Netherlands',
                                'fixed' => [
                                    'value' => '0.29',
                                    'currency' => 'EUR',
                                ],
                                'variable' => '0',
                            ],
                        ],
                        '_links' => [
                            'self' => [
                                'href' => 'https://api.mollie.com/v2/methods/ideal',
                                'type' => 'application/hal+json',
                            ],
                        ],
                    ],
                    [
                        'resource' => 'method',
                        'id' => 'creditcard',
                        'description' => 'Credit card',
                        'minimumAmount' => [
                            'value' => '0.01',
                            'currency' => 'EUR',
                        ],
                        'maximumAmount' => [
                            'value' => '2000.00',
                            'currency' => 'EUR',
                        ],
                        'image' => [
                            'size1x' => 'https://mollie.com/external/icons/payment-methods/creditcard.png',
                            'size2x' => 'https://mollie.com/external/icons/payment-methods/creditcard%402x.png',
                            'svg' => 'https://mollie.com/external/icons/payment-methods/creditcard.svg',
                        ],
                        'status' => 'activated',
                        'pricing' => [
                            [
                                'description' => 'Commercial & non-European cards',
                                'fixed' => [
                                    'value' => '0.25',
                                    'currency' => 'EUR',
                                ],
                                'variable' => '2.8',
                                'feeRegion' => 'other',
                            ],
                            [
                                'description' => 'European cards',
                                'fixed' => [
                                    'value' => '0.25',
                                    'currency' => 'EUR',
                                ],
                                'variable' => '1.8',
                                'feeRegion' => 'eu-cards',
                            ],
                            [
                                'description' => 'American Express',
                                'fixed' => [
                                    'value' => '0.25',
                                    'currency' => 'EUR',
                                ],
                                'variable' => '2.8',
                                'feeRegion' => 'amex',
                            ],
                        ],
                        '_links' => [
                            'self' => [
                                'href' => 'https://api.mollie.com/v2/methods/creditcard',
                                'type' => 'application/hal+json',
                            ],
                        ],
                    ],
                ],
            ],
            '_links' => [
                'self' => [
                    'href' => 'https://api.mollie.com/v2/methods',
                    'type' => 'application/hal+json',
                ],
                'documentation' => [
                    'href' => 'https://docs.mollie.com/reference/v2/methods-api/list-methods',
                    'type' => 'text/html',
                ],
            ],
        ];

        Http::fake([
            'https://api.mollie.com/*' => Http::response(json_encode($paymentMethods)),
        ]);

        $response = $this->mollieManager->getMolliePaymentMethods();

        $this->assertEquals(
            $response->count(),
            2
        );

        $this->assertEquals(
            $response[0]->id,
            'ideal'
        );

        $this->assertEquals(
            $response[0]->description,
            'iDEAL'
        );

        $this->assertEquals(
            $response[0]->minimumAmount->value,
            '0.01'
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
