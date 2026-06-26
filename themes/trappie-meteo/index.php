<?php
/**
 * Main fallback template.
 *
 * @package Trappie_Meteo
 */

get_header();
?>
<main id="primary" class="site-main-standard">
    <div class="content-shell">
        <header class="page-header">
            <p class="eyebrow"><?php esc_html_e('Trappie Meteo', 'trappie-meteo'); ?></p>
            <h1><?php echo is_home() ? esc_html__('Artikelen', 'trappie-meteo') : esc_html(get_bloginfo('name')); ?></h1>
        </header>
        <?php if (have_posts()) : ?>
            <div class="post-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('template-parts/content', 'card'); ?>
                <?php endwhile; ?>
            </div>
            <?php the_posts_pagination(); ?>
        <?php else : ?>
            <?php get_template_part('template-parts/content', 'none'); ?>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
