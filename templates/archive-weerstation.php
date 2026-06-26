<?php
/**
 * Archive template for public weather stations.
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_ids = get_option('trappie_weerstations_page_ids', []);
$filter_url = !empty($page_ids['weerstations-filteren']) ? get_permalink((int) $page_ids['weerstations-filteren']) : '';
$compare_url = !empty($page_ids['weerstations-vergelijken']) ? get_permalink((int) $page_ids['weerstations-vergelijken']) : '';
$suggest_url = !empty($page_ids['weerstation-voorstellen']) ? get_permalink((int) $page_ids['weerstation-voorstellen']) : '';

get_header();
?>
<main id="primary" class="site-main trappie-archive">
    <header class="trappie-archive-header">
        <p class="trappie-kicker">Trappie Weerstations</p>
        <h1>Weerstations</h1>
        <p>Ontdek en vergelijk weerstations voor thuis en hobbygebruik.</p>
        <nav class="trappie-archive-actions" aria-label="Weerstation hulpmiddelen">
            <?php if ($filter_url) : ?>
                <a href="<?php echo esc_url($filter_url); ?>">Filteren</a>
            <?php endif; ?>
            <?php if ($compare_url) : ?>
                <a href="<?php echo esc_url($compare_url); ?>">Vergelijken</a>
            <?php endif; ?>
            <?php if ($suggest_url) : ?>
                <a href="<?php echo esc_url($suggest_url); ?>">Weerstation voorstellen</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php echo do_shortcode('[weerstations_overzicht aantal="24"]'); ?>
</main>
<?php
get_footer();
