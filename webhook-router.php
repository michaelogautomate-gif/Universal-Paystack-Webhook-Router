<?php
/*
Plugin Name: Universal Paystack Webhook Router
Description: Routes a single Paystack webhook out to multiple WooCommerce or platform endpoints dynamically based on transaction metadata tags.
Version: 2.3
Author: Michael Ogolor
Author URI: https://fouchix.com/michael-ogolor/
Text Domain: paystack-webhook-router
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct file execution access paths
}

// 1. Hook Admin Interface Navigation Setup Panels
add_action('admin_menu', 'mogolor_pwr_add_admin_menu');
add_action('admin_init', 'mogolor_pwr_settings_init');

function mogolor_pwr_add_admin_menu() {
    add_options_page(
        esc_html__('Paystack Router Settings', 'paystack-webhook-router'),
        esc_html__('Paystack Router', 'paystack-webhook-router'),
        'manage_options',
        'mogolor_paystack_router',
        'mogolor_pwr_options_page'
    );
}

function mogolor_pwr_settings_init() {
    register_setting('mogolor_pwr_plugin_page', 'mogolor_pwr_settings');

    add_settings_section(
        'mogolor_pwr_plugin_page_section',
        esc_html__('API & Route Configuration', 'paystack-webhook-router'),
        '__return_null',
        'mogolor_pwr_plugin_page'
    );

    add_settings_field(
        'mogolor_pwr_secret_keys',
        esc_html__('Paystack Secret Keys', 'paystack-webhook-router'),
        'mogolor_pwr_keys_render',
        'mogolor_pwr_plugin_page',
        'mogolor_pwr_plugin_page_section'
    );

    add_settings_field(
        'mogolor_pwr_routes',
        esc_html__('Webhook Platform Routing Map', 'paystack-webhook-router'),
        'mogolor_pwr_routes_render',
        'mogolor_pwr_plugin_page',
        'mogolor_pwr_plugin_page_section'
    );
}

function mogolor_pwr_keys_render() {
    $options = get_option('mogolor_pwr_settings');
    ?>
    <p>
        <label><b><?php esc_html_e('Live Secret Key:', 'paystack-webhook-router'); ?></b></label><br>
        <input type='text' name='mogolor_pwr_settings[live_secret_key]' class='regular-text' value='<?php echo esc_attr($options['live_secret_key'] ?? ''); ?>' placeholder="sk_live_...">
    </p>
    <p>
        <label><b><?php esc_html_e('Test Secret Key:', 'paystack-webhook-router'); ?></b></label><br>
        <input type='text' name='mogolor_pwr_settings[test_secret_key]' class='regular-text' value='<?php echo esc_attr($options['test_secret_key'] ?? ''); ?>' placeholder="sk_test_...">
    </p>
    <?php
}

function mogolor_pwr_routes_render() {
    $options = get_option('mogolor_pwr_settings');
    $routes = $options['routes'] ?? [];
    ?>
    <p class="description" style="margin-bottom: 15px;">
        <?php esc_html_e('Map your metadata platform parameter values directly to their destination endpoint URLs.', 'paystack-webhook-router'); ?>
    </p>
    <table id="mogolor-pwr-routes-table" style="width: 100%; max-width: 850px; text-align: left; border-collapse: collapse; margin-bottom: 15px;">
        <thead>
            <tr>
                <th style="padding: 8px; width: 30%; border-bottom: 2px solid #ccd0d4;"><?php esc_html_e('Platform Key (Metadata)', 'paystack-webhook-router'); ?></th>
                <th style="padding: 8px; width: 60%; border-bottom: 2px solid #ccd0d4;"><?php esc_html_e('Destination Webhook Target URL', 'paystack-webhook-router'); ?></th>
                <th style="padding: 8px; width: 10%; border-bottom: 2px solid #ccd0d4;"><?php esc_html_e('Action', 'paystack-webhook-router'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($routes) && is_array($routes)) {
                foreach ($routes as $key => $url) : ?>
                    <tr>
                        <td style="padding: 6px;"><input type="text" name="mogolor_pwr_settings[routes_key][]" value="<?php echo esc_attr($key); ?>" placeholder="e.g. woocommerce" style="width: 95%;" /></td>
                        <td style="padding: 6px;"><input type="text" name="mogolor_pwr_settings[routes_url][]" value="<?php echo esc_url($url); ?>" class="regular-text" placeholder="https://site.com/wc-api/..." style="width: 100%; max-width: none;" /></td>
                        <td style="padding: 6px;"><button type="button" class="button mogolor-pwr-remove-row" style="color: #b32d2d; border-color: #b32d2d;"><?php esc_html_e('Remove', 'paystack-webhook-router'); ?></button></td>
                    </tr>
                <?php endforeach;
            } else { ?>
                <tr>
                    <td style="padding: 6px;"><input type="text" name="mogolor_pwr_settings[routes_key][]" value="" placeholder="e.g. woocommerce" style="width: 95%;" /></td>
                    <td style="padding: 6px;"><input type="text" name="mogolor_pwr_settings[routes_url][]" value="" class="regular-text" placeholder="https://site.com/wc-api/..." style="width: 100%; max-width: none;" /></td>
                    <td style="padding: 6px;"><button type="button" class="button mogolor-pwr-remove-row" style="color: #b32d2d; border-color: #b32d2d;"><?php esc_html_e('Remove', 'paystack-webhook-router'); ?></button></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <button type="button" id="mogolor-pwr-add-row" class="button button-secondary"><?php esc_html_e('Add New Route Row', 'paystack-webhook-router'); ?></button>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tableBody = document.querySelector('#mogolor-pwr-routes-table tbody');
        document.getElementById('mogolor-pwr-add-row').addEventListener('click', function() {
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td style="padding: 6px;"><input type="text" name="mogolor_pwr_settings[routes_key][]" value="" placeholder="e.g. store_two" style="width: 95%;" /></td>
                <td style="padding: 6px;"><input type="text" name="mogolor_pwr_settings[routes_url][]" value="" class="regular-text" placeholder="https://site.com/wc-api/..." style="width: 100%; max-width: none;" /></td>
                <td style="padding: 6px;"><button type="button" class="button mogolor-pwr-remove-row" style="color: #b32d2d; border-color: #b32d2d;"><?php echo esc_js(__('Remove', 'paystack-webhook-router')); ?></button></td>
            `;
            tableBody.appendChild(newRow);
        });

        tableBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('mogolor-pwr-remove-row')) {
                const rows = tableBody.querySelectorAll('tr');
                if (rows.length > 1) {
                    e.target.closest('tr').remove();
                } else {
                    alert('<?php echo esc_js(__('At least one routing configuration entry row is required.', 'paystack-webhook-router')); ?>');
                }
            }
        });
    });
    </script>
    <?php
}

// 2. Strict Input Data Sanitization Engine Interceptor Layer
add_filter('pre_update_option_mogolor_pwr_settings', function($value, $old_value) {
    $sanitized = [];

    // Sanitize standalone text strings safely
    $sanitized['live_secret_key'] = isset($value['live_secret_key']) ? sanitize_text_field($value['live_secret_key']) : '';
    $sanitized['test_secret_key'] = isset($value['test_secret_key']) ? sanitize_text_field($value['test_secret_key']) : '';
    $sanitized['routes'] = [];

    // Enforce array schema boundaries prior to processing mapping configurations
    if (isset($value['routes_key']) && isset($value['routes_url']) && is_array($value['routes_key']) && is_array($value['routes_url'])) {
        foreach ($value['routes_key'] as $index => $key) {
            $sanitized_key = sanitize_key($key);
            $sanitized_url = isset($value['routes_url'][$index]) ? esc_url_raw($value['routes_url'][$index]) : '';

            if (!empty($sanitized_key) && !empty($sanitized_url)) {
                $sanitized['routes'][$sanitized_key] = $sanitized_url;
            }
        }
    }
    return $sanitized;
}, 10, 2);

function mogolor_pwr_options_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Paystack Webhook Router Setup', 'paystack-webhook-router'); ?></h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields('mogolor_pwr_plugin_page');
            do_settings_sections('mogolor_pwr_plugin_page');
            submit_button();
            ?>
        </form>
        <hr style="margin: 20px 0;">
        <h3><?php esc_html_e('Integration Endpoint Information', 'paystack-webhook-router'); ?></h3>
        <p><?php esc_html_e('Copy this URL below and paste it inside your centralized Paystack Settings Dashboard Webhook Target Input configuration area:', 'paystack-webhook-router'); ?></p>
        <code style="background: #fff; padding: 6px 12px; border: 1px solid #ccd0d4; display: inline-block; font-size: 14px; color: #d63638;">
            <?php echo esc_url(rest_url('paystack/v1/router')); ?>
        </code>
    </div>
    <?php
}

// 3. Register Core Functional REST Router Processing Engine Hooks
add_action('rest_api_init', function () {
    register_rest_route('paystack/v1', '/router', [
        'methods'             => 'POST',
        'callback'            => 'mogolor_pwr_webhook_routing_processor',
        // Public endpoint verification occurs cryptographically inside the validation handler callback logic
        'permission_callback' => '__return_true',
    ]);
});

function mogolor_pwr_webhook_routing_processor($request) {
    $options = get_option('mogolor_pwr_settings');

    // Evaluate environments signature key targets dynamically
    $is_debug = defined('WP_DEBUG') && WP_DEBUG === true;
    $secret_key = $is_debug ? ($options['test_secret_key'] ?? '') : ($options['live_secret_key'] ?? '');

    if (empty($secret_key)) {
        return new WP_REST_Response(['error' => esc_html__('Router secret keys are unconfigured.', 'paystack-webhook-router')], 500);
    }

    // Verify cryptographic origin signature using native framework engine abstraction layers
    $signature = $request->get_header('x-paystack-signature');
    $computed_signature = hash_hmac('sha512', $request->get_body(), $secret_key);

    if (empty($signature) || !hash_equals($signature, $computed_signature)) {
        return new WP_REST_Response(['error' => esc_html__('Invalid signature verification check failed', 'paystack-webhook-router')], 403);
    }

    $payload = $request->get_json_params();
    $event = $payload['event'] ?? '';

    // Direct event checks: Immediately answer unrelated triggers with a fast 200 execution escape route
    if ($event !== 'charge.success') {
        return new WP_REST_Response(['status' => esc_html__('Event bypassed - unhandled process action context', 'paystack-webhook-router')], 200);
    }

    $platform = $payload['data']['metadata']['platform'] ?? '';
    $routes = $options['routes'] ?? [];

    if (empty($platform) || !isset($routes[$platform])) {
        return new WP_REST_Response(['error' => sprintf(esc_html__("Unknown route fallback context target: '%s'", 'paystack-webhook-router'), sanitize_text_field($platform))], 400);
    }

    // Pipeline forwarding operation to target child endpoint destination securely
    $url = $routes[$platform];
    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type'         => 'application/json',
            'X-Paystack-Signature' => sanitize_text_field($signature) // Carry forward original verification token header
        ],
        'body'    => json_encode($payload),
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        return new WP_REST_Response(['error' => esc_html__('Proxy dynamic transport forwarding event execution pipeline failed', 'paystack-webhook-router')], 500);
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);
    return new WP_REST_Response($response_body, wp_remote_retrieve_response_code($response));
}
