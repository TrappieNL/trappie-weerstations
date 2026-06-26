<?php
/**
 * Plugin Name: Trappie Weerstations
 * Description: Informatieve hobby- en advieswebsite over weerstations. Geen webshop.
 * Version: 1.0.0
 * Author: Trappie
 * Text Domain: trappie-weerstations
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TRAPPIE_WEERSTATIONS_VERSION', '1.0.0');
define('TRAPPIE_WEERSTATIONS_FILE', __FILE__);
define('TRAPPIE_WEERSTATIONS_DIR', plugin_dir_path(__FILE__));
define('TRAPPIE_WEERSTATIONS_URL', plugin_dir_url(__FILE__));

require_once TRAPPIE_WEERSTATIONS_DIR . 'includes/class-trappie-weerstations-post-types.php';
require_once TRAPPIE_WEERSTATIONS_DIR . 'includes/class-trappie-weerstations-admin.php';
require_once TRAPPIE_WEERSTATIONS_DIR . 'includes/class-trappie-weerstations-rest.php';
require_once TRAPPIE_WEERSTATIONS_DIR . 'includes/class-trappie-weerstations-frontend.php';

final class Trappie_Weerstations_Plugin
{
    public static function init(): void
    {
        Trappie_Weerstations_Post_Types::init();
        Trappie_Weerstations_Admin::init();
        Trappie_Weerstations_REST::init();
        Trappie_Weerstations_Frontend::init();
    }

    public static function activate(): void
    {
        Trappie_Weerstations_Post_Types::register();
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}

add_action('plugins_loaded', ['Trappie_Weerstations_Plugin', 'init']);
register_activation_hook(__FILE__, ['Trappie_Weerstations_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['Trappie_Weerstations_Plugin', 'deactivate']);
