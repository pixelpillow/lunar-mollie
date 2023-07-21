<?php

namespace Pixelpillow\LunarMollie\Actions;

use Lunar\Models\Cart;

class SetPaymentIntentIdOnCart
{
    public function __invoke(?Cart $cart, string|int $paymentIntentId): void
    {
        if (! $cart) {
            return;
        }

        $meta = $cart->meta->toArray();
        $meta['payment_intent'] = $paymentIntentId;

        $cart->meta = $meta;
        $cart->save();
    }
}
