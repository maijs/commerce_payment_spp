<?php

namespace Drupal\commerce_payment_spp;

/**
 * Interface PortalInterface
 */
interface PortalConnectorInterface {

  /**
   * Returns a connection to Swedbank Payment Portal.
   *
   * @param $mode
   *
   * @return \SwedbankPaymentPortal\SwedbankPaymentPortal
   */
  public function connect($mode);

  /**
   * Returns an instance of Authentication object.
   *
   * @param $mode
   *
   * @return \SwedbankPaymentPortal\SharedEntity\Authentication
   */
  public function getAuth($mode);

}
