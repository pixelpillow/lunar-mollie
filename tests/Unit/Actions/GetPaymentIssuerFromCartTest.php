<?php

namespace Pixelpillow\LunarMollie\Tests\Unit\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Pixelpillow\LunarMollie\Actions\GetPaymentIssuerFromCart;
use Pixelpillow\LunarMollie\Actions\SetPaymentIssuerOnCart;
use Pixelpillow\LunarMollie\Tests\TestCase;
use Pixelpillow\LunarMollie\Tests\Utils\CartBuilder;

class GetPaymentIssuerFromCartTest extends TestCase
{
    use RefreshDatabase;

    public function testGetPaymentIssuerFromCart()
    {
        // Create a cart
        $cart = CartBuilder::build();

        // Set the payment intent id
        $paymentIssuerId = uniqid('ideal_');

        App::make(SetPaymentIssuerOnCart::class)($cart, $paymentIssuerId);

        $cart->refresh();

        $paymentIssuerFromCart = App::make(GetPaymentIssuerFromCart::class)($cart);

        $this->assertEquals($paymentIssuerId, $paymentIssuerFromCart);
    }
}
