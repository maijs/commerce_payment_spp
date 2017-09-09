<?php

namespace Drupal\commerce_payment_spp;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Defines Commerce Swedbank Payment Portal banklink plugin manager.
 */
class BanklinkManager extends DefaultPluginManager implements BanklinkManagerInterface {

  /**
   * Provides default values.
   *
   * @var array
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
    'country' => '',
    'service_type_callback' => '',
    'payment_method_callback' => '',
    'supported_languages' => [],
    'class' => 'Drupal\commerce_payment_spp\Plugin\Commerce\SwedbankPaymentPortal\Banklink\Banklink',
  ];

  /** @var array $requiredDefinitionProperties */
  protected $requiredDefinitionProperties = [
    'label',
    'country',
    'service_type_callback',
    'payment_method_callback',
  ];

  /**
   * BanklinkManager constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->factory = new ContainerFactory($this, '\Drupal\commerce_payment_spp\Plugin\Commerce\SwedbankPaymentPortal\Banklink\BanklinkInterface');
    $this->moduleHandler = $module_handler;
    $this->alterInfo('commerce_payment_spp_banklink_plugins');
    $this->setCacheBackend($cache_backend, 'commerce_payment_spp_banklink_plugins', ['commerce_payment_spp_banklink_plugins']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $yaml_discovery = new YamlDiscovery('commerce_payment_spp.banklink', $this->moduleHandler->getModuleDirectories());
      $yaml_discovery->addTranslatableProperty('label');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($yaml_discovery);
    }

    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach ($this->requiredDefinitionProperties as $property) {
      if (!isset($definition[$property]) || $definition[$property] == '') {
        throw new PluginException(sprintf('Banklink "%s" definition must include "%s" property.', $plugin_id, $property));
      }
    }
  }

}
