<?php

namespace Pixelpillow\LunarMollie\Tests\Unit\Generators;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Transaction;
use Pixelpillow\LunarMollie\Tests\TestCase;
use Pixelpillow\LunarMollie\Tests\Utils\CartBuilder;

class WebhookUrlGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function testWebhookUrlGeneratorGeneratesTheCorrectUrl()
    {
        $cart = CartBuilder::build();
        $transaction = new Transaction();
        $transaction->id = uniqid();

        $url = $this->mollieManager->getWebhookUrl($cart, $transaction);

        // TODO: This is a very basic test, but it's better than nothing.
        $this->assertEquals('https://exampleshop.com/webhook/?transaction_id='.$transaction->id.'&cart_id='.$cart->id, $url);
    }
}
