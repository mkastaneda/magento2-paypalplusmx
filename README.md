# magento2-paypalplusmx
Magento 2 PayPal Plus (México)
# Installation
```
composer require qbo/module-paypalplusmx
composer update
php bin/magento setup:upgrade
```
# Configuration

- Select "Mexico" for Merchant Location under Payment Methods Configuration
- Make sure your Merchant email is configured under the Standard PayPal payment method configuration. PayPal standard method does not have to be necessarily activated, however the merchant email does.
- Enter your PayPal API credentials under PayPalPlus Module configuration
- Save and clean the cache.
