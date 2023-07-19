<?php

namespace Pixelpillow\LunarMollie\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Models\Transaction;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Types\PaymentStatus;
use Pixelpillow\LunarMollie\MolliePaymentType;
use Pixelpillow\LunarMollie\Tests\TestCase;
use Pixelpillow\LunarMollie\Tests\Utils\CartBuilder;

/**
 * @group mollie.payments
 */
class MolliePaymentTypeTest extends TestCase
{
    use RefreshDatabase;

    public function testAnOrderIsCaptured()
    {
        $cart = CartBuilder::build();
        $payment = new MolliePaymentType($this->mollieManager);

        $mollieMockPayment = new Payment($this->mollieApiClient);
        $mollieMockPayment->id = uniqid('tr_');
        $mollieMockPayment->status = PaymentStatus::STATUS_PAID;
        $mollieMockPayment->amount = '100.00';

        Http::fake([
            'https://api.mollie.com/*' => Http::response(json_encode($mollieMockPayment)),
        ]);

        /**
         * @var PaymentAuthorize
         */
        $response = $payment->cart($cart->refresh())->withData([
            'payment_id' => $mollieMockPayment->id,
        ])->authorize();

        $this->assertInstanceOf(PaymentAuthorize::class, $response);

        $this->assertTrue($response->success);

        $this->assertNotNull($cart->refresh()->order->placed_at);

        $this->assertEquals($mollieMockPayment->id, $cart->meta->payment_intent);

        $this->assertDatabaseHas((new Transaction)->getTable(), [
            'order_id' => $cart->refresh()->order->id,
            'type' => 'capture',
        ]);
    }

    /**
     * @group thisone
     */
    public function testHandleFailedPayment()
    {
        $cart = CartBuilder::build();

        $payment = new MolliePaymentType($this->mollieManager);

        $mollieMockPayment = new Payment($this->mollieApiClient);
        $mollieMockPayment->id = uniqid('tr_');
        $mollieMockPayment->status = PaymentStatus::STATUS_FAILED;
        $mollieMockPayment->amount = '100.00';

        Http::fake([
            'https://api.mollie.com/*' => Http::response(json_encode($mollieMockPayment)),
        ]);

        /**
         * @var PaymentAuthorize
         */
        $response = $payment->cart($cart)->withData([
            'payment_id' => 'tr_1234567890',
        ])->authorize();

        $this->assertInstanceOf(PaymentAuthorize::class, $response);
        $this->assertFalse($response->success);
        $this->assertNull($cart->refresh()->order->placed_at);

        $this->assertDatabaseMissing((new Transaction)->getTable(), [
            'order_id' => $cart->refresh()->order->id,
            'type' => 'capture',
        ]);
    }

    public function testsPaymentIsSuccessful()
    {
        $mollieMockPayment = new Payment($this->mollieApiClient);

        $mollieMockPayment->status = PaymentStatus::STATUS_OPEN;

        $paymentIsSuccessful = (new MolliePaymentType)->isSuccessful($mollieMockPayment);

        $this->assertFalse($paymentIsSuccessful);

        $mollieMockPayment->status = PaymentStatus::STATUS_PAID;

        $paymentIsSuccessful = (new MolliePaymentType)->isSuccessful($mollieMockPayment);

        $this->assertTrue($paymentIsSuccessful);
    }
}
