<?php
/**
 * Plugin Name: Allpaypayz for WooCommerce
 * Plugin URI:  https://allpaypayz.com
 * Description: Accept payments via Allpaypayz (cards, redirect, alternative methods) inside WooCommerce.
 * Version:     0.1.0
 * Author:      Allpaypayz
 * License:     MIT
 * Text Domain: allpaypayz-woocommerce
 * Requires PHP: 8.1
 * Requires at least: 6.0
 * WC requires at least: 7.0
 * WC tested up to: 9.5
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ALLPAYPAYZ_WC_PLUGIN_FILE', __FILE__);
define('ALLPAYPAYZ_WC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALLPAYPAYZ_WC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ALLPAYPAYZ_WC_VERSION', '0.1.0');

require_once ALLPAYPAYZ_WC_PLUGIN_DIR . 'includes/class-allpaypayz-bootstrap.php';

add_action('plugins_loaded', ['Allpaypayz_WC_Bootstrap', 'init']);
