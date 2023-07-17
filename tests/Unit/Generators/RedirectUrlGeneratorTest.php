<?php

namespace Pixelpillow\LunarMollie\Tests\Unit\Generators;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Transaction;
use Pixelpillow\LunarMollie\Tests\TestCase;
use Pixelpillow\LunarMollie\Tests\Utils\CartBuilder;

class RedirectUrlGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function testRedirectUrlGenerator()
    {
        $cart = CartBuilder::build();
        $transaction = new Transaction();
        $transaction->id = uniqid();

        $url = $this->mollieManager->getRedirectUrl($cart, $transaction);

        $this->assertEquals('https://exampleshop.com/payment/success?transaction_id='.$transaction->id.'&cart_id='.$cart->id, $url);

    }
}
