<?php

namespace Drupal\commerce_payment_spp;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Interface OrderTokenGeneratorInterface
 */
interface OrderTokenGeneratorInterface {

  /**
   * Returns a token based on the order values.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return string
   */
  public function get(OrderInterface $order);

  /**
   * Validates the token against the originating order.
   *
   * @param $token
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return bool
   */
  public function validate($token, OrderInterface $order);

}
