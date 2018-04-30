<?php

namespace Drupal\commerce_payment_spp\Plugin\Commerce\PaymentGateway;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment_spp\CallbackHandler;
use Drupal\commerce_payment_spp\MerchantReferenceGeneratorInterface;
use Drupal\commerce_payment_spp\OrderTokenGeneratorInterface;
use Drupal\commerce_payment_spp\PriceConverterInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment_spp\BanklinkManagerInterface;
use Drupal\commerce_payment_spp\PortalConnectorInterface;
use Drupal\commerce_payment_spp\Exception\InvalidOrderTokenException;
use SwedbankPaymentPortal\BankLink\PurchaseBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the off-site Swedbank payment portal payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "swedbank_payment_portal_banklink",
 *   label = "Banklink (Swedbank payment portal)",
 *   display_label = "Banklink",
 *   forms = {
 *      "offsite-payment" = "Drupal\commerce_payment_spp\PluginForm\BanklinkCheckoutForm",
 *   },
 *   payment_method_types = {"swedbank_payment_portal_banklink"},
 * )
 */
class BanklinkPaymentGateway extends PaymentGatewayBase implements BanklinkPaymentGatewayInterface {

  /** @var \Drupal\commerce_payment_spp\BanklinkManagerInterface $banklinkManager */
  protected $banklinkManager;

  /** @var \Drupal\commerce_payment_spp\PortalConnectorInterface $portalConnector */
  protected $portalConnector;

  /** @var \Drupal\Core\Entity\EntityStorageInterface $currencyStorage */
  protected $currencyStorage;

  /** @var \Drupal\commerce_payment_spp\PriceConverterInterface $priceConverter */
  protected $priceConverter;

  /** @var \Drupal\commerce_payment_spp\MerchantReferenceGeneratorInterface $merchantReferenceGenerator */
  protected $merchantReferenceGenerator;

  /**
   * BanklinkPaymentGateway constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   * @param \Drupal\Component\Datetime\TimeInterface $time
   * @param \Drupal\commerce_payment_spp\BanklinkManagerInterface $banklink_manager
   * @param \Drupal\commerce_payment_spp\PortalConnectorInterface $portal_connector
   * @param \Drupal\commerce_payment_spp\PriceConverterInterface $price_converter
   * @param \Drupal\commerce_payment_spp\MerchantReferenceGeneratorInterface $merchant_reference_generator
   * @param \Drupal\commerce_payment_spp\OrderTokenGeneratorInterface $order_token_generator
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, BanklinkManagerInterface $banklink_manager, PortalConnectorInterface $portal_connector, PriceConverterInterface $price_converter, MerchantReferenceGeneratorInterface $merchant_reference_generator, OrderTokenGeneratorInterface $order_token_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time, $order_token_generator);
    $this->banklinkManager = $banklink_manager;
    $this->portalConnector = $portal_connector;
    $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');
    $this->priceConverter = $price_converter;
    $this->merchantReferenceGenerator = $merchant_reference_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('plugin.manager.commerce_payment_spp.banklink'),
      $container->get('commerce_payment_spp.portal_connector'),
      $container->get('commerce_payment_spp.price_converter'),
      $container->get('commerce_payment_spp.merchant_reference_generator'),
      $container->get('commerce_payment_spp.order_token_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBanklinkId() {
    return $this->configuration['banklink'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBanklinkPlugin() {
    return $this->banklinkManager->createInstance($this->getBanklinkId());
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'banklink' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['banklink'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => $this->t('Banklink'),
      '#options' => array_map(function($item) {
        $banklink = $this->banklinkManager->createInstance($item['id']);
        return $banklink->getLabel();
      }, $this->banklinkManager->getDefinitions()),
      '#default_value' => $this->getBanklinkId(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['banklink'] = $values['banklink'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    try {
      // Validate return request.
      $this->validateReturnRequest($order, $request);

      // Get merchant reference.
      $merchant_reference = $request->query->get('merchant_reference');

      // Connect to payment portal.
      $portal = $this->portalConnector->connect($this->getMode());

      // Handle pending transactions. To complete the purchase, set the logic
      // in callback handler class.
      $portal->getBankLinkGateway()->handlePendingTransaction($merchant_reference);
    }
    catch (InvalidOrderTokenException $e) {
      throw new PaymentGatewayException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    try {
      $xml = $request->getContent();

      // Connect to payment portal.
      $portal = $this->portalConnector->connect($this->getMode());

      // Handle notification.
      $portal->getBankLinkGateway()->handleNotification($xml);
    } catch (\Exception $e) {
      watchdog_exception('commerce_payment_spp', $e);
    }

    return new Response('<Response>OK</Response>', 200);
  }

  /**
   * {@inheritdoc}
   */
  public function createPurchaseRequest(PaymentInterface $payment) {
    /** @var \Drupal\commerce_price\Price $price $amount */
    $amount = $payment->getAmount();

    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
    $currency = $this->currencyStorage->load($amount->getCurrencyCode());

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();

    /** @var \Drupal\commerce_payment_spp\Plugin\Commerce\SwedbankPaymentPortal\Banklink\BanklinkInterface $banklink */
    $banklink = $this->getBanklinkPlugin();

    // Create merchant reference.
    $merchant_reference = $this->merchantReferenceGenerator->createMerchantReference($order);

    // Generate order token.
    $order_token = $this->orderTokenGenerator->get($order);

    // Create URL options.
    $url_options['query']['merchant_reference'] = $merchant_reference;
    $url_options['query']['order_token'] = $order_token;

    // Connect to payment portal.
    $portal = $this->portalConnector->connect($this->getMode());

    // Create purchase request.
    $purchase_request = (new PurchaseBuilder())
      ->setDescription(sprintf('%s (%s)', $order->getStore()->getName(), $merchant_reference))
      ->setAmountValue($this->priceConverter->convertDecimalToInteger($amount))
      ->setAmountExponent(2)
      ->setAmountCurrencyCode($currency->getNumericCode())
      ->setConsumerEmail($order->getCustomer()->getEmail())
      ->setServiceType($banklink->getServiceType())
      ->setPaymentMethod($banklink->getPaymentMethod())
      ->setSuccessUrl($this->buildReturnUrl($payment->getOrder(), $url_options))
      ->setFailureUrl($this->buildCancelUrl($payment->getOrder(), $url_options))
      ->setMerchantReference($merchant_reference)
      ->setLanguage($this->getPurchaseRequestLanguage($order))
      ->setPageSetId(1)
      ->getPurchaseRequest();

    return $portal->getBankLinkGateway()->initPayment(
      $purchase_request,
      new CallbackHandler($merchant_reference, $order->id(), $order_token)
    );
  }

  /**
   * Returns purchase request language.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return string|null
   */
  protected function getPurchaseRequestLanguage(OrderInterface $order) {
    $supported_languages = $this->getBanklinkPlugin()->getSupportedLanguages();
    $user_preferred_langcode = $order->getCustomer()->getPreferredLangcode();

    if (in_array($user_preferred_langcode, $supported_languages)) {
      return $user_preferred_langcode;
    }

    return NULL;
  }

}
