=== Allpaypayz for WooCommerce ===
Contributors: allpaypayz
Tags: payments, gateway, woocommerce, allpaypayz, cards
Requires at least: 6.0
Requires PHP: 8.1
Tested up to: 6.7
Stable tag: 0.1.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Allpaypayz payment gateway integration for WooCommerce.

== Description ==

Accept payments via Allpaypayz inside WooCommerce. The customer is redirected to
the Allpaypayz hosted checkout, and order status is updated automatically from
the signed webhook delivery.

Features:

* Redirect-style checkout flow (no card data in your store)
* Webhook signature verification (HMAC-SHA256, 300 s tolerance)
* Full refund + partial refund support via WooCommerce admin
* English + Russian translations
* Tested against WooCommerce 7+ on PHP 8.1+

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. From the plugin directory, run `composer require allpaypayz/sdk
   guzzlehttp/guzzle` so the bundled SDK lands in `vendor/`.
3. Activate the plugin through the WordPress *Plugins* screen.
4. Go to *WooCommerce → Settings → Payments → Allpaypayz* and enter:
   * **API key** — `sk_...` token from your Allpaypayz dashboard
   * **Webhook sign key** — symmetric secret
   * **API environment** — Production / Staging
5. Register the webhook URL with Allpaypayz:
   `https://your-shop.example.com/?wc-api=allpaypayz`

== Changelog ==

= 0.1.0 =
* Initial release.
