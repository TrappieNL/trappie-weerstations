<?php
/**
 * Comments template.
 *
 * @package Trappie_Meteo
 */

if (post_password_required()) {
    return;
}
?>
<section id="comments" class="comments-area">
    <?php if (have_comments()) : ?>
        <h2><?php esc_html_e('Reacties', 'trappie-meteo'); ?></h2>
        <ol class="comment-list">
            <?php wp_list_comments(['style' => 'ol', 'short_ping' => true]); ?>
        </ol>
        <?php the_comments_navigation(); ?>
    <?php endif; ?>
    <?php comment_form(); ?>
</section>
