=== Expinet Payment Gateway ===
Contributors: kaushikdomadiya
Tags: woocommerce, payment gateway, credit card, expinet, checkout
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 8.9
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A credit card payment gateway for WooCommerce using Expinet’s payment integration API.

== Description ==

Expinet Payment Gateway is a WooCommerce payment plugin that allows you to securely accept credit card payments on your store checkout page via the Expinet payment service.

**Key Features:**
- Seamless credit card payment form on the WooCommerce checkout page
- Supports:
  - Credit card only payments
  - Gift card only payments
  - Split payments (Gift card + Credit card)
- Uses Cleave.js for card input formatting
- Transaction logging system — view API request/response logs from the admin
- Custom admin log page under **WooCommerce → Expinent Payment Log**
- Loads custom CSS and JS assets only on checkout page
- Compatible with latest WordPress and WooCommerce versions

**Note:**  
This plugin currently works exclusively on the WooCommerce checkout page and is not integrated with the WordPress Block Editor (Gutenberg).

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/expinet-payment-gateway` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **WooCommerce → Settings → Payments**.
4. Enable **Expinet Payment Gateway**.
5. Configure your Expinet API credentials and other settings.
6. Test and go live.

== Frequently Asked Questions ==

= Can I use this plugin inside Gutenberg block pages? =

No — this plugin currently works only on the WooCommerce checkout page, and does not have a Gutenberg block.

= Does it support multiple payment types? =

Yes — the plugin supports:
- Credit card only payments
- Gift card only payments
- Split payments using both

= Where can I view payment logs? =

You can view Expinet API request/response logs from **WooCommerce → Expinent Payment Log** in your WordPress admin dashboard.

= Is this plugin compatible with the latest WooCommerce and WordPress versions? =

Yes — tested up to WordPress 6.5 and WooCommerce 8.9.

== Screenshots ==

1. Credit card payment form on checkout page
2. Expinet payment log listing in admin

== Changelog ==

= 1.0.0 =
* Initial stable release
* Added credit card payment form on checkout
* Integrated Cleave.js for input formatting
* Added custom admin payment log page
* Supports gift card and split payment scenarios

== Upgrade Notice ==

= 1.0.0 =
First official release.

