<?php
/**
 * Generic archive template.
 *
 * @package Trappie_Meteo
 */

get_header();
?>
<main id="primary" class="site-main-standard">
    <div class="content-shell">
        <header class="page-header">
            <p class="eyebrow"><?php esc_html_e('Archief', 'trappie-meteo'); ?></p>
            <?php the_archive_title('<h1>', '</h1>'); ?>
            <?php the_archive_description('<div>', '</div>'); ?>
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
