<?php
/**
 * Single post template.
 *
 * @package Trappie_Meteo
 */

get_header();
?>
<main id="primary" class="site-main-standard">
    <div class="reading-width">
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class(); ?>>
                <header class="entry-header">
                    <p class="entry-meta"><?php echo esc_html(get_the_date()); ?></p>
                    <h1><?php the_title(); ?></h1>
                </header>
                <?php if (has_post_thumbnail()) : ?>
                    <div class="entry-featured"><?php the_post_thumbnail('large'); ?></div>
                <?php endif; ?>
                <div class="entry-content">
                    <?php the_content(); ?>
                    <?php wp_link_pages(); ?>
                </div>
            </article>
            <?php the_post_navigation(); ?>
            <?php if (comments_open() || get_comments_number()) { comments_template(); } ?>
        <?php endwhile; ?>
    </div>
</main>
<?php get_footer(); ?>
