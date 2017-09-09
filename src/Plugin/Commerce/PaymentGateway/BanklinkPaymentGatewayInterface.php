<?php

namespace Drupal\commerce_payment_spp\Plugin\Commerce\PaymentGateway;

/**
 * Interface BanklinkPaymentGatewayInterface
 */
interface BanklinkPaymentGatewayInterface extends SwedbankPaymentGatewayInterface {

  /**
   * Returns banklink plugin ID.
   *
   * @return string
   */
  public function getBanklinkId();

  /**
   * Returns banklink plugin instance.
   *
   * @return \Drupal\commerce_payment_spp\Plugin\Commerce\SwedbankPaymentPortal\Banklink\BanklinkInterface
   */
  public function getBanklinkPlugin();

  /**
   * Returns redirect method;
   *
   * @return string
   */
  public function getRedirectMethod();

}
