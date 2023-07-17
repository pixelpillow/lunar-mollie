<?php

namespace Pixelpillow\LunarMollie\Tests\Stubs;

use Pixelpillow\LunarMollie\Generators\BaseUrlGenerator;

class TestRedirectUrlGenerator extends BaseUrlGenerator
{
    /**
     * Generate the URL.
     */
    public function generate(): string
    {
        return 'https://exampleshop.com/payment/success?transaction_id='.$this->transaction->id.'&cart_id='.$this->cart->id;
    }
}
