<?php

namespace Pixelpillow\LunarMollie\Tests\Unit\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Mollie\Api\Types\PaymentMethod;
use Pixelpillow\LunarMollie\Actions\GetPaymentMethodFromCart;
use Pixelpillow\LunarMollie\Actions\SetPaymentMethodOnCart;
use Pixelpillow\LunarMollie\Tests\TestCase;
use Pixelpillow\LunarMollie\Tests\Utils\CartBuilder;

class GetPaymentMethodFromCartTest extends TestCase
{
    use RefreshDatabase;

    public function testGetPaymentMethodFromCart()
    {
        // Create a cart
        $cart = CartBuilder::build();

        // Set the payment method
        $paymentMethod = PaymentMethod::BANCONTACT;

        App::make(SetPaymentMethodOnCart::class)($cart, $paymentMethod);

        $cart->refresh();

        $paymentMethodFromCart = App::make(GetPaymentMethodFromCart::class)($cart);

        $this->assertEquals($paymentMethod, $paymentMethodFromCart);
    }
}
