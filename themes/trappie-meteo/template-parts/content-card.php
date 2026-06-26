<?php
/**
 * Reusable post card.
 *
 * @package Trappie_Meteo
 */
?>
<article <?php post_class('post-card'); ?>>
    <?php if (has_post_thumbnail()) : ?>
        <a class="post-card-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
            <?php the_post_thumbnail('trappie-card'); ?>
        </a>
    <?php endif; ?>
    <div class="post-card-body">
        <p class="entry-meta"><?php echo esc_html(get_the_date()); ?></p>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <?php the_excerpt(); ?>
    </div>
</article>
