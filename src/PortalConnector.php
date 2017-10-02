<?php

namespace Drupal\commerce_payment_spp;

use Drupal\Core\Config\ConfigFactoryInterface;
use SwedbankPaymentPortal\Logger\LoggerInterface;
use SwedbankPaymentPortal\Options\CommunicationOptions;
use SwedbankPaymentPortal\Options\ServiceOptions;
use SwedbankPaymentPortal\SharedEntity\Authentication;
use SwedbankPaymentPortal\SwedbankPaymentPortal;

/**
 * Class PortalConnector
 */
class PortalConnector implements PortalConnectorInterface {

  /** @var \Drupal\Core\Config\ImmutableConfig $sppConfig */
  protected $sppConfig;

  /** @var \SwedbankPaymentPortal\Logger\LoggerInterface $logger */
  protected $logger;

  /**
   * PortalConnector constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param $configuration_key
   * @param \SwedbankPaymentPortal\Logger\LoggerInterface $logger
   */
  public function __construct(ConfigFactoryInterface $config_factory, $configuration_key, LoggerInterface $logger) {
    $this->sppConfig = $config_factory->get($configuration_key);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function connect($mode) {
    $environment_values = $this->sppConfig->get(sprintf('environment.%s', $mode));

    $options = new ServiceOptions(
      new CommunicationOptions($environment_values['url']),
      $this->getAuth($mode),
      $this->logger
    );

    return new SwedbankPaymentPortal($options);
  }

  /**
   * {@inheritdoc}
   */
  public function getAuth($mode) {
    $environment_values = $this->sppConfig->get(sprintf('environment.%s', $mode));

    return new Authentication($environment_values['username'], $environment_values['password']);
  }

}
