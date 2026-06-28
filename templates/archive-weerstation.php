<?php
/**
 * Archive template for public weather stations.
 */

if (!defined('ABSPATH')) {
    exit;
}

$page_ids = get_option('trappie_weerstations_page_ids', []);
$compare_url = !empty($page_ids['weerstations-vergelijken']) ? get_permalink((int) $page_ids['weerstations-vergelijken']) : '';
$contact_url = !empty($page_ids['contact']) ? get_permalink((int) $page_ids['contact']) : home_url('/contact/');

get_header();
?>
<main id="primary" class="site-main trappie-archive">
    <header class="trappie-archive-header">
        <p class="trappie-kicker">Trappie Weerstations</p>
        <h1>Weerstations</h1>
        <p>Ontdek betrouwbare meetapparatuur voor tuin, dak en thuisnetwerk. Vergelijk sensoren, verbindingen en weerplatformen naast elkaar.</p>
        <nav class="trappie-archive-actions" aria-label="Weerstation hulpmiddelen">
            <a href="#alle-weerstations">Filter op merk</a>
            <?php if ($compare_url) : ?>
                <a href="<?php echo esc_url($compare_url); ?>">Vergelijken</a>
            <?php endif; ?>
            <a href="<?php echo esc_url($contact_url); ?>">Contact</a>
        </nav>
    </header>

    <?php echo do_shortcode('[weerstations_uitgelicht aantal="4"]'); ?>
    <?php echo do_shortcode('[weerstations_filter]'); ?>
</main>
<?php
get_footer();
