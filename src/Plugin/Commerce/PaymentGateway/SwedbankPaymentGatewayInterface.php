<?php

namespace Drupal\commerce_payment_spp\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use SwedbankPaymentPortal\SharedEntity\Type\TransactionResult;
use SwedbankPaymentPortal\Transaction\TransactionFrame;

/**
 * Interface SwedbankPaymentGatewayInterface
 */
interface SwedbankPaymentGatewayInterface {

  /**
   * Returns redirect method.
   */
  public function getRedirectMethod();

  /**
   * Creates purchase request.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *
   * @return \SwedbankPaymentPortal\BankLink\CommunicationEntity\PurchaseResponse\PurchaseResponse
   */
  public function createPurchaseRequest(PaymentInterface $payment);

  /**
   * Creates payment entity.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param \SwedbankPaymentPortal\SharedEntity\Type\TransactionResult $status
   * @param \SwedbankPaymentPortal\Transaction\TransactionFrame $transactionFrame
   */
  public function createPayment(OrderInterface $order, TransactionResult $status, TransactionFrame $transactionFrame);

  /**
   * Completes order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   */
  public function completeOrder(OrderInterface $order);

}
