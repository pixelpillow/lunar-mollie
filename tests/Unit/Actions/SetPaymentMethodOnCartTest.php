<?php

namespace Pixelpillow\LunarMollie\Tests\Unit\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Pixelpillow\LunarMollie\Actions\GetPaymentMethodFromCart;
use Pixelpillow\LunarMollie\Actions\SetPaymentMethodOnCart;
use Pixelpillow\LunarMollie\Tests\TestCase;
use Pixelpillow\LunarMollie\Tests\Utils\CartBuilder;

class SetPaymentMethodOnCartTest extends TestCase
{
    use RefreshDatabase;

    public function testSetPaymentMethodOnCart()
    {
        // Create a cart
        $cart = CartBuilder::build();

        // Set the payment method to ideal
        $payment_method = 'ideal';

        // Set the payment method on the cart
        App::make(SetPaymentMethodOnCart::class)($cart, $payment_method);

        $cart->refresh();

        $this->assertEquals($payment_method, App::make(GetPaymentMethodFromCart::class)($cart));

    }
}
