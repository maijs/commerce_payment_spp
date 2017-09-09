<?php

namespace Drupal\commerce_payment_spp\Plugin\Commerce\SwedbankPaymentPortal\Banklink;

use Drupal\Component\Plugin\PluginBase;

/**
 * Class Banklink
 */
class Banklink extends PluginBase implements BanklinkInterface {

  /** @var string $serviceTypeClass */
  protected $serviceTypeClass = '\SwedbankPaymentPortal\BankLink\CommunicationEntity\Type\ServiceType';

  /** @var string $paymentMethodClass */
  protected $paymentMethodClass = '\SwedbankPaymentPortal\BankLink\CommunicationEntity\Type\PaymentMethod';

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceTypeCallback() {
    return $this->pluginDefinition['service_type_callback'];
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceType() {
    return call_user_func([$this->serviceTypeClass, $this->getServiceTypeCallback()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethodCallback() {
    return $this->pluginDefinition['payment_method_callback'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethod() {
    return call_user_func([$this->paymentMethodClass, $this->getPaymentMethodCallback()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedLanguages() {
    return $this->pluginDefinition['supported_languages'];
  }

}
