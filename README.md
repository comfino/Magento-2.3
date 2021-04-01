# TakPay for Magento 2.3

TakPay payment module adds to Magento the ability to make purchases in installments as an additional payment system in the cart.
After completing the order and selecting the payment channel "TakPay", the user is redirected to the page where he fills out the appropriate credit application.

## Requirements
 * Magento 2.3
 * PHP 7.1+
 * cURL

## Installation

1. Clone the repository
2. Move to app/code/Comperia/ComperiaGateway.
3. Execute: 

```bash
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```
4. Admin panel **Stores/Configuration/Sales/Payment Methods**
5. Settings "TakPay": API-KEY.
