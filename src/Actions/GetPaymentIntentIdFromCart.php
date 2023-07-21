<?php

namespace Pixelpillow\LunarMollie\Actions;

use Lunar\Models\Cart;

class GetPaymentIntentIdFromCart
{
    /**
     * Get the payment intent id from the cart
     */
    public function __invoke(Cart $cart): ?string
    {
        if (! $cart) {
            return null;
        }

        $meta = $cart->meta->toArray();

        return $meta['payment_intent'] ?? null;
    }
}
