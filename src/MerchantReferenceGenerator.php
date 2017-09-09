<?php

namespace Drupal\commerce_payment_spp;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Class MerchantReferenceGenerator
 */
class MerchantReferenceGenerator implements MerchantReferenceGeneratorInterface {

  /** @var int $maxLength */
  protected $maxLength = 16;

  /**
   * {@inheritdoc}
   */
  public function createMerchantReference(OrderInterface $order) {
    // Get order number.
    $order_number = sprintf('%s-', $order->id());
    // Prepare merchant reference suffix.
    $suffix_pad_length = $this->maxLength - strlen($order_number);
    $suffix = str_pad(strtotime('now'), $suffix_pad_length, 0, STR_PAD_LEFT);

    return substr(sprintf('%s%s', $order_number, $suffix), 0, $this->maxLength);
  }

}
