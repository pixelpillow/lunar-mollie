<?php

namespace Pixelpillow\LunarMollie\Actions;

use Lunar\Models\Cart;

class GetPaymentIntentIdFromCart
{
    public function execute(Cart $cart): ?string
    {
        if (! $cart) {
            return null;
        }

        $meta = (array) $cart->meta;

        return $meta['payment_intent'] ?? null;
    }
}
