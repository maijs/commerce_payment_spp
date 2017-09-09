<?php

namespace Drupal\commerce_payment_spp\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CheckoutFormBase
 */
class CheckoutFormBase extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment_spp\Plugin\Commerce\PaymentGateway\SwedbankPaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    // Create purchase request.
    $purchase_request = $payment_gateway_plugin->createPurchaseRequest($payment);
    // Get redirect URL.
    $redirect_url = $purchase_request->getCustomerRedirectUrl();

    return $this->buildRedirectForm($form, $form_state, $redirect_url, [], $payment_gateway_plugin->getRedirectMethod());
  }

}
