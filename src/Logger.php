<?php

namespace Drupal\commerce_payment_spp;

use Psr\Log\LoggerInterface as PsrLoggerInterface;
use SwedbankPaymentPortal\Logger\LoggerInterface;
use SwedbankPaymentPortal\SharedEntity\Type\TransportType;

/**
 * Class Logger
 */
class Logger implements LoggerInterface {

  /** @var \Psr\Log\LoggerInterface $logger */
  protected $logger;

  /**
   * Logger constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(PsrLoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function logData($requestXml, $responseXml, $requestObject, $responseObject, TransportType $type) {
    $this->logger->log('info', 'Request sent: @request_xml', ['@request_xml' => $requestXml]);
    $this->logger->log('info', 'Response received: @response_xml', ['@response_xml' => $responseXml]);
  }

}
