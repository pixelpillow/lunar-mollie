<?php

namespace Pixelpillow\LunarMollie;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Models\Transaction;
use Lunar\PaymentTypes\AbstractPayment;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Api\Types\RefundStatus;
use Pixelpillow\LunarMollie\Actions\SetPaymentIntentIdOnCart;
use Pixelpillow\LunarMollie\Exceptions\InvalidRequestException;
use Pixelpillow\LunarMollie\Managers\MollieManager;

class MolliePaymentType extends AbstractPayment
{
    /**
     * The Mollie instance.
     *
     * @var MollieManager
     */
    protected $mollie;

    /**
     * The Mollie Payment
     */
    protected Payment $molliePayment;

    /**
     * Initialise the payment type.
     */
    public function __construct($mollieManager = null)
    {
        $this->mollie = $mollieManager ?? new MollieManager();
    }

    /**
     * Authorize the payment for processing.
     */
    public function authorize(): PaymentAuthorize
    {
        if (! $this->order) {
            if (! $this->order = $this->cart->order) {
                $this->order = $this->cart->createOrder();
            }
        }

        if ($this->order->placed_at) {
            // Somethings gone wrong!
            return new PaymentAuthorize(
                success: false,
                message: 'This order has already been placed',
            );
        }

        $this->molliePayment = $this->mollie->getPayment(
            $this->data['payment_id']
        );

        $this->setPaymentIntentIdOnCart();

        if (! $this->isReadyToBeReleased()) {
            return new PaymentAuthorize(
                success: false,
                message: 'Payment not approved',
            );
        }

        return $this->releaseSuccess();
    }

    /**
     * Set the payment intent id on the cart.
     */
    protected function setPaymentIntentIdOnCart(): void
    {
        App::make(SetPaymentIntentIdOnCart::class)($this->cart, $this->molliePayment->id);
    }

    /**
     * Release the payment for processing.
     */
    protected function isReadyToBeReleased(): bool
    {
        return in_array($this->molliePayment->status, [
            PaymentStatus::STATUS_PAID,
            PaymentStatus::STATUS_AUTHORIZED,
        ]);
    }

    /**
     * Capture a payment for a transaction.
     */
    public function capture(Transaction $transaction, $amount = 0): PaymentCapture
    {
        try {
            $payment = $this->mollie->createMolliePayment(
                $transaction->order->cart,
                $transaction,
                $amount
            );
        } catch (InvalidRequestException $e) {
            report($e);

            return new PaymentCapture(
                success: false,
                message: $e->getMessage()
            );
        }

        $transaction->order->transactions()->create([
            'parent_transaction_id' => $transaction->id,
            'success' => ! in_array($payment->status, [
                PaymentStatus::STATUS_FAILED,
                PaymentStatus::STATUS_CANCELED,
            ]),
            'type' => 'capture',
            'driver' => 'mollie',
            'amount' => $amount,
            'reference' => $payment->id,
            'status' => 'succeeded',
            'notes' => $payment->description,
            'captured_at' => Carbon::parse($payment->paidAt),
            'card_type' => 'ideal',
        ]);

        return new PaymentCapture(success: true);
    }

    /**
     * Refund a captured transaction
     *
     * @param  string|null  $notes
     */
    public function refund(Transaction $transaction, int $amount = 0, $notes = null): PaymentRefund
    {
        try {
            $refund = $this->mollie->createMollieRefund($transaction->reference, $amount
            );
        } catch (ApiException $e) {
            return new PaymentRefund(
                success: false,
                message: $e->getMessage()
            );
        }

        $transaction->order->transactions()->create([
            'success' => $refund->status !== RefundStatus::STATUS_FAILED,
            'type' => 'refund',
            'driver' => 'mollie',
            'amount' => $this->mollie->normalizeAmountToInteger($refund->amount->value),
            'reference' => $refund->id,
            'status' => $refund->status,
            'notes' => $notes,
            'card_type' => 'ideal',
            'meta' => $refund->metadata,
        ]);

        return new PaymentRefund(
            success: true
        );
    }

    /**
     * Return a successfully released payment.
     */
    private function releaseSuccess(): PaymentAuthorize
    {
        DB::transaction(function () {
            $this->order->update([
                'status' => $this->config['released'] ?? 'paid',
                'placed_at' => now(),
            ]);

            $this->createTransaction(
                $this->molliePayment,
                'capture',
                [
                    'parent_transaction_id' => null,
                ]
            );
        });

        return new PaymentAuthorize(success: true);
    }

    protected function createTransaction(
        Payment $payment,
        string $type,
        array $data = []
    ): void {
        $this->order->transactions()->create([
            'success' => $this->isSuccessful($payment),
            'type' => $type,
            'driver' => 'molie',
            'amount' => $this->mollie->normalizeAmountToInteger($payment->amount),
            'reference' => $payment->id,
            'status' => $payment->status,
            'notes' => $payment->description,
            'captured_at' => $type === 'capture' ? Carbon::parse($payment->paidAt) : null,
            'card_type' => 'ideal',
            ...$data,
        ]);
    }

    /**
     * Check if is successful.
     *
     * @param  Payment  $payment The Mollie payment
     */
    public function isSuccessful(Payment $payment): bool
    {
        $notSuccessful = [
            PaymentStatus::STATUS_OPEN,
            PaymentStatus::STATUS_FAILED,
            PaymentStatus::STATUS_CANCELED,
            PaymentStatus::STATUS_EXPIRED,
            PaymentStatus::STATUS_PENDING,
        ];

        return ! in_array($payment->status, $notSuccessful);

    }
}
