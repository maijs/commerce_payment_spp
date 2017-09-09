<?php

namespace Drupal\commerce_payment_spp\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PaymentPortalSettingsForm
 */
class PaymentPortalSettingsForm extends ConfigFormBase {

  /**
   * PaymentPortalSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'swedbank_payment_portal_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_payment_spp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_payment_spp.settings');

    $environments = [
      'test' => $this->t('Test'),
      'live' => $this->t('Live'),
    ];

    foreach ($environments as $environment => $label) {
      $form[$environment] = [
        '#type' => 'details',
        '#title' => $label,
        '#open' => TRUE,
        '#tree' => TRUE,
      ];

      $form[$environment]['username'] = [
        '#type' => 'textfield',
        '#title' => t('Username'),
        '#default_value' => $config->get(sprintf('environment.%s.username', $environment)),
      ];

      $form[$environment]['password'] = [
        '#type' => 'password',
        '#title' => t('Password'),
        '#default_value' => $config->get(sprintf('environment.%s.password', $environment)),
      ];

      $form[$environment]['url'] = [
        '#type' => 'url',
        '#title' => t('URL'),
        '#default_value' => $config->get(sprintf('environment.%s.url', $environment)),
      ];
    }

    $form['notification'] = [
      '#type' => 'details',
      '#title' => $this->t('Notification'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['notification']['allowed_ips'] = [
      '#type' => 'textarea',
      '#title' => t('Alowed IPs'),
      '#description' => t('Specify allowed IPs from which notification can be sent (one per line).'),
      '#default_value' => implode("\n", $config->get('notification.allowed_ips')),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Add validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('commerce_payment_spp.settings');

    // Save environment settings.
    foreach (['test', 'live'] as $environment) {
      $values = $form_state->getValue([$environment]);
      $config
        ->set(sprintf('environment.%s.username', $environment), $values['username'])
        ->set(sprintf('environment.%s.password', $environment), $values['password'])
        ->set(sprintf('environment.%s.url', $environment), $values['url']);
    }

    // Save notification settings.
    $notification_values = array_filter(array_map(function ($value) {
      return trim($value);
    }, explode("\r\n", $form_state->getValue(['notification', 'allowed_ips']))));

    $config->set('notification.allowed_ips', $notification_values);

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
