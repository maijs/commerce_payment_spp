<?php

namespace Drupal\commerce_payment_spp;

use Drupal\commerce_price\Price;

/**
 * Class PriceConverter
 */
class PriceConverter implements PriceConverterInterface {

  /**
   * {@inheritdoc}
   *
   * @todo Re-think the implementation and do not assume that price number
   * is two digit decimal.
   */
  public function convertDecimalToInteger(Price $price) {
    $number = $price->getNumber() * 100;
    return (int) $number;
  }

}
