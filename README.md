Universal Paystack Webhook Router
A highly secure, robust, and lightweight WordPress plugin that enables a single centralized Paystack merchant account to dynamically route incoming webhook payment events (specifically charge.success) out to multiple independent target websites simultaneously. Routing decisions are made automatically using transaction metadata tags.

This architecture completely solves the limitation of the Paystack dashboard allowing only a single Webhook URL per account, enabling merchants to seamlessly scale multi-site e-commerce structures, learning management systems, and SaaS instances off one payment gateway profile.

Key Features
Dynamic Request Proxying: Evaluates incoming Paystack JSON transaction payloads and forwards them verbatim to the exact child site responsible for the order.

Cryptographic Signature Verification: Enforces security by verifying Paystack's cryptographic token header (X-Paystack-Signature) against your configured Live or Test Secret Keys via timing-attack-safe hash_equals comparisons before executing proxy paths.

WordPress Core Security Adherence: Adheres strictly to security standards including strict data input type casting, explicit array boundary sanitization (sanitize_key, esc_url_raw), and database escape schemas.

Namespace Collision Defended: Completely isolated using an explicitly unique prefix configuration (mogolor_pwr_) ensuring total interoperability without function or option overriding conflicts.

Native WP-REST API Architecture: Utilizes the robust register_rest_route layer rather than blocking admin hooks, resulting in sub-millisecond execution handshakes.

Architecture & Data Flow
Transaction Occurs: A customer pays on a child platform (e.g., your secondary LMS site or main WooCommerce site).

Metadata Tagged: A local snippet injects a distinct identification tag (e.g., platform => 'tutor_lms') into the Paystack checkout payload.

Central Hub Interception: Paystack sends the webhook payload to the primary website running this plugin.

Validation and Verification: The Central Hub checks the signature. If it is verified, it matches the incoming platform key against your user-defined routing database map.

Secure Forwarding: The hub duplicates the payload header signatures and seamlessly pipes the event down via wp_remote_post to the designated child store's webhook intake endpoint.

Installation Guide
Step 1: Deploy the Central Router Hub Plugin
You can install this plugin on your primary directory hub site using one of the following methods:

Option A: Zip Archive Installation (Recommended)
Locate the standalone script file named webhook-router.php.

Compress/Zip it individually into a standard .zip folder named webhook-router.zip.

Log into your primary WordPress website's administration dashboard.

Navigate to Plugins > Add New > Upload Plugin.

Choose your freshly generated webhook-router.zip archive and click Install Now.

Click Activate Plugin.

Option B: Manual Directory Upload
Connect to your primary hub site's hosting server using an SFTP client or your hosting provider's File Manager.

Navigate to the /wp-content/plugins/ directory path.

Create a new folder named paystack-webhook-router.

Upload your webhook-router.php script file directly into that folder.

Access your WordPress Admin Area, navigate to Plugins, locate Universal Paystack Webhook Router, and click Activate.

⚙️ Configuration Map
1. Hub Settings Dashboard Setup
Once activated, navigate to Settings > Paystack Router inside your primary hub domain's dashboard panel.

Provide your merchant credentials inside the Live Secret Key and Test Secret Key fields.

Scroll to the bottom and copy the customized integration link generated within the Integration Endpoint Information box. It will look like this:

https://your-main-site.com/wp-json/paystack/v1/router
Click Save Changes.

2. Paystack Gateway Webhook Setup
Log into your centralized Paystack Merchant Dashboard.

Navigate to Settings > API Keys & Webhooks.

Locate the Webhook URL entry field and input your copied centralized routing link.

Click Save Changes. Your profile will now route all transaction handshakes securely through your custom endpoint.

💻 Connection Snippets Deployment
To route transaction traffic accurately, each site must pass a unique identifier tag back through the Paystack checkout engine. Instead of modifying core plugin templates, add these code settings blocks into an injection tool like WPCode or Code Snippets on the target site instances.

1. Primary WooCommerce Store Snippet Configuration
Target Installation Location: Main Site Dashboard

Execution Boundary: PHP Snippet (Run Everywhere)

PHP
/**
 * Inject Unique Platform Identifiers into Main WooCommerce Checkout Arguments
 */
add_filter('woocommerce_payment_gateway_paystack_transaction_args', 'mogolor_pwr_main_site_metadata');

function mogolor_pwr_main_site_metadata($args) {
    if (!isset($args['metadata']) || !is_array($args['metadata'])) {
        $args['metadata'] = array();
    }
    $args['metadata']['platform'] = 'woocommerce';
    return $args;
}
After activation, navigate back to your central hub's router platform map map table and link the key woocommerce to your primary store's API endpoint (e.g., https://your-main-site.com/wc-api/Tbz_WC_Paystack_Webhook/).

2. Secondary Platform Snippet Configuration (e.g., Tutor LMS / Child Site)
Target Installation Location: Secondary Site Dashboard

Execution Boundary: PHP Snippet (Run Everywhere)

PHP
/**
 * Inject Unique Platform Identifiers into Secondary Site Paystack Request Body for Routing
 */
add_filter('wc_gateway_paystack_request_body', 'mogolor_pwr_secondary_site_metadata', 10, 2);

function mogolor_pwr_secondary_site_metadata($request_body, $order_id) {
    if (!isset($request_body['metadata']) || !is_array($request_body['metadata'])) {
        $request_body['metadata'] = array();
    }

    $request_body['metadata']['platform'] = 'tutor_lms';
    $request_body['metadata']['origin_site'] = esc_url(home_url());
    $request_body['metadata']['order_id'] = $order_id;

    return $request_body;
}
After activation, navigate to your central hub's router map table, add a new row with the key tutor_lms, and map it to your target platform integration API endpoint.

🛡️ Security Considerations
Verification Constraints: If the incoming signature payload fails to match the locally calculated HMAC-SHA512 checksum string, the processor cuts execution immediately and returns a strict 403 Forbidden REST response code.

Debugging Context Handling: The router systematically detects your site configuration's state. When WP_DEBUG is active, it enforces test keys automatically; during standard production contexts, it switches strictly to live validation environments.

Timing-Attack Resilience: Employs the internal WordPress framework string handler hash_equals() to completely neutralize timing attack vector probing vulnerabilities.

📄 License & Authorship
Author: Michael Ogolor

Author URI: fouchix.com/michael-ogolor

License: GPL2 (General Public License version 2 or later)

This application framework is open-source software. Feel free to fork, expand, and optimize implementations as needed!
