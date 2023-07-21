<?php

namespace Pixelpillow\LunarMollie\Tests\Unit\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Pixelpillow\LunarMollie\Actions\SetPaymentIntentIdOnCart;
use Pixelpillow\LunarMollie\Tests\TestCase;
use Pixelpillow\LunarMollie\Tests\Utils\CartBuilder;

class SetPaymentIntentIdOnCartTest extends TestCase
{
    use RefreshDatabase;

    public function testSetPaymentIntentIdOnCart()
    {
        // Create a cart
        $cart = CartBuilder::build();

        // Set the payment intent id
        $paymentIntentId = uniqid('pi_');

        App::make(SetPaymentIntentIdOnCart::class)($cart, $paymentIntentId);

        $cart->refresh();

        $meta = (array) $cart->meta;

        $this->assertEquals($paymentIntentId, $meta['payment_intent']);
    }
}
