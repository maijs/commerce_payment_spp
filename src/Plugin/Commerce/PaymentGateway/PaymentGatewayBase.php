<?php

namespace Drupal\commerce_payment_spp\Plugin\Commerce\PaymentGateway;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment_spp\OrderTokenGeneratorInterface;
use Drupal\commerce_payment_spp\Exception\InvalidOrderTokenException;
use SwedbankPaymentPortal\SharedEntity\Type\TransactionResult;
use SwedbankPaymentPortal\Transaction\TransactionFrame;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PaymentGatewayBase
 */
abstract class PaymentGatewayBase extends OffsitePaymentGatewayBase implements SwedbankPaymentGatewayInterface {

  /** @var \Drupal\commerce_payment_spp\OrderTokenGeneratorInterface $orderTokenGenerator */
  protected $orderTokenGenerator;

  /**
   * PaymentGatewayBase constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   * @param \Drupal\Component\Datetime\TimeInterface $time
   * @param \Drupal\commerce_payment_spp\OrderTokenGeneratorInterface $order_token_generator
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, OrderTokenGeneratorInterface $order_token_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->orderTokenGenerator = $order_token_generator;
  }

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
   * Validates return request.
   *
   * Return request should contain order token which is generated when
   * purchase request was created.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @throws \Drupal\commerce_payment_spp\Exception\InvalidOrderTokenException
   *
   * @see \Drupal\commerce_payment_spp\Plugin\Commerce\PaymentGateway\BanklinkPaymentGateway::createPurchaseRequest()
   * @see \Drupal\commerce_payment_spp\Plugin\Commerce\PaymentGateway\CreditCardHpsPaymentGateway::createPurchaseRequest()
   */
  protected function validateReturnRequest(OrderInterface $order, Request $request) {
    // Order token should be a string, hence the default value is set to "".
    $order_token = $request->query->get('order_token', '');

    if (!$this->orderTokenGenerator->validate($order_token, $order)) {
      throw new InvalidOrderTokenException(sprintf('Order token "%s" is not valid for this request (order ID %d).', $order_token, $order->id()));
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
