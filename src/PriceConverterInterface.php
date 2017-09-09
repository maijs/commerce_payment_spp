<?php

namespace Drupal\commerce_payment_spp;

use Drupal\commerce_price\Price;

/**
 * Interface PriceConverterInterface
 */
interface PriceConverterInterface {

  /**
   * Converts a price from decimal number to integer (e.g., 10.01 => 1001).
   *
   * @param \Drupal\commerce_price\Price $price
   *
   * @return int
   */
  public function convertDecimalToInteger(Price $price);

}
