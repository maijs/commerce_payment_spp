# Commerce Swedbank Payment Portal

This module integrates [Swedbank Payment Portal](https://www.swedbank.lv/business/cash/ecommerce/paymentPortal?language=ENG) payment methods (banklink, credit card) as payment gateways in [Drupal
Commerce](https://www.drupal.org/project/commerce).

## Installation

1. Add the following dependency in your `composer.json` file:
    ```json
    "require": {
        "drupal/commerce_payment_spp": "@dev"
    }
    ```
    and run `composer update`.

    Alternatively you can add dependency directly by running `composer require drupal/commerce_payment_spp:@dev`.

2. Install module `Commerce Swedbank Payment Portal`.

## Configuration

1. Go to `/admin/commerce/config/payment-gateways/add`.
2. Select the preferred payment gateway (banklink, credit card).
3. Go to `/admin/commerce/config/payment-gateways/swedbank-payment-portal-settings` and input Swedbank credentials.
