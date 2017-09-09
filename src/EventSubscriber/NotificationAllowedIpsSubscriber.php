<?php

namespace Drupal\commerce_payment_spp\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class NotificationAllowedIpsSubscriber
 */
class NotificationAllowedIpsSubscriber implements EventSubscriberInterface {

  /** @var \Symfony\Component\HttpFoundation\Request $request */
  protected $request;

  /** @var \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch */
  protected $currentRouteMatch;

  /** @var \Drupal\Core\Config\ImmutableConfig $sppConfig */
  protected $sppConfig;

  /** @var \Psr\Log\LoggerInterface $logger */
  protected $logger;

  /**
   * NotificationAllowedIpsSubscriber constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param $configuration_key
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(RequestStack $request_stack, CurrentRouteMatch $current_route_match, ConfigFactoryInterface $config_factory, $configuration_key, LoggerInterface $logger) {
    $this->request = $request_stack->getCurrentRequest();
    $this->currentRouteMatch = $current_route_match;
    $this->sppConfig = $config_factory->get($configuration_key);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onNotification'];

    return $events;
  }

  /**
   * Denies access to "commerce_payment.notify" route is client's IP is not
   * in allowed IP list.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function onNotification(GetResponseEvent $event) {
    if ($this->currentRouteMatch->getRouteName() == 'commerce_payment.notify') {
      $client_ip = $this->request->getClientIp();
      $allowed_ips = $this->sppConfig->get('notification.allowed_ips');

      if (!in_array($client_ip, $allowed_ips)) {
        $this->logger->warning('Access to notification is denied because IP @ip is not allowed.', ['@ip' => $client_ip]);
        throw new AccessDeniedHttpException();
      }
    }
  }


}
