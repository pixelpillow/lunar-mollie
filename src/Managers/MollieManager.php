<?php

namespace Pixelpillow\LunarMollie\Managers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Lunar\Models\Cart;
use Lunar\Models\Transaction;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\IssuerCollection;
use Mollie\Api\Resources\MethodCollection;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Refund;
use Mollie\Api\Types\PaymentMethod;
use Pixelpillow\LunarMollie\Actions\GetPaymentIntentIdFromCart;
use Pixelpillow\LunarMollie\Actions\GetPaymentIssuerFromCart;
use Pixelpillow\LunarMollie\Actions\GetPaymentMethodFromCart;
use Pixelpillow\LunarMollie\Actions\SetPaymentIssuerOnCart;
use Pixelpillow\LunarMollie\Actions\SetPaymentMethodOnCart;
use Pixelpillow\LunarMollie\Exceptions\InvalidConfigurationException;
use Pixelpillow\LunarMollie\Exceptions\InvalidRequestException;
use Pixelpillow\LunarMollie\Exceptions\MissingMetadataException;
use Pixelpillow\LunarMollie\Generators\BaseUrlGenerator;

class MollieManager
{
    /**
     * Mollie API client
     *
     *
     * @var \Mollie\Api\MollieApiClient
     */
    protected $client;

    public function __construct()
    {

        $apiKey = Config::get('lunar.mollie.api_key');

        if (! $apiKey) {
            throw new \Exception('Mollie API key not set');
        }

        $this->client = $this->getClient();
        $this->client->setApiKey($apiKey);
    }

    /**
     * Return the Mollie client
     *
     * @return MollieApiClient
     */
    public function getClient()
    {
        if (env('APP_ENV') === 'testing') {
            $httpClient = Config::get('lunar.mollie.test_client_adaptor');

            if (! $httpClient) {
                throw new InvalidConfigurationException('Mollie test client adaptor not set in config');
            }

            $httpClient = new $httpClient();

        } else {
            $httpClient = null;
        }

        return new MollieApiClient($httpClient);
    }

    /**
     * Fetch a payment from the Mollie API.
     *
     * @param  Cart  $cart The cart to fetch the payment for.
     * @return Payment|null The Mollie payment.
     */
    protected function fetchMolliePaymentFromCart(Cart $cart): ?Payment
    {
        $paymentIntent = App::make(GetPaymentIntentIdFromCart::class)($cart);

        if (! $paymentIntent) {
            return null;
        }

        return $this->fetchMolliePayment($paymentIntent);
    }

    /**
     * Fetch a payment from the Mollie API.
     *
     * @param  string  $paymentId
     * @return Payment|null The Mollie payment.
     *
     * @throws InvalidRequestException When the payment could not be fetched.
     */
    protected function fetchMolliePayment($paymentId): ?Payment
    {
        try {
            $payment = $this->client->payments->get($paymentId);
        } catch (InvalidRequestException $e) {
            report($e);

            return null;
        }

        return $payment;
    }

    /**
     * Fetch an intent from the Mollie API.
     *
     * @param  string  $intentId
     * @return Payment|null The Mollie payment.
     */
    public function fetchIntent($intentId): ?Payment
    {
        try {
            $intent = $this->client->orders->get($intentId);
        } catch (ApiException $e) {
            return null;
        }

        return $intent;
    }

    /**
     * Create a Mollie payment
     *
     * @param  Cart  $cart The cart to create the payment for.
     * @param  Transaction  $transaction The transaction to create the payment for.
     * @param  int  $amount The amount to create the payment for.
     * @param  string  $method The payment method to create the payment for.
     * @param  string|null  $paymentIssuer The payment issuer to create the a ideal payment for. This is only used when the payment method is ideal.
     * @return Payment The Mollie payment.
     */
    public function createMolliePayment(
        Cart $cart,
        Transaction $transaction,
        int $amount,
        string $method,
        string $paymentIssuer = null,
    ): Payment {
        App::make(SetPaymentMethodOnCart::class)($cart, $method);

        if ($paymentIssuer) {
            App::make(SetPaymentIssuerOnCart::class)($cart, $paymentIssuer);
        }

        $paymentIssuer = App::make(GetPaymentIssuerFromCart::class)($cart);

        if ($method === PaymentMethod::IDEAL && ! $paymentIssuer) {
            throw new MissingMetadataException('When the payment method is iDeal, the payment issuer should be set');
        }

        return $this->client->payments->create([
            'amount' => [
                'currency' => $cart->currency->code,
                'value' => $this->normalizeAmountToString($amount),
            ],
            'description' => (string) $transaction->id,
            'redirectUrl' => $this->getRedirectUrl($cart, $transaction),
            'webhookUrl' => $this->getWebhookUrl($cart, $transaction),
            'method' => App::make(GetPaymentMethodFromCart::class)($cart),
            'issuer' => $paymentIssuer ?? null,
        ]);
    }

