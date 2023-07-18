<?php

namespace Pixelpillow\LunarMollie\Actions;

use Lunar\Models\Cart;

class SetPaymentIssuerOnCart
{
    /**
     * Set the payment issuer on the cart
     *
     * @param  Cart  $cart The cart to set the payment issuer on.
     * @param  string  $paymentIssuer The payment issuer to set. e.g. ideal_ABNANL2A
     */
    public function __invoke(Cart $cart, string $paymentIssuer): void
    {
        $meta = (array) $cart->meta;
        $meta['payment_issuer'] = $paymentIssuer;

        $cart->meta = $meta;
        $cart->save();
    }
}
