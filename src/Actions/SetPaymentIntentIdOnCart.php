<?php

namespace Pixelpillow\LunarMollie\Actions;

use Lunar\Models\Cart;

class SetPaymentIntentIdOnCart
{
    public function __invoke(null|Cart $cart, string|int $paymentIntentId): void
    {
        if (! $cart) {
            return;
        }

        $meta = (array) $cart->meta;
        $meta['payment_intent'] = $paymentIntentId;

        $cart->meta = $meta;
        $cart->save();
    }
}
