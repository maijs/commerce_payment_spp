<?php

namespace Drupal\commerce_payment_spp\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment_spp\OrderTokenGeneratorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment_spp\CallbackHandler;
use Drupal\commerce_payment_spp\MerchantReferenceGeneratorInterface;
use Drupal\commerce_payment_spp\PortalConnectorInterface;
use Drupal\commerce_payment_spp\Exception\InvalidOrderTokenException;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use CommerceGuys\Addressing\Country\CountryRepositoryInterface;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\SetupRequest;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\Action;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\BillingDetails;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\CardTxn;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\CustomerDetails;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\DynamicData;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\HPSTxn;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\MerchantConfiguration;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\PersonalDetails;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\RiskDetails;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\ShippingDetails;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\ThreeDSecure;
use SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\TxnDetails;
use SwedbankPaymentPortal\CC\Type\ScreeningAction;
use SwedbankPaymentPortal\CC\Type\TransactionChannel;
use SwedbankPaymentPortal\SharedEntity\Amount;

/**
 * Provides the off-site Swedbank payment portal payment gateway (credit cards).
 *
 * @CommercePaymentGateway(
 *   id = "swedbank_payment_portal_hps",
 *   label = "Credit card (hosted page, Swedbank payment portal)",
 *   display_label = "Credit card",
 *   forms = {
 *      "offsite-payment" = "Drupal\commerce_payment_spp\PluginForm\CreditCardHpsCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 * )
 */
class CreditCardHpsPaymentGateway extends PaymentGatewayBase {

  /**
   * Hosted page IDs designate which language is used on the hosted page.
   *
   * @var array $hostedPageSetIds
   */
  protected $hostedPageSetIds = [
    'test' => [
      'en' => 164,
      'et' => 166,
      'lv' => 168,
      'lt' => 170,
      'ru' => 172,
    ],
    'live' => [
      'en' => 2207,
      'et' => 2209,
      'lv' => 2211,
      'lt' => 2213,
      'ru' => 2215,
    ],
  ];

  /** @var null|\Symfony\Component\HttpFoundation\Request $request */
  protected $request;

  /** @var \Drupal\commerce_payment_spp\PortalConnectorInterface $portalConnector */
  protected $portalConnector;

  /** @var \CommerceGuys\Addressing\Country\CountryRepositoryInterface $countryRepository */
  protected $countryRepository;

  /** @var \Drupal\commerce_payment_spp\MerchantReferenceGeneratorInterface $merchantReferenceGenerator */
  protected $merchantReferenceGenerator;

  /**
   * CreditCardHpsPaymentGateway constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   * @param \Drupal\Component\Datetime\TimeInterface $time
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   * @param \Drupal\commerce_payment_spp\PortalConnectorInterface $portal_connector
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   * @param \Drupal\commerce_payment_spp\MerchantReferenceGeneratorInterface $merchant_reference_generator
   * @param \Drupal\commerce_payment_spp\OrderTokenGeneratorInterface $order_token_generator
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, RequestStack $requestStack, PortalConnectorInterface $portal_connector, CountryRepositoryInterface $country_repository, MerchantReferenceGeneratorInterface $merchant_reference_generator, OrderTokenGeneratorInterface $order_token_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time, $order_token_generator);
    $this->request = $requestStack->getCurrentRequest();
    $this->portalConnector = $portal_connector;
    $this->countryRepository = $country_repository;
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
      $container->get('request_stack'),
      $container->get('commerce_payment_spp.portal_connector'),
      $container->get('address.country_repository'),
      $container->get('commerce_payment_spp.merchant_reference_generator'),
      $container->get('commerce_payment_spp.order_token_generator')
    );
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
      $portal->getPaymentCardHostedPagesGateway()->handlePendingTransaction($merchant_reference);
    }
    catch (InvalidOrderTokenException $e) {
      throw new PaymentGatewayException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPurchaseRequest(PaymentInterface $payment) {
    /** @var \Drupal\commerce_price\Price $price $amount */
    $amount = $payment->getAmount();

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();

    /** @var \Drupal\user\UserInterface $customer */
    $customer = $order->getCustomer();

    /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
    $billing_profile = $order->getBillingProfile();

    /** @var \Drupal\profile\Entity\ProfileInterface|null $shipping_profile */
    $shipping_profile = $this->getShippingProfile($order);

    // Create merchant reference.
    $merchant_reference = $this->merchantReferenceGenerator->createMerchantReference($order);

    // Generate order token.
    $order_token = $this->orderTokenGenerator->get($order);

    // Create URL options.
    $url_options['query']['merchant_reference'] = $merchant_reference;
    $url_options['query']['order_token'] = $order_token;

    // Connect to payment portal.
    $portal = $this->portalConnector->connect($this->getMode());
    // Get authentication instance.
    $auth = $this->portalConnector->getAuth($this->getMode());

    // Get billing details.
    $billing_details = $this->getBillingDetails($billing_profile);

    // Get personal details.
    $personal_details = $this->getPersonalDetails($billing_profile);

    // Get shipping details.
    $shipping_details = $this->getShippingDetails($shipping_profile);

    // Get risk details.
    $risk = $this->getRiskDetails($customer);

    // Get merchant configuration.
    $merchant_configuration = $this->getMerchantConfiguration();

    // Get customer details.
    $customer_details = new CustomerDetails($billing_details, $personal_details, $shipping_details, $risk);

    // Get risk action.
    $risk_action = new Action(ScreeningAction::preAuthorization(), $merchant_configuration, $customer_details);

