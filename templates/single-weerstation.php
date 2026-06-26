<?php
/**
 * Single weerstation template.
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="primary" class="site-main trappie-single">
    <?php while (have_posts()) : the_post(); ?>
        <article <?php post_class('trappie-station-detail'); ?>>
            <header class="trappie-detail-header">
                <div>
                    <p class="trappie-kicker">Weerstation</p>
                    <h1><?php the_title(); ?></h1>
                    <?php Trappie_Weerstations_Frontend::render_disclaimer(); ?>
                </div>
                <?php if (has_post_thumbnail()) : ?>
                    <div class="trappie-detail-image"><?php the_post_thumbnail('large'); ?></div>
                <?php endif; ?>
            </header>

            <div class="trappie-detail-content">
                <?php the_content(); ?>
            </div>

            <section class="trappie-specs" aria-label="Specificaties">
                <h2>Specificaties</h2>
                <dl>
                    <?php foreach (Trappie_Weerstations_Post_Types::STATION_FIELDS as $key => $field) : ?>
                        <?php $value = get_post_meta(get_the_ID(), $key, true); ?>
                        <?php if ($value === '') { continue; } ?>
                        <div>
                            <dt><?php echo esc_html($field['label']); ?></dt>
                            <dd>
                                <?php if ($field['type'] === 'url') : ?>
                                    <a href="<?php echo esc_url($value); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($value); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html(Trappie_Weerstations_Frontend::format_value($key, $value)); ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </section>
        </article>
    <?php endwhile; ?>
</main>
<?php
get_footer();
