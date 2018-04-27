<?php

namespace Drupal\commerce_payment_spp\Exception;

use Drupal\commerce_payment\Exception\PaymentGatewayException;

/**
 * Thrown when return request from payment gateway contains invalid token.
 */
class InvalidOrderTokenException extends PaymentGatewayException {}

