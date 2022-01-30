<?php
/**
 * Plugin Name:             Offerwhere for WooCommerce
 * Plugin URI:              https://www.offerwhere.com/grow-business/loyalty-programs
 * Description:             Retain more customers. Run an effective loyalty program on your website, in your store, or from your home in minutes.
 * Version:                 1.5.0
 * Requires at least:       3.1
 * Tested up to:            5.9
 * Requires PHP:            7.0
 * WC requires at least:    3.5
 * WC tested up to:         6.0
 * Author:                  Offerwhere
 * Author URI:              https://www.offerwhere.com
 * License:                 GPLv2 or later
 * License URI:             http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

const OFFERWHERE_WORDPRESS_MINIMUM_VERSION = '3.1';
const OFFERWHERE_VERSION = '1.5.0';
const OFFERWHERE_WOOCOMMERCE_MINIMUM_VERSION = '3.5';

define('OFFERWHERE_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once(OFFERWHERE_PLUGIN_DIR . 'class.offerwhere_validator.php');
require_once(OFFERWHERE_PLUGIN_DIR . 'class.offerwhere_settings.php');
require_once(OFFERWHERE_PLUGIN_DIR . 'class.offerwhere_http_status.php');
require_once(OFFERWHERE_PLUGIN_DIR . 'class.offerwhere_message.php');
require_once(OFFERWHERE_PLUGIN_DIR . 'class.offerwhere_woocommerce.php');
require_once(OFFERWHERE_PLUGIN_DIR . 'class.offerwhere_api.php');
require_once(OFFERWHERE_PLUGIN_DIR . 'class.offerwhere_database.php');
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

add_action('plugins_loaded', 'offerwhere_plugins_loaded');
add_action('init', array(Offerwhere_Settings::OFFERWHERE_SETTINGS_CLASS, 'init'));
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'offerwhere_add_plugin_page_settings_link');
register_activation_hook(__FILE__, 'offerwhere_run_activation_routine');
register_uninstall_hook(__FILE__, 'offerwhere_run_uninstall_routine');

function offerwhere_plugins_loaded()
{
    if (Offerwhere_Settings::offerwhere_is_setting_missing()) {
        add_action('admin_notices', 'offerwhere_missing_api_key_notice');
        return;
    }
    if (version_compare($GLOBALS['wp_version'], OFFERWHERE_WORDPRESS_MINIMUM_VERSION, '<')) {
        add_action('admin_notices', 'offerwhere_unsupported_wordpress_version_notice');
        return;
    }
    if (!class_exists('woocommerce')) {
        add_action('admin_notices', 'offerwhere_woocommerce_not_active_notice');
        return;
    }
    if (version_compare(WC_VERSION, OFFERWHERE_WOOCOMMERCE_MINIMUM_VERSION, '<')) {
        add_action('admin_notices', 'offerwhere_unsupported_woocommerce_version_notice');
        return;
    }
    add_action('init', array(Offerwhere_WooCommerce::OFFERWHERE_WOOCOMMERCE_CLASS, 'init'));
}

function offerwhere_missing_api_key_notice()
{
    ?>
    <div class="error"><p>Offerwhere is not configured. Enter the values required.</p></div>
    <?php
}

function offerwhere_unsupported_wordpress_version_notice()
{
    esc_html(printf(
        '<div class="error"><p>Offerwhere %s requires WordPress %s or higher. Upgrade WordPress.</p></div>',
        OFFERWHERE_VERSION,
        OFFERWHERE_WORDPRESS_MINIMUM_VERSION
    ));
}

function offerwhere_woocommerce_not_active_notice()
{
    ?>
    <div class="error"><p>Offerwhere requires WooCommerce to be installed and active. Install and activate
            WooCommerce.</p></div>
    <?php
}

function offerwhere_unsupported_woocommerce_version_notice()
{
    esc_html(printf(
        '<div class="error"><p>Offerwhere %s requires WooCommerce %s or higher. Upgrade WooCommerce.</p></div>',
        OFFERWHERE_VERSION,
        OFFERWHERE_WOOCOMMERCE_MINIMUM_VERSION
    ));
}

function offerwhere_add_plugin_page_settings_link($links)
{
    $links[] = '<a href="' .
        admin_url('options-general.php?page=' . Offerwhere_Settings::OFFERWHERE_SETTINGS_PAGE_SLUG) .
        '">' . __('Settings') . '</a>';
    return $links;
}

function offerwhere_run_activation_routine()
{
    Offerwhere_Database::offerwhere_create_user_table_if_not_exists();
}

function offerwhere_run_uninstall_routine()
{
    Offerwhere_Database::offerwhere_drop_user_table();
    Offerwhere_Settings::offerwhere_delete_settings();
}