    // Get transaction details.
    $txn_details = new TxnDetails(
      $risk_action,
      $merchant_reference,
      new Amount($amount->getNumber()),
      new ThreeDSecure(
        $this->getPurchaseDescription($order, $merchant_reference),
        $this->getStoreUrl(),
        new \DateTime()
      )
    );

    // Get hosted page service transaction container.
    $hps_txn = new HPSTxn(
      // @todo Change the first parameter to expiry URL.
      $this->buildCancelUrl($payment->getOrder(), $url_options), // <- expiry URL
      $this->buildReturnUrl($payment->getOrder(), $url_options),
      $this->buildCancelUrl($payment->getOrder(), $url_options),
      $this->getHostedPageId($order, $this->getMode()),
      new DynamicData(NULL, $this->getStoreUrl())
    );

    // Get hosted page service transaction.
    $transaction = new Transaction($txn_details, $hps_txn, new CardTxn());

    // Get setup request.
    $setupRequest = new SetupRequest($auth, $transaction);

    return $portal->getPaymentCardHostedPagesGateway()->initPayment(
      $setupRequest,
      new CallbackHandler($merchant_reference, $order->id(), $order_token)
    );
  }

  /**
   * Returns the shipping profile or NULL if no shipments are defined.
   *
   * The shipping profile is assumed to be the same for all shipments.
   * Therefore, it is taken from the first found shipment.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   */
  protected function getShippingProfile(OrderInterface $order) {
    $shipping_profile = NULL;

    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
    if ($order->shipments) {
      foreach ($order->shipments->referencedEntities() as $shipment) {
        $shipping_profile = $shipment->getShippingProfile();
        break;
      }
    }

    return $shipping_profile;
  }

  /**
   * Returns customer's billing details.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *
   * @return \SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\BillingDetails
   */
  protected function getBillingDetails(ProfileInterface $profile) {
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
    $address = $profile->address->first();

    return new BillingDetails(
      // Get state/province.
      $address->getAdministrativeArea(),
      // Get first name, last name.
      sprintf('%s %s', $address->getGivenName(), $address->getFamilyName()),
      // Get postal code.
      $address->getPostalCode(),
      // Get address line 1.
      $address->getAddressLine1(),
      // Get address line 2.
      $address->getAddressLine2(),
      // Get city.
      $address->getLocality(),
      // Get country code;
      $address->getCountryCode()
    );
  }

  /**
   * Returns customer's shipping details.
   *
   * @param \Drupal\profile\Entity\ProfileInterface|null $profile
   *
   * @return \SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\ShippingDetails
   */
  protected function getShippingDetails(ProfileInterface $profile = NULL) {
    // Provide empty values if shipping profile does not exist.
    $title = $first_name = $last_name = $address_1 = $address_2 = $city = $country = $postal_code = '';

    if ($profile) {
      /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
      $address = $profile->address->first();

      $first_name = $address->getGivenName();
      $last_name = $address->getFamilyName();
      $address_1 = $address->getAddressLine1();
      $address_2 = $address->getAddressLine2();
      $city = $address->getLocality();
      $country = $address->getCountryCode();
      $postal_code = $address->getPostalCode();
    }

    return new ShippingDetails($title, $first_name, $last_name, $address_1, $address_2, $city, $country, $postal_code);
  }


  /**
   * Returns customer's personal details.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *
   * @return \SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\PersonalDetails
   */
  protected function getPersonalDetails(ProfileInterface $profile) {
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
    $address = $profile->address->first();

    return new PersonalDetails(
      // Get first name.
      $address->getGivenName(),
      // Get last name.
      $address->getFamilyName(),
      // @todo Get phone from billing profile.
      ''
    );
  }

  /**
   * Returns risk details.
   *
   * @param \Drupal\user\UserInterface $customer
   *
   * @return \SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\RiskDetails
   */
  protected function getRiskDetails(UserInterface $customer) {
    return new RiskDetails(
      // Get customer's IP.
      $this->request->getClientIp(),
      // Get customer email.
      $customer->getEmail()
    );
  }

  /**
   * Returns merchant configuration.
   *
   * @return \SwedbankPaymentPortal\CC\HPSCommunicationEntity\SetupRequest\Transaction\MerchantConfiguration
   */
  protected function getMerchantConfiguration() {
    return new MerchantConfiguration(TransactionChannel::web(), $this->getStoreUrl());
  }

  /**
   * Returns store URL.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   */
  protected function getStoreUrl() {
    return Url::fromRoute('<front>')->toString();
  }

  /**
   * Returns purchase description which is shown on hosted page service.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param null $merchant_reference
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected function getPurchaseDescription(OrderInterface $order, $merchant_reference = NULL) {
    return $this->t('Order number: @order_number', ['@order_number' => $merchant_reference]);
  }

  /**
   * Returns hosted page id.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param $mode
   *
   * @return string|null
   *
   * @throws \Exception
   */
  protected function getHostedPageId(OrderInterface $order, $mode) {
    if (!isset($this->hostedPageSetIds[$mode])) {
      throw new \Exception(sprintf('Hosted pages for mode "%s" don\'t exist.', $mode));
    }

    $hosted_page_ids = $this->hostedPageSetIds[$mode];
    $user_preferred_langcode = $order->getCustomer()->getPreferredLangcode();

    if (array_key_exists($user_preferred_langcode, $hosted_page_ids)) {
      return $hosted_page_ids[$user_preferred_langcode];
    }
    else {
      return key($hosted_page_ids);
    }
  }

}
