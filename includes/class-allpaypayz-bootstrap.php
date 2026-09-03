<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Allpaypayz_WC_Bootstrap
{
    public static function init(): void
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', static function () {
                echo '<div class="notice notice-error"><p>'
                    . esc_html__('Allpaypayz for WooCommerce requires WooCommerce to be active.', 'allpaypayz-woocommerce')
                    . '</p></div>';
            });
            return;
        }

        $autoload = ALLPAYPAYZ_WC_PLUGIN_DIR . 'vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }

        require_once ALLPAYPAYZ_WC_PLUGIN_DIR . 'includes/class-allpaypayz-gateway.php';
        require_once ALLPAYPAYZ_WC_PLUGIN_DIR . 'includes/class-allpaypayz-webhook.php';

        add_filter('woocommerce_payment_gateways', static function (array $gateways): array {
            $gateways[] = 'Allpaypayz_WC_Gateway';
            return $gateways;
        });

        add_action('woocommerce_api_allpaypayz', ['Allpaypayz_WC_Webhook', 'handle']);
    }
}
