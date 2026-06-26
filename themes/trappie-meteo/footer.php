<?php
/**
 * Site footer.
 *
 * @package Trappie_Meteo
 */
?>
<footer class="site-footer">
    <div class="footer-inner">
        <div>
            <p class="footer-title">Trappie <span>Meteo</span></p>
            <p><?php esc_html_e('Onafhankelijke informatie over weerstations, meteorologie en de techniek achter betrouwbare metingen.', 'trappie-meteo'); ?></p>
        </div>
        <div>
            <h2 class="footer-heading"><?php esc_html_e('Ontdekken', 'trappie-meteo'); ?></h2>
            <ul class="footer-menu">
                <li><a href="<?php echo esc_url(trappie_meteo_station_url()); ?>"><?php esc_html_e('Alle weerstations', 'trappie-meteo'); ?></a></li>
                <li><a href="<?php echo esc_url(trappie_meteo_page_url('weerstations-filteren')); ?>"><?php esc_html_e('Weerstations filteren', 'trappie-meteo'); ?></a></li>
                <li><a href="<?php echo esc_url(trappie_meteo_page_url('weerstations-vergelijken')); ?>"><?php esc_html_e('Weerstations vergelijken', 'trappie-meteo'); ?></a></li>
            </ul>
        </div>
        <div>
            <h2 class="footer-heading"><?php esc_html_e('Bijdragen', 'trappie-meteo'); ?></h2>
            <?php
            wp_nav_menu([
                'theme_location' => 'footer',
                'container' => false,
                'menu_class' => 'footer-menu',
                'fallback_cb' => false,
            ]);
            ?>
            <ul class="footer-menu">
                <li><a href="<?php echo esc_url(trappie_meteo_page_url('weerstation-voorstellen')); ?>"><?php esc_html_e('Weerstation voorstellen', 'trappie-meteo'); ?></a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <?php
        printf(
            esc_html__('© %1$s %2$s. Informatief hobbyadvies, geen webshop.', 'trappie-meteo'),
            esc_html(wp_date('Y')),
            esc_html(get_bloginfo('name'))
        );
        ?>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
