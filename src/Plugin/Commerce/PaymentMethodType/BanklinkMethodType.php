<?php

namespace Drupal\commerce_payment_spp\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the banklink payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "swedbank_payment_portal_banklink",
 *   label = @Translation("Banklink"),
 *   create_label = @Translation("Banklink"),
 * )
 */
class BanklinkMethodType extends PaymentMethodTypeBase  {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    return 'Banklink';
  }

}
