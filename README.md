# Spryng Payments for Magento® 2

This is the official Magento® 2 plugin from Spryng Payments. By installing this plugin, you can process payments via the following methods:

* iDEAL
* PayPal
* Credit Card
* SEPA Direct Debit
* Klarna
* SOFORT
* Bancontact

To use this plugin, you need a Spryng Payments account and API key. To get these, sign up at [signup](https://www.spryngpayments.com/).
Your webshop should also support HTTPS.

## Installation

### Magento® Marketplace

This extension will also be available on the Magento® Marketplace when approved.

### Manually

1. Go to Magento® 2 root folder

2. Enter following commands to install module:

   ```
   composer require spryngpayments/magento2
   ```

   Wait while dependencies are updated.

3. Enter following commands to enable module:

   ```
   php bin/magento module:enable Spryng_Payment
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```

4. If Magento® is running in production mode, deploy static content: 

   ```
   php bin/magento setup:static-content:deploy
   ```

5. Enable and configure the Mollie extension in Magento® Admin under *Stores* >
   *Configuration* > *Sales* > *Payment Methods* > *Spryng Payments*.
