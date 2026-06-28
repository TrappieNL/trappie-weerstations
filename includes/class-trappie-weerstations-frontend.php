<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Trappie_Weerstations_Frontend
{
    public static function init(): void
    {
        add_shortcode('weerstations_overzicht', [self::class, 'overview_shortcode']);
        add_shortcode('weerstations_filter', [self::class, 'filter_shortcode']);
        add_shortcode('weerstations_vergelijking', [self::class, 'comparison_shortcode']);
        add_shortcode('weerstation_voorstellen', [self::class, 'suggestion_shortcode']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('init', [self::class, 'handle_suggestion_form']);
        add_filter('template_include', [self::class, 'plugin_template']);
        add_filter('post_thumbnail_id', [self::class, 'gallery_thumbnail_fallback'], 10, 2);
    }

    public static function enqueue_assets(): void
    {
        wp_enqueue_style('trappie-weerstations', TRAPPIE_WEERSTATIONS_URL . 'assets/frontend.css', [], TRAPPIE_WEERSTATIONS_VERSION);
    }

    public static function gallery_thumbnail_fallback($thumbnail_id, $post): int
    {
        if ($thumbnail_id || !($post instanceof WP_Post) || $post->post_type !== Trappie_Weerstations_Post_Types::STATION_POST_TYPE) {
            return absint($thumbnail_id);
        }

        $gallery_ids = get_post_meta($post->ID, Trappie_Weerstations_Post_Types::GALLERY_META_KEY, true);
        $gallery_ids = Trappie_Weerstations_Post_Types::sanitize_gallery_ids($gallery_ids);

        return $gallery_ids[0] ?? 0;
    }

    public static function plugin_template(string $template): string
    {
        if (is_singular(Trappie_Weerstations_Post_Types::STATION_POST_TYPE)) {
            $plugin_template = TRAPPIE_WEERSTATIONS_DIR . 'templates/single-weerstation.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        if (is_post_type_archive(Trappie_Weerstations_Post_Types::STATION_POST_TYPE)) {
            $plugin_template = TRAPPIE_WEERSTATIONS_DIR . 'templates/archive-weerstation.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    public static function overview_shortcode(array $atts = []): string
    {
        $atts = shortcode_atts(['aantal' => 12], $atts);

        $query = new WP_Query([
            'post_type' => Trappie_Weerstations_Post_Types::STATION_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => max(1, absint($atts['aantal'])),
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        ob_start();
        self::render_disclaimer();
        self::render_station_cards($query);
        wp_reset_postdata();

        return (string) ob_get_clean();
    }

    public static function filter_shortcode(): string
    {
        $tax_query = [];
        foreach (array_keys(Trappie_Weerstations_Post_Types::TAXONOMIES) as $taxonomy) {
            $term = isset($_GET[$taxonomy]) ? sanitize_title(wp_unslash($_GET[$taxonomy])) : '';
            if ($term !== '') {
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $term,
                ];
            }
        }

        $query_args = [
            'post_type' => Trappie_Weerstations_Post_Types::STATION_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 24,
            'orderby' => 'title',
            'order' => 'ASC',
        ];
        if ($tax_query) {
            $query_args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($query_args);

        ob_start();
        self::render_disclaimer();
        echo '<form class="trappie-filter" method="get">';
        foreach (Trappie_Weerstations_Post_Types::TAXONOMIES as $taxonomy => $label) {
            $current = isset($_GET[$taxonomy]) ? sanitize_title(wp_unslash($_GET[$taxonomy])) : '';
            $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
            printf('<label><span>%s</span><select name="%s"><option value="">Alles</option>', esc_html($label), esc_attr($taxonomy));
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    printf('<option value="%s" %s>%s</option>', esc_attr($term->slug), selected($current, $term->slug, false), esc_html($term->name));
                }
            }
            echo '</select></label>';
        }
        echo '<button type="submit">Filter</button></form>';
        self::render_station_cards($query);
        wp_reset_postdata();

        return (string) ob_get_clean();
    }

    public static function comparison_shortcode(array $atts = []): string
    {
        $atts = shortcode_atts(['ids' => ''], $atts);
        $ids = array_filter(array_map('absint', explode(',', (string) $atts['ids'])));
        if (!$ids && isset($_GET['station_ids'])) {
            $ids = array_filter(array_map('absint', explode(',', sanitize_text_field(wp_unslash($_GET['station_ids'])))));
        }

        $query_args = [
            'post_type' => Trappie_Weerstations_Post_Types::STATION_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $ids ? count($ids) : 4,
        ];
        if ($ids) {
            $query_args['post__in'] = $ids;
            $query_args['orderby'] = 'post__in';
        }

        $query = new WP_Query($query_args);

        ob_start();
        self::render_disclaimer();
        if (!$query->have_posts()) {
            echo '<p>Geen weerstations om te vergelijken.</p>';
            return (string) ob_get_clean();
        }

        echo '<div class="trappie-table-wrap"><table class="trappie-comparison"><thead><tr><th>Eigenschap</th>';
        $posts = $query->posts;
        foreach ($posts as $post) {
            printf('<th><a href="%s">%s</a></th>', esc_url(get_permalink($post)), esc_html(get_the_title($post)));
        }
        echo '</tr></thead><tbody>';

        $fields = ['merk', 'model', 'indicatieve_prijsklasse', 'meetwaarden', 'refresh_rate', 'rf_frequentie', 'wifi', 'ethernet', 'bluetooth', 'zonnepaneel', 'compatible_home_assistant'];
        foreach ($fields as $key) {
            $label = Trappie_Weerstations_Post_Types::STATION_FIELDS[$key]['label'] ?? $key;
            printf('<tr><th>%s</th>', esc_html($label));
            foreach ($posts as $post) {
                $value = get_post_meta($post->ID, $key, true);
                printf('<td>%s</td>', esc_html(self::format_value($key, $value)));
            }
            echo '</tr>';
        }
        echo '</tbody></table></div>';
        wp_reset_postdata();

        return (string) ob_get_clean();
    }

    public static function suggestion_shortcode(): string
    {
        ob_start();
        self::render_disclaimer();
        if (isset($_GET['trappie_suggested']) && $_GET['trappie_suggested'] === '1') {
            echo '<p class="trappie-notice">Bedankt. Je voorstel is opgeslagen als kandidaat en wordt handmatig gecontroleerd.</p>';
        }
        ?>
        <form class="trappie-suggestion" method="post">
            <?php wp_nonce_field('trappie_suggest_station', 'trappie_suggest_nonce'); ?>
            <input type="hidden" name="trappie_suggest_action" value="1">
            <label><span>Merk</span><input type="text" name="merk" required></label>
            <label><span>Model</span><input type="text" name="model" required></label>
            <label><span>Fabrikant URL of bron</span><input type="url" name="bron_url"></label>
            <label><span>Waarom past dit weerstation?</span><textarea name="omschrijving" rows="5" required></textarea></label>
            <label><span>Opmerkingen</span><textarea name="opmerkingen" rows="4"></textarea></label>
            <button type="submit">Weerstation voorstellen</button>
        </form>
        <?php
        return (string) ob_get_clean();
    }

    public static function handle_suggestion_form(): void
    {
        if (!isset($_POST['trappie_suggest_action'])) {
            return;
        }

        if (!isset($_POST['trappie_suggest_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['trappie_suggest_nonce'])), 'trappie_suggest_station')) {
            wp_die(esc_html__('Ongeldig formulier.', 'trappie-weerstations'));
        }

        $merk = isset($_POST['merk']) ? sanitize_text_field(wp_unslash($_POST['merk'])) : '';
        $model = isset($_POST['model']) ? sanitize_text_field(wp_unslash($_POST['model'])) : '';
        $omschrijving = isset($_POST['omschrijving']) ? sanitize_textarea_field(wp_unslash($_POST['omschrijving'])) : '';

        if ($merk === '' || $model === '' || $omschrijving === '') {
            wp_die(esc_html__('Vul merk, model en omschrijving in.', 'trappie-weerstations'));
        }

        $candidate_id = wp_insert_post([
            'post_type' => Trappie_Weerstations_Post_Types::CANDIDATE_POST_TYPE,
            'post_status' => 'draft',
            'post_title' => trim($merk . ' ' . $model),
            'post_content' => $omschrijving,
        ], true);

        if (is_wp_error($candidate_id)) {
            wp_die(esc_html($candidate_id->get_error_message()));
        }

        update_post_meta((int) $candidate_id, 'merk', $merk);
        update_post_meta((int) $candidate_id, 'model', $model);
        update_post_meta((int) $candidate_id, 'omschrijving', $omschrijving);
        update_post_meta((int) $candidate_id, 'bron_url', isset($_POST['bron_url']) ? esc_url_raw(wp_unslash($_POST['bron_url'])) : '');
        update_post_meta((int) $candidate_id, 'opmerkingen', isset($_POST['opmerkingen']) ? sanitize_textarea_field(wp_unslash($_POST['opmerkingen'])) : '');
        update_post_meta((int) $candidate_id, 'candidate_status', 'nieuw');

        $redirect = remove_query_arg(['trappie_suggested'], wp_get_referer() ?: home_url('/'));
        wp_safe_redirect(add_query_arg('trappie_suggested', '1', $redirect));
        exit;
    }

    public static function render_disclaimer(): void
    {
        echo '<p class="trappie-disclaimer">Disclaimer: deze website biedt informatief hobbyadvies over weerstations. Dit is geen webshop en er is geen winkelwagen, checkout of voorraadbeheer.</p>';
    }

    private static function render_station_cards(WP_Query $query): void
    {
        if (!$query->have_posts()) {
            echo '<p>Geen weerstations gevonden.</p>';
            return;
        }

        echo '<div class="trappie-station-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<article class="trappie-station-card">';
            if (has_post_thumbnail()) {
                printf('<a href="%s" class="trappie-station-image">%s</a>', esc_url(get_permalink()), get_the_post_thumbnail(get_the_ID(), 'medium'));
            }
            printf('<h3><a href="%s">%s</a></h3>', esc_url(get_permalink()), esc_html(get_the_title()));
            $price = get_post_meta(get_the_ID(), 'indicatieve_prijsklasse', true);
            if ($price) {
                printf('<p class="trappie-muted">%s</p>', esc_html($price));
            }
            echo '<p>' . esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(), 36, '...')) . '</p>';
            printf('<p><a class="trappie-detail-link" href="%s">Bekijk alle informatie</a></p>', esc_url(get_permalink()));
            echo '</article>';
        }
        echo '</div>';
    }

    public static function format_value(string $key, $value): string
    {
        $type = Trappie_Weerstations_Post_Types::STATION_FIELDS[$key]['type'] ?? 'text';
        if ($type === 'checkbox') {
            return $value === '1' ? 'Ja' : 'Nee';
        }

        return $value !== '' ? (string) $value : '-';
    }
}
