<?php

namespace Pixelpillow\LunarMollie\Actions;

use Lunar\Models\Cart;
use Mollie\Api\Types\PaymentMethod;
use Pixelpillow\LunarMollie\Exceptions\MissingMetadataException;

class SetPaymentMethodOnCart
{
    /**
     * Set the payment issuer on the cart
     *
     * @param  Cart  $cart The cart to set the payment issuer on.
     * @param  string  $paymentMethod The payment issuer eg. "ideal"
     */
    public function __invoke(Cart $cart, string $paymentMethod): void
    {
        $uppercasePaymentMethod = strtoupper($paymentMethod);
        $paymentMethodConst = PaymentMethod::class.'::'.$uppercasePaymentMethod;

        if (defined($paymentMethodConst) === false) {
            throw new MissingMetadataException('Payment method '.$paymentMethod.' is not a valid Mollie payment method');
        }

        $meta = (array) $cart->meta;
        $meta['payment_method'] = $paymentMethod;

        $cart->meta = $meta;
        $cart->save();
    }
}
