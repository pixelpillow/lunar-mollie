<?php

namespace Pixelpillow\LunarMollie\Actions;

use Lunar\Models\Cart;

class SetPaymentIssuerOnCart
{
    /**
     * Set the payment issuer on the cart
     *
     * @param  null|Cart  $cart The cart to set the payment issuer on.
     * @param  string  $paymentIssuer The payment issuer to set. e.g. ideal_ABNANL2A
     */
    public function __invoke(null|Cart $cart, string $paymentIssuer): void
    {
        if (! $cart) {
            return;
        }

        $meta = (array) $cart->meta;
        $meta['payment_issuer'] = $paymentIssuer;

        $cart->meta = $meta;
        $cart->save();
    }
}
