<?php

namespace Pixelpillow\LunarMollie\Actions;

use Lunar\Models\Cart;
use Pixelpillow\LunarMollie\Exceptions\MissingMetadataException;

class GetPaymentMethodFromCart
{
    /**
     * Get the payment payment_method from the cart
     */
    public function __invoke(Cart $cart): ?string
    {
        if (! $cart) {
            return null;
        }

        $meta = $cart->meta->toArray();

        if (! isset($meta['payment_method'])) {
            throw new MissingMetadataException('Payment payment_method is missing.');
        }

        return $meta['payment_method'];
    }
}
