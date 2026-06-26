<?php
/**
 * Front page.
 *
 * @package Trappie_Meteo
 */

get_header();

$stations = new WP_Query([
    'post_type' => 'weerstation',
    'post_status' => 'publish',
    'posts_per_page' => 3,
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>
<main id="primary">
    <section class="meteo-hero">
        <div class="hero-inner">
            <div class="hero-copy">
                <p class="eyebrow"><?php esc_html_e('Meteorologie thuis', 'trappie-meteo'); ?></p>
                <h1>Trappie <span>Meteo</span></h1>
                <p><?php esc_html_e('Van luchtdruk tot regenmeter: heldere uitleg, eerlijke vergelijkingen en praktische techniek voor jouw weerstation.', 'trappie-meteo'); ?></p>
                <div class="hero-actions">
                    <a class="button button--light" href="<?php echo esc_url(trappie_meteo_station_url()); ?>"><?php esc_html_e('Bekijk weerstations', 'trappie-meteo'); ?></a>
                    <a class="button button--ghost" href="<?php echo esc_url(trappie_meteo_page_url('weerstations-vergelijken')); ?>"><?php esc_html_e('Vergelijk modellen', 'trappie-meteo'); ?></a>
                </div>
            </div>
        </div>
    </section>

    <section class="reading-strip" aria-label="<?php esc_attr_e('Thema-overzicht', 'trappie-meteo'); ?>">
        <div class="reading-strip-inner">
            <div class="reading-item">
                <span class="reading-label"><?php esc_html_e('Meten', 'trappie-meteo'); ?></span>
                <span class="reading-value"><?php esc_html_e('Wind, regen en temperatuur', 'trappie-meteo'); ?></span>
            </div>
            <div class="reading-item">
                <span class="reading-label"><?php esc_html_e('Verbinden', 'trappie-meteo'); ?></span>
                <span class="reading-value"><?php esc_html_e('Wifi, RF en weerplatformen', 'trappie-meteo'); ?></span>
            </div>
            <div class="reading-item">
                <span class="reading-label"><?php esc_html_e('Begrijpen', 'trappie-meteo'); ?></span>
                <span class="reading-value"><?php esc_html_e('Data, plaatsing en onderhoud', 'trappie-meteo'); ?></span>
            </div>
        </div>
    </section>

    <section class="section section--cloud">
        <div class="section-inner">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Stations', 'trappie-meteo'); ?></p>
                    <h2><?php esc_html_e('Vind een weerstation dat bij jouw metingen past', 'trappie-meteo'); ?></h2>
                    <p><?php esc_html_e('Bekijk functies, sensoren, verbindingen en compatibele weerplatformen naast elkaar.', 'trappie-meteo'); ?></p>
                </div>
                <a class="text-link" href="<?php echo esc_url(trappie_meteo_station_url()); ?>"><?php esc_html_e('Alle weerstations', 'trappie-meteo'); ?></a>
            </div>

            <?php if ($stations->have_posts()) : ?>
                <div class="station-grid">
                    <?php while ($stations->have_posts()) : $stations->the_post(); ?>
                        <article <?php post_class('station-card'); ?>>
                            <?php if (has_post_thumbnail()) : ?>
                                <a class="station-card-image" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
                                    <?php the_post_thumbnail('trappie-card'); ?>
                                </a>
                            <?php endif; ?>
                            <div class="station-card-body">
                                <?php $brand = get_post_meta(get_the_ID(), 'merk', true); ?>
                                <?php if ($brand) : ?><p class="station-meta"><?php echo esc_html($brand); ?></p><?php endif; ?>
                                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <?php the_excerpt(); ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <p><?php esc_html_e('De eerste weerstations worden binnenkort toegevoegd.', 'trappie-meteo'); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="section section--tech">
        <div class="section-inner">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('De techniek', 'trappie-meteo'); ?></p>
                    <h2><?php esc_html_e('Goede data begint buiten', 'trappie-meteo'); ?></h2>
                    <p><?php esc_html_e('Sensoropstelling, draadloze overdracht en slim onderhoud bepalen samen hoe betrouwbaar je metingen zijn.', 'trappie-meteo'); ?></p>
                </div>
            </div>
            <div class="topic-grid">
                <article class="topic">
                    <span class="topic-number">01</span>
                    <h3><?php esc_html_e('Sensoren', 'trappie-meteo'); ?></h3>
                    <p><?php esc_html_e('Leer hoe temperatuur, luchtvochtigheid, neerslag en wind daadwerkelijk worden gemeten.', 'trappie-meteo'); ?></p>
                </article>
                <article class="topic">
                    <span class="topic-number">02</span>
                    <h3><?php esc_html_e('Plaatsing', 'trappie-meteo'); ?></h3>
                    <p><?php esc_html_e('Beperk warmte, turbulentie en beschutting met een doordachte plek voor iedere sensor.', 'trappie-meteo'); ?></p>
                </article>
                <article class="topic">
                    <span class="topic-number">03</span>
                    <h3><?php esc_html_e('Datastromen', 'trappie-meteo'); ?></h3>
                    <p><?php esc_html_e('Van RF-signaal en gateway tot Weather Underground en Home Assistant.', 'trappie-meteo'); ?></p>
                </article>
            </div>
        </div>
    </section>

    <?php
    $articles = new WP_Query([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 3,
    ]);
    if ($articles->have_posts()) :
        ?>
        <section class="section">
            <div class="section-inner">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow"><?php esc_html_e('Kennis', 'trappie-meteo'); ?></p>
                        <h2><?php esc_html_e('Nieuwe artikelen', 'trappie-meteo'); ?></h2>
                    </div>
                    <a class="text-link" href="<?php echo esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/')); ?>"><?php esc_html_e('Alle artikelen', 'trappie-meteo'); ?></a>
                </div>
                <div class="post-grid">
                    <?php while ($articles->have_posts()) : $articles->the_post(); ?>
                        <?php get_template_part('template-parts/content', 'card'); ?>
                    <?php endwhile; ?>
                </div>
                <?php wp_reset_postdata(); ?>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
