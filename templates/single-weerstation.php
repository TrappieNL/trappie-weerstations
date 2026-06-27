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
        <?php
        $gallery_ids = get_post_meta(get_the_ID(), Trappie_Weerstations_Post_Types::GALLERY_META_KEY, true);
        $gallery_ids = Trappie_Weerstations_Post_Types::sanitize_gallery_ids($gallery_ids);
        $featured_id = get_post_thumbnail_id();
        $hero_image_id = $featured_id ?: ($gallery_ids[0] ?? 0);
        $detail_gallery_ids = array_values(array_filter($gallery_ids, static function (int $image_id) use ($hero_image_id): bool {
            return $image_id !== $hero_image_id && wp_attachment_is_image($image_id);
        }));
        ?>
        <article <?php post_class('trappie-station-detail'); ?>>
            <header class="trappie-detail-header">
                <div>
                    <p class="trappie-kicker">Weerstation</p>
                    <h1><?php the_title(); ?></h1>
                    <?php Trappie_Weerstations_Frontend::render_disclaimer(); ?>
                </div>
                <?php if ($hero_image_id) : ?>
                    <div class="trappie-detail-image"><?php echo wp_get_attachment_image($hero_image_id, 'large'); ?></div>
                <?php endif; ?>
            </header>

            <div class="trappie-detail-content">
                <?php the_content(); ?>
            </div>

            <?php if ($detail_gallery_ids) : ?>
                <section class="trappie-photo-gallery" aria-labelledby="trappie-photo-gallery-title">
                    <h2 id="trappie-photo-gallery-title">Afbeeldingen</h2>
                    <div class="trappie-photo-grid">
                        <?php foreach ($detail_gallery_ids as $image_id) : ?>
                            <figure>
                                <?php echo wp_get_attachment_image($image_id, 'large'); ?>
                                <?php $caption = wp_get_attachment_caption($image_id); ?>
                                <?php if ($caption) : ?><figcaption><?php echo esc_html($caption); ?></figcaption><?php endif; ?>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

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
