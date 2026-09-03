# Allpaypayz for WooCommerce

**[⬇ Download the latest version](https://github.com/allpaypayz/allpaypayz-cms-woocommerce/archive/refs/heads/main.zip)** · [Browse the code](https://github.com/allpaypayz/allpaypayz-cms-woocommerce) · [MIT](LICENSE)

<sub>The archive is a snapshot of `main` — the current state of the plugin. Tagged releases will appear on the Releases page once the code leaves alpha.</sub>


WordPress plugin that adds Allpaypayz as a payment gateway on the WooCommerce
checkout. Uses the bundled [`allpaypayz/sdk`](https://github.com/allpaypayz/allpaypayz-sdk-php) (PHP SDK) for every
Allpaypayz-side call.

> Status: **alpha** (v0.1.0). Targets WordPress 6.0+ / WooCommerce 7.0+ on PHP 8.1+.

## Install

1. Upload `cms-woocommerce/` to `wp-content/plugins/allpaypayz-woocommerce/`.
2. Inside the plugin directory:
   ```bash
   composer require allpaypayz/sdk guzzlehttp/guzzle
   ```
   This pulls the PHP SDK + a PSR-18 client into `vendor/`. The bootstrap
   class loads `vendor/autoload.php` on every request.
3. Activate **Allpaypayz for WooCommerce** in **Plugins**.
4. **WooCommerce → Settings → Payments → Allpaypayz** — fill in the API key,
   sign key, environment, and payment method.
5. Register the webhook URL with Allpaypayz:
   `https://your-shop.example.com/?wc-api=allpaypayz`

## How it works

- `allpaypayz-woocommerce.php` — plugin header; defers init to `plugins_loaded`.
- `includes/class-allpaypayz-bootstrap.php` — verifies WooCommerce is active,
  loads composer, registers the gateway, registers the webhook endpoint via
  `add_action('woocommerce_api_allpaypayz', ...)`.
- `includes/class-allpaypayz-gateway.php` — `Allpaypayz_WC_Gateway` extends
  `WC_Payment_Gateway`:
  - `init_form_fields()` declares the admin settings.
  - `process_payment($order_id)` calls
    `client->payments->createRedirect(...)` with
    `merchant_reference: WC-<order_id>` and returns
    `['result' => 'success', 'redirect' => $checkout_url]`.
  - `process_refund(...)` calls `client->payments->createRefund(...)` —
    WooCommerce admin refund UI is wired automatically thanks to
    `supports => ['refunds']`.
  - Stores the Allpaypayz payment id in `_allpaypayz_payment_id` order meta.
- `includes/class-allpaypayz-webhook.php` — `Allpaypayz_WC_Webhook::handle()` runs
  on the `woocommerce_api_allpaypayz` endpoint, verifies the signature via
  `Allpaypayz\Webhooks::verify`, and applies the resulting state change.

## Event-to-status mapping

| v4 `event.type` | WooCommerce action |
|---|---|
| `payment.succeeded`, `order.completed` | `$order->payment_complete()` |
| `payment.failed`, `payment.cancelled`, `order.cancelled`, `order.expired` | `$order->update_status('failed', ...)` |
| `payment.refunded`, `payment.partially_refunded`, `refund.succeeded` | order note (operator follow-up) |

## Files

```
cms-woocommerce/
├── README.md
├── composer.json
├── readme.txt                            — WP.org plugin metadata
├── allpaypayz-woocommerce.php               — plugin header + init hook
├── includes/
│   ├── class-allpaypayz-bootstrap.php       — registers gateway + webhook
│   ├── class-allpaypayz-gateway.php         — WC_Payment_Gateway subclass
│   └── class-allpaypayz-webhook.php         — signed webhook receiver
├── languages/                            — .po / .mo translations
└── assets/                               — gateway icon (optional)
```

## License

MIT
