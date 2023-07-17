<?php

namespace Pixelpillow\LunarMollie\Tests\Stubs;

use Pixelpillow\LunarMollie\Generators\BaseUrlGenerator;

class TestWebhookUrlGenerator extends BaseUrlGenerator
{
    /**
     * Generate the URL.
     */
    public function generate(): string
    {

        $orderId = $this->getTransaction()->id;
        $cartId = $this->getCart()->id;

        return "https://exampleshop.com/webhook/?transaction_id={$orderId}&cart_id={$cartId}";
    }
}
