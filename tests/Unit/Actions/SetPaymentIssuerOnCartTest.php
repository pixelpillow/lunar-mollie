<?php

namespace Pixelpillow\LunarMollie\Tests\Unit\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Pixelpillow\LunarMollie\Actions\SetPaymentIssuerOnCart;
use Pixelpillow\LunarMollie\Tests\TestCase;
use Pixelpillow\LunarMollie\Tests\Utils\CartBuilder;

class SetPaymentIssuerOnCartTest extends TestCase
{
    use RefreshDatabase;

    public function testSetPaymentIssuerOnCart()
    {
        // Create a cart
        $cart = CartBuilder::build();

        // Set the payment issuer to ABN AMRO
        $payment_issuer = 'ideal_ABNANL2A';

        // Set the payment issuer on the cart
        App::make(SetPaymentIssuerOnCart::class)($cart, $payment_issuer);

        $cart->refresh();

        $meta = (array) $cart->meta;

        $this->assertEquals($payment_issuer, $meta['payment_issuer']);

    }
}
