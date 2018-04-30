<?php

namespace Drupal\commerce_payment_spp;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;

/**
 * Class OrderTokenGenerator
 */
class OrderTokenGenerator implements OrderTokenGeneratorInterface {

  /**
   * {@inheritdoc}
   */
  public function get(OrderInterface $order) {
    return $this->compute($order);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($token, OrderInterface $order) {
    return Crypt::hashEquals($this->compute($order), $token);
  }

  /**
   * Computes the token from the order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return string
   */
  protected function compute(OrderInterface $order) {
    return Crypt::hmacBase64($this->getValue($order), Settings::getHashSalt());
  }

  /**
   * Returns the hashable value of the order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return null|string
   */
  protected function getValue(OrderInterface $order) {
    return $order->uuid();
  }

}
