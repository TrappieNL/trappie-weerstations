<?php
/**
 * Not found template.
 *
 * @package Trappie_Meteo
 */

get_header();
?>
<main id="primary" class="site-main-standard">
    <div class="reading-width">
        <p class="eyebrow">404</p>
        <h1><?php esc_html_e('Deze meting konden we niet vinden', 'trappie-meteo'); ?></h1>
        <p><?php esc_html_e('De pagina bestaat niet meer of het adres is gewijzigd.', 'trappie-meteo'); ?></p>
        <a class="button" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Terug naar home', 'trappie-meteo'); ?></a>
    </div>
</main>
<?php get_footer(); ?>
