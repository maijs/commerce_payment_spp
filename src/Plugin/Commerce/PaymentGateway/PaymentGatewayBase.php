<?php

namespace Drupal\commerce_payment_spp\Plugin\Commerce\PaymentGateway;

use Drupal\Core\Url;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use SwedbankPaymentPortal\SharedEntity\Type\TransactionResult;
use SwedbankPaymentPortal\Transaction\TransactionFrame;

/**
 * Class PaymentGatewayBase
 */
abstract class PaymentGatewayBase extends OffsitePaymentGatewayBase implements SwedbankPaymentGatewayInterface {

  /**
   * {@inheritdoc}
   */
  public function getRedirectMethod() {
    return $this->configuration['redirect_method'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'redirect_method' => 'get',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(OrderInterface $order, TransactionResult $status, TransactionFrame $transactionFrame) {
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    try {
      $payment = $payment_storage->create([
        'state' => 'completed',
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => $order->get('payment_gateway')->entity->id(),
        'order_id' => $order->id(),
        'test' => $this->getMode() == 'test',
        'remote_id' => $transactionFrame->getResponse()->getDataCashReference(),
        'remote_state' => ($status == TransactionResult::success()),
      ]);
      $payment->save();

      // Log the event.
      // @todo Use proper dependency injection.
      \Drupal::logger('commerce_payment_spp')->info('Payment for order @order_id has been added.', [
        '@order_id' => $order->id(),
      ]);
    } catch (\Exception $e) {
      watchdog_exception('commerce_payment_spp', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function completeOrder(OrderInterface $order) {
    try {
      $transition_id = 'place';

      $transitions = $order->getState()->getTransitions();
      if (isset($transitions[$transition_id]) && $next_transition = $transitions[$transition_id]) {
        $order->getState()->applyTransition($next_transition);
        $order->save();

        // Log the event.
        // @todo Use proper dependency injection.
        \Drupal::logger('commerce_payment_spp')->info('Order @order_id has been transitioned to state @state.', [
          '@order_id' => $order->id(),
          '@state' => $next_transition->getId(),
        ]);
      }
      else {
        throw new \Exception(sprintf('Transition %s for order %s could not be found.', $transition_id, $order->id()));
      }
    } catch (\Exception $e) {
      watchdog_exception('commerce_payment_spp', $e);
    }
  }

  /**
   * Builds the URL to the "return" page.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param array $options
   *
   * @return string
   */
  protected function buildReturnUrl(OrderInterface $order, array $options) {
    return Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], $options + ['absolute' => TRUE])->toString();
  }

  /**
   * Builds the URL to the "cancel" page.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param array $options
   *
   * @return string
   */
  protected function buildCancelUrl(OrderInterface $order, array $options) {
    return Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], $options + ['absolute' => TRUE])->toString();
  }

}
