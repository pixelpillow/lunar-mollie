<?php

namespace Pixelpillow\LunarMollie\Generators;

use Lunar\Models\Cart;
use Lunar\Models\Transaction;

abstract class BaseUrlGenerator
{
    /**
     * The cart to generate the URL for.
     *
     * @var Cart
     */
    protected $cart;

    /**
     * The transaction to generate the URL for.
     *
     * @var Transaction
     */
    protected $transaction;

    public function __construct(Cart $cart, Transaction $transaction)
    {
        $this->cart = $cart;
        $this->transaction = $transaction;
    }

    /**
     * Generate the URL.
     */
    abstract public function generate(): string;

    /**
     * Get the cart.
     */
    public function getCart(): Cart
    {
        return $this->cart;
    }

    /**
     * Get the transaction.
     */
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }
}
