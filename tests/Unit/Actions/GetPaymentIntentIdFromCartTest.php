<?php

namespace Pixelpillow\LunarMollie\Tests\Unit\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Pixelpillow\LunarMollie\Actions\GetPaymentIntentIdFromCart;
use Pixelpillow\LunarMollie\Actions\SetPaymentIntentIdOnCart;
use Pixelpillow\LunarMollie\Tests\TestCase;
use Pixelpillow\LunarMollie\Tests\Utils\CartBuilder;

class GetPaymentIntentIdFromCartTest extends TestCase
{
    use RefreshDatabase;

    public function testGetPaymentIntentIdFromCart()
    {
        // Create a cart
        $cart = CartBuilder::build();

        // Set the payment intent id
        $paymentIntentId = uniqid('pi_');

        App::make(SetPaymentIntentIdOnCart::class)($cart, $paymentIntentId);

        $cart->refresh();

        $paymentIntentIdFromCart = App::make(GetPaymentIntentIdFromCart::class)($cart);

        $this->assertEquals($paymentIntentId, $paymentIntentIdFromCart);
    }
}
