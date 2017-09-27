# Magento 2: qbo/module-paypalplusmx payment module (MX)
Magento 2 PayPal Plus (México)

# Installation

From the root folder of your project:
```
composer require qbo/module-paypalplusmx
php bin/magento setup:upgrade
```
If something goes wrong with dependencies, and youre OK to ignore it,  add the following line to your composer.json under "require" section:
```
"qbo/module-paypalplusmx": "dev-master"
```
Then update your dependencies
```
composer update [--ignore-platform-reqs]
```

# Configuration

- Select "Mexico" for Merchant Location under Payment Methods Configuration
- Get your REST APP Credentiasl from https://developer.paypal.com
- Enter your PayPal API credentials under PayPalPlus Module configuration.
- Set Sandbox/Live Mode
- Save and clean the cache.
- A Profile Experience ID should be automatically created, if it is not created on the first save, try again.

# Upgrading

From the root folder of your project:
```
composer update [--ignore-platform-reqs]
php bin/magento setup:upgrade
rm -rf var/generation var/di var/view_preprocessed pub/static
php bin/magento setup:static-content:deploy
```
# Debugging

- The module has a configuration "Debug Mode" which logs everything going out to PAYPAL APIs, error responses and details
