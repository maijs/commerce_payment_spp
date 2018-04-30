# Commerce Swedbank Payment Portal

This module integrates [Swedbank Payment Portal](https://www.swedbank.lv/business/cash/ecommerce/paymentPortal?language=ENG)
payment methods (banklink, credit card) as payment gateways with [Drupal Commerce](https://www.drupal.org/project/commerce).

## Requirements

This module depends on [Swedbank Payment Portal API library for PHP](https://github.com/Swedbank-SPP/swedbank-payment-portal).

## Installation

1. Run <code>composer require drupal/commerce_payment_spp</code> in your Drupal project to download the module. This
will automatically install [Swedbank Payment Portal API library for PHP](https://github.com/Swedbank-SPP/swedbank-payment-portal).

2. Install the module by running `drush en -y commerce_payment_spp`.

## Configuration

1. Go to `/admin/commerce/config/payment-gateways/add`.
2. Select the preferred payment gateway (banklink, credit card).
3. Go to `/admin/commerce/config/payment-gateways/swedbank-payment-portal-settings` and enter your Swedbank Payment
Portal credentials.
