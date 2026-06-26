<?php
/**
 * Page template.
 *
 * @package Trappie_Meteo
 */

get_header();
$wide_pages = ['weerstations-filteren', 'weerstations-vergelijken', 'weerstation-voorstellen'];
$content_class = is_page($wide_pages) ? 'content-shell' : 'reading-width';
?>
<main id="primary" class="site-main-standard">
    <div class="<?php echo esc_attr($content_class); ?>">
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class(); ?>>
                <header class="entry-header">
                    <p class="eyebrow"><?php esc_html_e('Trappie Meteo', 'trappie-meteo'); ?></p>
                    <h1><?php the_title(); ?></h1>
                </header>
                <div class="entry-content">
                    <?php the_content(); ?>
                    <?php wp_link_pages(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
</main>
<?php get_footer(); ?>
