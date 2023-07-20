<?php

namespace Pixelpillow\LunarMollie\Actions;

use Lunar\Models\Cart;
use Pixelpillow\LunarMollie\Exceptions\MissingMetadataException;

class GetPaymentIssuerFromCart
{
    /**
     * Get the payment issuer from the cart
     */
    public function __invoke(Cart $cart): ?string
    {
        if (! $cart) {
            return null;
        }

        if (! isset($cart->meta['payment_issuer'])) {
            throw new MissingMetadataException('Payment issuer is missing.');
        }

        return $cart->meta['payment_issuer'];
    }
}