    /**
     * Create a Mollie refund
     *
     * @param  string  $paymentId The payment to create the Refund for.
     * @param  int  $amount The amount to create the Refund for.
     * @return Refund The Mollie Refund.
     */
    public function createMollieRefund(
        string $paymentId,
        int $amount,
        string $notes = null,
    ): Refund {

        $payment = $this->fetchMolliePayment($paymentId);

        return $payment->refund(
            [
                'amount' => [
                    'currency' => $payment->amount->currency,
                    'value' => $this->normalizeAmountToString($amount),
                ],
                'description' => $notes,
            ]
        );
    }

    /**
     * Get a list of Mollie payment issuers for iDEAL payments
     *
     * @return IssuerCollection The Mollie payment issuers.
     *
     * @see https://docs.mollie.com/reference/v2/methods-api/list-methods
     */
    public function getMolliePaymentIssuers(): ?IssuerCollection
    {
        try {
            $reponse = $this->client->methods->get(\Mollie\Api\Types\PaymentMethod::IDEAL, ['include' => 'issuers']);

            return $reponse->issuers();
        } catch (ApiException $e) {
            report($e);
        }

        return null;
    }

    /**
     * Get a list of Mollie payment methods
     *
     * @param  array  $parameters The parameters to filter the payment methods on.
     * @return \Mollie\Api\Resources\BaseCollection|\Mollie\Api\Resources\MethodCollection|null
     *
     * @see https://docs.mollie.com/reference/v2/methods-api/list-methods
     */
    public function getMolliePaymentMethods(
        array $parameters = []
    ): ?MethodCollection {
        try {
            $reponse = $this->client->methods->allActive(empty($parameters) ? $parameters : null);

            return $reponse;
        } catch (ApiException $e) {
            report($e);
        }

        return null;
    }

    /**
     * Get the redirect URL from the config
     *
     * @param  Cart  $cart The cart to get the webhook URL for.
     * @param  Transaction  $transaction The transaction to get the webhook URL for.
     * @return string The redirect URL
     *
     * @throws InvalidConfigurationException When the redirect URL is not set
     */
    public function getRedirectUrl(Cart $cart, Transaction $transaction): string
    {
        $redirectUrlGeneratorClass = Config::get('lunar.mollie.redirect_url_generator');

        if (! $redirectUrlGeneratorClass && ! class_exists($redirectUrlGeneratorClass)) {
            throw new InvalidConfigurationException('Mollie redirect URL generator not set in config');
        }

        /**
         * @var BaseUrlGenerator $redirectUrlGenerator
         */
        $redirectUrlGenerator = new $redirectUrlGeneratorClass($cart, $transaction);

        return $redirectUrlGenerator->generate();

    }

    /**
     * Get the webhook URL from the config
     *
     * @param  Cart  $cart The cart to get the webhook URL for.
     * @param  Transaction  $transaction The transaction to get the webhook URL for.
     * @return string The webhook URL
     *
     * @throws InvalidConfigurationException When the webhook URL is not set
     */
    public function getWebhookUrl(Cart $cart, Transaction $transaction): string
    {
        $webhookUrlGeneratorClass = Config::get('lunar.mollie.webhook_url_generator');

        if (! $webhookUrlGeneratorClass && ! class_exists($webhookUrlGeneratorClass)) {
            throw new InvalidConfigurationException('Mollie webhook URL generator not set in config');
        }

        /**
         * @var BaseUrlGenerator $webhookUrlGenerator
         */
        $webhookUrlGenerator = new $webhookUrlGeneratorClass($cart, $transaction);

        return $webhookUrlGenerator->generate();
    }

    public function getPayment($paymentId): Payment
    {
        return $this->client->payments->get($paymentId);
    }

    /**
     * Normalizes an amount to the correct format for Mollie to use.
     * The amount shoudn't be a integer but a string with a dot as decimal separator.
     * eg. 10.00 instead of 1000
     *
     * @see https://docs.mollie.com/reference/v2/payments-api/create-payment
     *
     * @param  int  $amount The amount in cents
     */
    public function normalizeAmountToString(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    /**
     * Normalizes an amount to a integer.
     * The amount should be a integer without a dot as decimal separator.
     * eg. 1000 instead of 10.00
     * This is the opposite of normalizeAmountToString
     */
    public function normalizeAmountToInteger(string $amount): int
    {
        return (int) str_replace('.', '', $amount);
    }
}
