=== Payment Gateway for Expinet and for WooCommerce ===
Contributors: khushdoms
Tags: woocommerce, payment gateway, credit card, expinet, checkout
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 8.9
Requires Plugins: woocommerce
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A credit card payment gateway for WooCommerce using Expinet’s payment integration API.

== Description ==

Expinet Gateway for Woocommerce payment plugin that allows you to securely accept credit card payments on your store checkout page via the Expinet payment service.

**Key Features:**
- Seamless credit card payment form on the WooCommerce checkout page
- Supports:
  - Credit card only payments
  - Gift card only payments
  - Split payments (Gift card + Credit card)
- Uses Vanilla-Masker for card input formatting
- Transaction logging system — view API request/response logs from the admin
- Custom admin log page under **WooCommerce → Expinet Payment Log**
- Loads custom CSS and JS assets only on checkout page
- Compatible with latest WordPress and WooCommerce versions

== External services ==

This plugin connects to the Expinet Payment Gateway API to process credit card transactions for WooCommerce orders.

= What data is sent =
When a customer checkout using this payment gateway, the following data is securely transmitted to Expinet:
- Cardholder name
- Credit card number, expiry date, and CVV
- Billing address
- Order amount and currency
- Store identification credentials (merchant ID, API keys configured by the site owner)

= When data is sent =
This data is sent only when a customer initiates a payment through the Expinet gateway at checkout. No data is sent otherwise.

= Where data is sent =
The data is sent securely over HTTPS to the Expinet servers:
- `https://api.expinet.com` (Production)
- `https://sandbox.expinet.com` (Sandbox, if configured)

= Documentation =
Expinet Payment Link : https://docs.expinet.net/developers/quick-start

**Note:**  
This plugin currently works exclusively on the WooCommerce checkout page and is not integrated with the WordPress Block Editor (Gutenberg).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/payment-gateway-expinet-and-woocommerce-integration` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **WooCommerce → Settings → Payments**.
4. Enable **Payment Gateway for Expinet and for WooCommerce**.
5. Configure your Expinet API credentials and other settings.
6. Test and go live.

== Source Code ==

This plugin uses the [Vanilla Masker](https://github.com/BankFacil/vanilla-masker) JavaScript library (v1.1.1). The original uncompressed source file is included in the `/js/` folder for review.

Build tools or minification methods are not used in this plugin. Only vanilla JavaScript is included.

== Frequently Asked Questions ==

= Can I use this plugin inside Gutenberg block pages? =

No — this plugin currently works only on the WooCommerce checkout page, and does not have a Gutenberg block.

= Does it support multiple payment types? =

Yes — the plugin supports:
- Credit card only payments
- Gift card only payments
- Split payments using both

= Where can I view payment logs? =

You can view Expinet API request/response logs from **WooCommerce → Expinet Payment Log** in your WordPress admin dashboard.

= Is this plugin compatible with the latest WooCommerce and WordPress versions? =

Yes — tested up to WordPress 6.5 and WooCommerce 8.9.

== Screenshots ==

1. Credit card payment form on checkout page
2. Expinet payment log listing in admin
3. Expinet payment credentials settings in admin

== Changelog ==

= 1.0.0 =
* Initial stable release
* Added credit card payment form on checkout
* Integrated vanilla-masker.min.js for input formatting
* Added custom admin payment log page
* Supports gift card and split payment scenarios

== Upgrade Notice ==

= 1.0.0 =
First official release.

