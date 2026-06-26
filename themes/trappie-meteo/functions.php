<?php
/**
 * Trappie Meteo theme functions.
 *
 * @package Trappie_Meteo
 */

if (!defined('ABSPATH')) {
    exit;
}

function trappie_meteo_setup(): void
{
    load_theme_textdomain('trappie-meteo', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'height' => 96,
        'width' => 320,
        'flex-height' => true,
        'flex-width' => true,
    ]);
    add_theme_support('automatic-feed-links');
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('editor-styles');
    add_editor_style('style.css');

    register_nav_menus([
        'primary' => __('Hoofdnavigatie', 'trappie-meteo'),
        'footer' => __('Footernavigatie', 'trappie-meteo'),
    ]);

    add_image_size('trappie-card', 720, 450, true);
}
add_action('after_setup_theme', 'trappie_meteo_setup');

function trappie_meteo_assets(): void
{
    $theme = wp_get_theme();
    wp_enqueue_style('trappie-meteo', get_stylesheet_uri(), [], $theme->get('Version'));
    wp_enqueue_script('trappie-meteo-navigation', get_template_directory_uri() . '/assets/js/navigation.js', [], $theme->get('Version'), true);
}
add_action('wp_enqueue_scripts', 'trappie_meteo_assets');

function trappie_meteo_primary_menu_fallback(): void
{
    $items = [
        home_url('/') => __('Home', 'trappie-meteo'),
        get_post_type_archive_link('weerstation') ?: home_url('/weerstations/') => __('Weerstations', 'trappie-meteo'),
        home_url('/weerstations-filteren/') => __('Filteren', 'trappie-meteo'),
        home_url('/weerstation-voorstellen/') => __('Voorstellen', 'trappie-meteo'),
    ];

    echo '<ul class="primary-menu">';
    foreach ($items as $url => $label) {
        printf('<li><a href="%s">%s</a></li>', esc_url($url), esc_html($label));
    }
    echo '</ul>';
}

function trappie_meteo_station_url(): string
{
    $url = get_post_type_archive_link('weerstation');
    return $url ?: home_url('/weerstations/');
}

function trappie_meteo_page_url(string $slug): string
{
    $page = get_page_by_path($slug);
    return $page instanceof WP_Post ? get_permalink($page) : home_url('/' . trim($slug, '/') . '/');
}

function trappie_meteo_excerpt_length(int $length): int
{
    return is_admin() ? $length : 24;
}
add_filter('excerpt_length', 'trappie_meteo_excerpt_length');

function trappie_meteo_excerpt_more(): string
{
    return '&hellip;';
}
add_filter('excerpt_more', 'trappie_meteo_excerpt_more');
