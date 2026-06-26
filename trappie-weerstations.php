<?php
/**
 * Plugin Name: Trappie Weerstations
 * Description: Informatieve hobby- en advieswebsite over weerstations. Geen webshop.
 * Version: 1.1.0
 * Author: Trappie
 * Text Domain: trappie-weerstations
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TRAPPIE_WEERSTATIONS_VERSION', '1.1.0');
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
        add_action('admin_init', [self::class, 'maybe_upgrade']);
    }

    public static function activate(): void
    {
        Trappie_Weerstations_Post_Types::register();
        self::install_pages();
        update_option('trappie_weerstations_version', TRAPPIE_WEERSTATIONS_VERSION);
        flush_rewrite_rules();
    }

    public static function maybe_upgrade(): void
    {
        if (get_option('trappie_weerstations_version') === TRAPPIE_WEERSTATIONS_VERSION) {
            return;
        }

        self::install_pages();
        update_option('trappie_weerstations_version', TRAPPIE_WEERSTATIONS_VERSION);
        flush_rewrite_rules(false);
    }

    private static function install_pages(): void
    {
        $pages = [
            'weerstations-filteren' => [
                'title' => 'Weerstations filteren',
                'content' => '[weerstations_filter]',
            ],
            'weerstations-vergelijken' => [
                'title' => 'Weerstations vergelijken',
                'content' => '[weerstations_vergelijking]',
            ],
            'weerstation-voorstellen' => [
                'title' => 'Weerstation voorstellen',
                'content' => '[weerstation_voorstellen]',
            ],
        ];

        $page_ids = [];
        foreach ($pages as $slug => $page) {
            $existing = get_page_by_path($slug, OBJECT, 'page');
            if ($existing instanceof WP_Post) {
                $page_ids[$slug] = $existing->ID;
                continue;
            }

            $page_id = wp_insert_post([
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => $page['title'],
                'post_name' => $slug,
                'post_content' => $page['content'],
            ]);

            if (!is_wp_error($page_id) && $page_id) {
                $page_ids[$slug] = (int) $page_id;
            }
        }

        update_option('trappie_weerstations_page_ids', $page_ids);
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}

add_action('plugins_loaded', ['Trappie_Weerstations_Plugin', 'init']);
register_activation_hook(__FILE__, ['Trappie_Weerstations_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['Trappie_Weerstations_Plugin', 'deactivate']);
