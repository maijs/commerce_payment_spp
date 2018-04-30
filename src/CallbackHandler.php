<?php

namespace Drupal\commerce_payment_spp;

use SwedbankPaymentPortal\CallbackInterface;
use SwedbankPaymentPortal\CC\PaymentCardTransactionData;
use SwedbankPaymentPortal\SharedEntity\Type\TransactionResult;
use SwedbankPaymentPortal\Transaction\TransactionFrame;

/**
 * Class CallbackHandler
 */
class CallbackHandler implements CallbackInterface {

  /** @var string $merchant_reference */
  private $merchant_reference;

  /** @var integer $order_id */
  private $order_id;

  /** @var string $order_token */
  private $order_token;

  /**
   * CallbackHandler constructor.
   *
   * @param $merchant_reference
   * @param $order_id
   * @param $order_token
   */
  public function __construct($merchant_reference, $order_id, $order_token) {
    $this->merchant_reference = $merchant_reference;
    $this->order_id = $order_id;
    $this->order_token = $order_token;
  }

  /**
   * {@inheritdoc}
   */
  public function handleFinishedTransaction(TransactionResult $status, TransactionFrame $transactionFrame, PaymentCardTransactionData $creditCardTransactionData = NULL) {
    if ($status == TransactionResult::success()) {
      try {
        // Make sure stale pending transactions don't halt the process.
        /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
        if ($order = \Drupal::entityTypeManager()->getStorage('commerce_order')->load($this->order_id)) {
          $payment_gateway = $order->get('payment_gateway')->entity;

          /** @var \Drupal\commerce_payment_spp\Plugin\Commerce\PaymentGateway\BanklinkPaymentGatewayInterface $payment_gateway_plugin */
          $payment_gateway_plugin = $payment_gateway->getPlugin();

          // Create payment.
          $payment_gateway_plugin->createPayment($order, $status, $transactionFrame);

          // Complete order.
          $payment_gateway_plugin->completeOrder($order);
        }
        else {
          throw new \Exception(sprintf('Order "%s" does not exist.', $this->order_id));
        }
      } catch (\Exception $e) {
        watchdog_exception('commerce_payment_spp', $e);
      }
    } else if ($status == TransactionResult::failure()) {
      // Act on failed transaction.
    } else {
      // Act on other transaction status.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function serialize() {
    return json_encode(
      [
        'merchant_reference' => $this->merchant_reference,
        'order_id' => $this->order_id,
        'order_token' => $this->order_token,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($serialized) {
    $data = json_decode($serialized);

    $this->merchant_reference = $data->merchant_reference;
    $this->order_id = $data->order_id;
    $this->order_token = $data->order_token;
  }

}
