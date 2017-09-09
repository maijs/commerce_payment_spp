<?php

namespace Drupal\commerce_payment_spp;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Interface MerchantReferenceGeneratorInterface
 */
interface MerchantReferenceGeneratorInterface {

  /**
   * Returns merchant reference.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return string
   */
  public function createMerchantReference(OrderInterface $order);

}
