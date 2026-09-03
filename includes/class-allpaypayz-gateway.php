<?php

if (!defined('ABSPATH')) {
    exit;
}

use Allpaypayz\Exception\AllpaypayzException;
use Allpaypayz\Allpaypayz;

final class Allpaypayz_WC_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id                 = 'allpaypayz';
        $this->icon               = apply_filters('allpaypayz_wc_icon', '');
        $this->has_fields         = false;
        $this->method_title       = __('Allpaypayz', 'allpaypayz-woocommerce');
        $this->method_description = __('Accept payments via Allpaypayz.', 'allpaypayz-woocommerce');
        $this->supports           = ['products', 'refunds'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title          = $this->get_option('title');
        $this->description    = $this->get_option('description');
        $this->enabled        = $this->get_option('enabled');
        $this->api_key        = $this->get_option('api_key');
        $this->sign_key       = $this->get_option('sign_key');
        $this->base_url       = $this->get_option('base_url');
        $this->payment_method = $this->get_option('payment_method');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable / Disable', 'allpaypayz-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable Allpaypayz', 'allpaypayz-woocommerce'),
                'default' => 'no',
            ],
            'title' => [
                'title'       => __('Title', 'allpaypayz-woocommerce'),
                'type'        => 'text',
                'description' => __('Shown on the checkout page.', 'allpaypayz-woocommerce'),
                'default'     => __('Pay with Allpaypayz', 'allpaypayz-woocommerce'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'   => __('Description', 'allpaypayz-woocommerce'),
                'type'    => 'textarea',
                'default' => __('You will be redirected to Allpaypayz to complete payment.', 'allpaypayz-woocommerce'),
            ],
            'api_key' => [
                'title'       => __('API key', 'allpaypayz-woocommerce'),
                'type'        => 'password',
                'description' => __('Your Allpaypayz terminal API key (sk_...).', 'allpaypayz-woocommerce'),
                'desc_tip'    => true,
            ],
            'sign_key' => [
                'title'       => __('Webhook sign key', 'allpaypayz-woocommerce'),
                'type'        => 'password',
                'description' => __('Symmetric key Allpaypayz uses to sign webhook deliveries.', 'allpaypayz-woocommerce'),
                'desc_tip'    => true,
            ],
            'base_url' => [
                'title'   => __('API environment', 'allpaypayz-woocommerce'),
                'type'    => 'select',
                'options' => [
                    'https://api4.allpaypayz.com'         => __('Production', 'allpaypayz-woocommerce'),
                    'https://staging-api4.allpaypayz.com' => __('Staging', 'allpaypayz-woocommerce'),
                ],
                'default' => 'https://api4.allpaypayz.com',
            ],
            'payment_method' => [
                'title'   => __('Payment method', 'allpaypayz-woocommerce'),
                'type'    => 'text',
                'default' => 'card',
            ],
        ];
    }

    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return ['result' => 'failure', 'redirect' => ''];
        }

        if (!class_exists(Allpaypayz::class)) {
            wc_add_notice(__('Allpaypayz SDK not installed.', 'allpaypayz-woocommerce'), 'error');
            return ['result' => 'failure', 'redirect' => ''];
        }

        try {
            $client = new Allpaypayz(apiKey: (string) $this->api_key, baseUrl: (string) $this->base_url);
            $payment = $client->payments->createRedirect([
                'merchant_reference' => 'WC-' . $order->get_id(),
                'amount' => [
                    'amount_minor' => (int) round((float) $order->get_total() * 100),
                    'currency'     => $order->get_currency(),
                ],
                'description'    => sprintf(__('WooCommerce order #%d', 'allpaypayz-woocommerce'), $order->get_id()),
                'payment_method' => (string) $this->payment_method,
                'customer' => [
                    'name'  => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                    'email' => $order->get_billing_email(),
                    'phone' => $order->get_billing_phone(),
                ],
                'urls' => [
                    'success'  => $this->get_return_url($order),
                    'error'    => wc_get_checkout_url(),
                    'callback' => WC()->api_request_url('allpaypayz'),
                ],
                'extra_data' => [
                    'wc_order_id'  => (string) $order->get_id(),
                    'wc_order_key' => $order->get_order_key(),
                ],
            ]);
        } catch (AllpaypayzException $e) {
            wc_add_notice(sprintf(
                __('Payment initiation failed: %s', 'allpaypayz-woocommerce'),
                $e->errorCode,
            ), 'error');
            return ['result' => 'failure', 'redirect' => ''];
        }

        $checkoutUrl = $payment['checkout_url'] ?? null;
        if (!$checkoutUrl) {
            wc_add_notice(__('Allpaypayz did not return a checkout URL.', 'allpaypayz-woocommerce'), 'error');
            return ['result' => 'failure', 'redirect' => ''];
        }

        $order->update_meta_data('_allpaypayz_payment_id', $payment['id'] ?? '');
        $order->save();

        return ['result' => 'success', 'redirect' => $checkoutUrl];
    }

    public function process_refund($order_id, $amount = null, $reason = ''): bool|WP_Error
    {
        $order = wc_get_order($order_id);
        $paymentId = $order ? (string) $order->get_meta('_allpaypayz_payment_id') : '';
        if (!$paymentId) {
            return new WP_Error('allpaypayz', __('No Allpaypayz payment id stored on order.', 'allpaypayz-woocommerce'));
        }
        try {
            $client = new Allpaypayz(apiKey: (string) $this->api_key, baseUrl: (string) $this->base_url);
            $body = ['reason' => $reason ?: 'merchant_requested'];
            if ($amount !== null) {
                $body['amount'] = [
                    'amount_minor' => (int) round((float) $amount * 100),
                    'currency'     => $order->get_currency(),
                ];
            }
            $client->payments->createRefund($paymentId, $body);
            return true;
        } catch (AllpaypayzException $e) {
            return new WP_Error('allpaypayz', $e->getMessage());
        }
    }

    /** @var string */ public $api_key = '';
    /** @var string */ public $sign_key = '';
    /** @var string */ public $base_url = '';
    /** @var string */ public $payment_method = '';
}
