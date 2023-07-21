<?php

namespace Pixelpillow\LunarMollie\Actions;

use Lunar\Models\Cart;

class SetPaymentMethodOnCart
{
    /**
     * Set the payment issuer on the cart
     *
     * @param  Cart  $cart The cart to set the payment issuer on.
     * @param  string  $paymentMethod The payment issuer eg. "ideal"
     */
    public function __invoke(Cart $cart, string $paymentMethode): void
    {
        $meta = $cart->meta->toArray();
        $meta['payment_method'] = $paymentMethode;

        $cart->meta = $meta;
        $cart->save();
    }
}
