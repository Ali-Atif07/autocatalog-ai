<?php
/**
 * Plugin Name: AutoCatalog AI
 * Plugin URI:  https://github.com/Ali-Atif07/autocatalog-ai
 * Description: AI-powered product descriptions, tags and SEO meta for WooCommerce.
 * Version:     1.0.0
 * Author:      Mohammed Ali Atif
 * Author URI:  https://github.com/Ali-Atif07
 * License:     GPLv2 or later          
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html  
 * Text Domain: autocatalog-ai          
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 */
if (!defined('ABSPATH')) exit;

define('AIPRODUCT_VERSION', '1.0.0');
define('AIPRODUCT_PATH', plugin_dir_path(__FILE__));
define('AIPRODUCT_URL', plugin_dir_url(__FILE__));

require_once AIPRODUCT_PATH . 'includes/admin-settings.php';
require_once AIPRODUCT_PATH . 'includes/api-handler.php';
require_once AIPRODUCT_PATH . 'includes/woo-integration.php';


// Show error if WooCommerce not installed
function aiproduct_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>AI Product Assistant requires WooCommerce to be installed and active.</p></div>';
        });
        deactivate_plugins(plugin_basename(__FILE__));
    }
}
add_action('plugins_loaded', 'aiproduct_check_woocommerce');