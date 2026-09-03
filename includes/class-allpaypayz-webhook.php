<?php

if (!defined('ABSPATH')) {
    exit;
}

use Allpaypayz\Exception\WebhookException;
use Allpaypayz\Webhooks;

final class Allpaypayz_WC_Webhook
{
    public static function handle(): void
    {
        $gateways = WC()->payment_gateways()->payment_gateways();
        $gateway = $gateways['allpaypayz'] ?? null;
        if (!$gateway || empty($gateway->sign_key)) {
            status_header(500);
            exit('allpaypayz_not_configured');
        }

        $rawBody = file_get_contents('php://input') ?: '';
        $sigHeader = $_SERVER['HTTP_CALLBACK_SIGNATURE'] ?? '';

        try {
            $event = Webhooks::verify(
                rawBody: $rawBody,
                signatureHeader: $sigHeader,
                signKey: (string) $gateway->sign_key,
            );
        } catch (WebhookException $e) {
            status_header(400);
            exit($e->errorCode);
        }

        $resource = $event['resource'] ?? null;
        $reference = is_array($resource) ? ($resource['merchant_reference'] ?? null) : null;
        if (!is_string($reference) || !preg_match('/^WC-(\d+)$/', $reference, $m)) {
            status_header(200);
            echo '{}';
            return;
        }
        $orderId = (int) $m[1];
        $order = wc_get_order($orderId);
        if (!$order) {
            status_header(200);
            echo '{}';
            return;
        }
        self::applyEvent($order, (string) ($event['type'] ?? ''));
        status_header(200);
        header('Content-Type: application/json');
        echo '{}';
    }

    private static function applyEvent(WC_Order $order, string $type): void
    {
        if (in_array($type, ['payment.succeeded', 'order.completed'], true)) {
            if (!$order->is_paid()) {
                $order->payment_complete();
                $order->add_order_note(__('Allpaypayz: payment confirmed via webhook.', 'allpaypayz-woocommerce'));
            }
            return;
        }
        if (in_array($type, ['payment.failed', 'payment.cancelled', 'order.cancelled', 'order.expired'], true)) {
            $order->update_status('failed', sprintf(
                __('Allpaypayz: %s', 'allpaypayz-woocommerce'),
                $type,
            ));
            return;
        }
        if (in_array($type, ['payment.refunded', 'refund.succeeded'], true)) {
            $order->add_order_note(__('Allpaypayz: refund event received.', 'allpaypayz-woocommerce'));
            return;
        }
        if ($type === 'payment.partially_refunded') {
            $order->add_order_note(__('Allpaypayz: partial refund event received.', 'allpaypayz-woocommerce'));
        }
    }
}
