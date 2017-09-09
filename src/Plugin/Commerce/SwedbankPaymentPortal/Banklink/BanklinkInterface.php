<?php

namespace Drupal\commerce_payment_spp\Plugin\Commerce\SwedbankPaymentPortal\Banklink;

/**
 * Interface BanklinkInterface
 */
interface BanklinkInterface {

  /**
   * Returns banklink plugin ID.
   *
   * @return string
   */
  public function getId();

  /**
   * Returns banklink plugin label.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Returns service type callback method name.
   *
   * @return string
   */
  public function getServiceTypeCallback();

  /**
   * Returns service type object.
   *
   * @return \SwedbankPaymentPortal\BankLink\CommunicationEntity\Type\ServiceType
   */
  public function getServiceType();

  /**
   * Returns payment method callback method name.
   *
   * @return string
   */
  public function getPaymentMethodCallback();

  /**
   * Returns payment method object.
   *
   * @return \SwedbankPaymentPortal\BankLink\CommunicationEntity\Type\PaymentMethod
   */
  public function getPaymentMethod();

  /**
   * Returns supported language codes.
   *
   * @return array
   */
  public function getSupportedLanguages();

}
