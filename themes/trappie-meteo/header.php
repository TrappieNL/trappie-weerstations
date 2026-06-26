<?php
/**
 * Site header.
 *
 * @package Trappie_Meteo
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#primary"><?php esc_html_e('Naar de inhoud', 'trappie-meteo'); ?></a>
<header class="site-header">
    <div class="header-inner">
        <div class="site-branding">
            <?php if (has_custom_logo()) : ?>
                <?php the_custom_logo(); ?>
            <?php endif; ?>
            <a class="site-title" href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                Trappie <span>Meteo</span>
            </a>
        </div>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation">
            <span class="screen-reader-text"><?php esc_html_e('Menu openen', 'trappie-meteo'); ?></span>
            <span class="menu-toggle-lines" aria-hidden="true"></span>
        </button>
        <nav id="primary-navigation" class="primary-navigation" aria-label="<?php esc_attr_e('Hoofdnavigatie', 'trappie-meteo'); ?>">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container' => false,
                'menu_class' => 'primary-menu',
                'fallback_cb' => 'trappie_meteo_primary_menu_fallback',
            ]);
            ?>
        </nav>
    </div>
</header>
