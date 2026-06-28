<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Trappie_Weerstations_Frontend
{
    public static function init(): void
    {
        add_shortcode('weerstations_overzicht', [self::class, 'overview_shortcode']);
        add_shortcode('weerstations_uitgelicht', [self::class, 'featured_shortcode']);
        add_shortcode('weerstations_filter', [self::class, 'filter_shortcode']);
        add_shortcode('weerstations_vergelijking', [self::class, 'comparison_shortcode']);
        add_shortcode('weerstation_voorstellen', [self::class, 'suggestion_shortcode']);
        add_shortcode('trappie_contactformulier', [self::class, 'contact_shortcode']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('init', [self::class, 'handle_contact_form']);
        add_filter('template_include', [self::class, 'plugin_template']);
        add_filter('post_thumbnail_id', [self::class, 'gallery_thumbnail_fallback'], 10, 2);
    }

    public static function enqueue_assets(): void
    {
        wp_enqueue_style('trappie-weerstations', TRAPPIE_WEERSTATIONS_URL . 'assets/frontend.css', [], TRAPPIE_WEERSTATIONS_VERSION);
        wp_enqueue_script('trappie-weerstations', TRAPPIE_WEERSTATIONS_URL . 'assets/frontend.js', [], TRAPPIE_WEERSTATIONS_VERSION, true);
        wp_localize_script('trappie-weerstations', 'trappieFrontend', [
            'maxCompare' => 4,
            'maxCompareMessage' => 'Je kunt maximaal vier weerstations tegelijk vergelijken.',
        ]);
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

    public static function featured_shortcode(array $atts = []): string
    {
        $atts = shortcode_atts(['aantal' => 4], $atts);
        $amount = max(1, min(8, absint($atts['aantal'])));
        $query_args = [
            'post_type' => Trappie_Weerstations_Post_Types::STATION_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $amount,
            'meta_key' => Trappie_Weerstations_Post_Types::FEATURED_META_KEY,
            'meta_value' => '1',
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        $using_featured = true;
        $query = new WP_Query($query_args);

        if (!$query->have_posts()) {
            $using_featured = false;
            unset($query_args['meta_key'], $query_args['meta_value']);
            $query = new WP_Query($query_args);
        }

        if (!$query->have_posts()) {
            return '';
        }

        ob_start();
        echo '<section class="trappie-featured-stations">';
        printf(
            '<div class="trappie-section-heading"><div><p class="trappie-kicker">Onze selectie</p><h2>%s</h2></div>',
            esc_html($using_featured ? 'Uitgelichte weerstations' : 'Recente weerstations')
        );
        printf('<a href="%s">Bekijk alle weerstations</a></div>', esc_url(get_post_type_archive_link(Trappie_Weerstations_Post_Types::STATION_POST_TYPE)));
        self::render_station_cards($query, false);
        echo '</section>';
        wp_reset_postdata();

        return (string) ob_get_clean();
    }

    public static function filter_shortcode(): string
    {
        $current_brand = isset($_GET['merk']) ? sanitize_text_field(wp_unslash($_GET['merk'])) : '';
        $tax_query = [];
        foreach (array_keys(Trappie_Weerstations_Post_Types::TAXONOMIES) as $taxonomy) {
            if ($taxonomy === 'merk') {
                continue;
            }
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
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ];
        if ($current_brand !== '') {
            $query_args['meta_query'] = [[
                'key' => 'merk',
                'value' => $current_brand,
                'compare' => '=',
            ]];
        }
        if ($tax_query) {
            $query_args['tax_query'] = $tax_query;
        }

        $query = new WP_Query($query_args);

        ob_start();
        self::render_disclaimer();
        echo '<section id="alle-weerstations" class="trappie-all-stations">';
        printf('<div class="trappie-results-heading"><div><p class="trappie-kicker">Stationsgids</p><h2>Alle weerstations</h2></div><p>%d gevonden</p></div>', absint($query->found_posts));
        echo '<form class="trappie-filter" method="get">';
        echo '<label><span>Merk</span><select name="merk"><option value="">Alle merken</option>';
        foreach (self::station_brands() as $brand) {
            printf('<option value="%s" %s>%s</option>', esc_attr($brand), selected($current_brand, $brand, false), esc_html($brand));
        }
        echo '</select></label>';
        foreach (Trappie_Weerstations_Post_Types::TAXONOMIES as $taxonomy => $label) {
            if ($taxonomy === 'merk') {
                continue;
            }
            $current = isset($_GET[$taxonomy]) ? sanitize_title(wp_unslash($_GET[$taxonomy])) : '';
            $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
            if (is_wp_error($terms) || !$terms) {
                continue;
            }
            printf('<label><span>%s</span><select name="%s"><option value="">Alle %s</option>', esc_html($label), esc_attr($taxonomy), esc_html(strtolower($label)));
            foreach ($terms as $term) {
                printf('<option value="%s" %s>%s</option>', esc_attr($term->slug), selected($current, $term->slug, false), esc_html($term->name));
            }
            echo '</select></label>';
        }
        echo '<div class="trappie-filter-actions"><button type="submit">Toon resultaten</button>';
        if ($current_brand !== '' || $tax_query) {
            echo '<a href="' . esc_url(remove_query_arg(array_keys(Trappie_Weerstations_Post_Types::TAXONOMIES))) . '">Wis filters</a>';
        }
        echo '</div></form>';
        self::render_station_cards($query);
        echo '</section>';
        wp_reset_postdata();

        return (string) ob_get_clean();
    }

    public static function comparison_shortcode(array $atts = []): string
    {
        $atts = shortcode_atts(['ids' => ''], $atts);
        $ids = array_values(array_unique(array_filter(array_map('absint', explode(',', (string) $atts['ids'])))));
        if (!$ids && isset($_GET['station_ids'])) {
            $ids = array_values(array_unique(array_filter(array_map('absint', explode(',', sanitize_text_field(wp_unslash($_GET['station_ids'])))))));
        }
        $ids = array_slice($ids, 0, 4);

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
            $image = get_the_post_thumbnail($post, 'medium');
            printf(
                '<th><a class="trappie-comparison-station" href="%s">%s<span>%s</span></a></th>',
                esc_url(get_permalink($post)),
                $image,
                esc_html(get_the_title($post))
            );
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
        $contact_page = get_page_by_path('contact');
        $contact_url = $contact_page instanceof WP_Post ? get_permalink($contact_page) : home_url('/contact/');

        return sprintf(
            '<div class="trappie-contact-cta"><h2>Contact opnemen</h2><p>Heb je een vraag of ontbreekt er informatie? Gebruik dan het algemene contactformulier.</p><a href="%s">Ga naar contact</a></div>',
            esc_url($contact_url)
        );
    }

    public static function contact_shortcode(): string
    {
        ob_start();
        if (isset($_GET['trappie_contact'])) {
            $status = sanitize_key(wp_unslash($_GET['trappie_contact']));
            if ($status === 'sent') {
                echo '<p class="trappie-notice">Bedankt. Je bericht is verzonden.</p>';
            } elseif ($status === 'invalid') {
                echo '<p class="trappie-notice trappie-notice-error">Controleer je naam, e-mailadres en bericht.</p>';
            } elseif ($status === 'error') {
                echo '<p class="trappie-notice trappie-notice-error">Het bericht kon niet worden verzonden. Probeer het later opnieuw.</p>';
            }
        }
        ?>
        <form class="trappie-contact-form" method="post">
            <?php wp_nonce_field('trappie_contact', 'trappie_contact_nonce'); ?>
            <input type="hidden" name="trappie_contact_action" value="1">
            <div class="trappie-contact-grid">
                <label><span>Naam</span><input type="text" name="contact_name" autocomplete="name" required></label>
                <label><span>E-mailadres</span><input type="email" name="contact_email" autocomplete="email" required></label>
            </div>
            <label><span>Onderwerp</span><input type="text" name="contact_subject" required></label>
            <label><span>Bericht</span><textarea name="contact_message" rows="7" required></textarea></label>
            <label class="trappie-honeypot" aria-hidden="true">Website<input type="text" name="contact_website" tabindex="-1" autocomplete="off"></label>
            <button type="submit">Bericht verzenden</button>
        </form>
        <?php
        return (string) ob_get_clean();
    }

    public static function handle_contact_form(): void
    {
        if (!isset($_POST['trappie_contact_action'])) {
            return;
        }

        $redirect = wp_get_referer() ?: home_url('/contact/');
        $redirect = remove_query_arg('trappie_contact', $redirect);

        if (!isset($_POST['trappie_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['trappie_contact_nonce'])), 'trappie_contact')) {
            wp_die(esc_html__('Ongeldig formulier.', 'trappie-weerstations'));
        }

        $honeypot = isset($_POST['contact_website']) ? sanitize_text_field(wp_unslash($_POST['contact_website'])) : '';
        if ($honeypot !== '') {
            wp_safe_redirect(add_query_arg('trappie_contact', 'sent', $redirect));
            exit;
        }

        $name = isset($_POST['contact_name']) ? sanitize_text_field(wp_unslash($_POST['contact_name'])) : '';
        $email = isset($_POST['contact_email']) ? sanitize_email(wp_unslash($_POST['contact_email'])) : '';
        $subject = isset($_POST['contact_subject']) ? sanitize_text_field(wp_unslash($_POST['contact_subject'])) : '';
        $message = isset($_POST['contact_message']) ? sanitize_textarea_field(wp_unslash($_POST['contact_message'])) : '';

        if ($name === '' || !is_email($email) || $subject === '' || $message === '') {
            wp_safe_redirect(add_query_arg('trappie_contact', 'invalid', $redirect));
            exit;
        }

        $mail_subject = sprintf('[%s] %s', wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES), $subject);
        $mail_message = "Naam: {$name}\nE-mail: {$email}\n\n{$message}";
        $headers = ['Reply-To: ' . $name . ' <' . $email . '>'];
        $sent = wp_mail(get_option('admin_email'), $mail_subject, $mail_message, $headers);

        wp_safe_redirect(add_query_arg('trappie_contact', $sent ? 'sent' : 'error', $redirect));
        exit;
    }

    public static function render_disclaimer(): void
    {
        echo '<p class="trappie-disclaimer">Disclaimer: deze website biedt informatief hobbyadvies over weerstations. Dit is geen webshop en er is geen winkelwagen, checkout of voorraadbeheer.</p>';
    }

    private static function station_brands(): array
    {
        static $brands;

        if (is_array($brands)) {
            return $brands;
        }

        $brands = [];
        $station_ids = get_posts([
            'post_type' => Trappie_Weerstations_Post_Types::STATION_POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        foreach ($station_ids as $station_id) {
            $brand = trim((string) get_post_meta($station_id, 'merk', true));
            if ($brand !== '') {
                $brands[$brand] = $brand;
            }
        }

        natcasesort($brands);
        return array_values($brands);
    }

    private static function comparison_url(): string
    {
        $page_ids = get_option('trappie_weerstations_page_ids', []);
        if (!empty($page_ids['weerstations-vergelijken'])) {
            return get_permalink((int) $page_ids['weerstations-vergelijken']);
        }

        return home_url('/weerstations-vergelijken/');
    }

    private static function render_station_cards(WP_Query $query, bool $comparison_enabled = true): void
    {
        if (!$query->have_posts()) {
            echo '<p>Geen weerstations gevonden.</p>';
            return;
        }

        echo '<div class="trappie-station-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            $station_id = get_the_ID();
            $brand = get_post_meta($station_id, 'merk', true);
            $price = get_post_meta($station_id, 'indicatieve_prijsklasse', true);
            $featured = get_post_meta($station_id, Trappie_Weerstations_Post_Types::FEATURED_META_KEY, true) === '1';
            echo '<article class="trappie-station-card">';
            if (has_post_thumbnail()) {
                printf('<a href="%s" class="trappie-station-image">%s%s</a>', esc_url(get_permalink()), get_the_post_thumbnail($station_id, 'medium_large'), $featured ? '<span class="trappie-card-badge">Uitgelicht</span>' : '');
            }
            echo '<div class="trappie-card-content">';
            if ($brand) {
                printf('<p class="trappie-card-brand">%s</p>', esc_html($brand));
            }
            printf('<h3><a href="%s">%s</a></h3>', esc_url(get_permalink()), esc_html(get_the_title()));
            if ($price) {
                printf('<p class="trappie-muted">%s</p>', esc_html($price));
            }
            echo '<p>' . esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(), 36, '...')) . '</p>';
            printf('<p><a class="trappie-detail-link" href="%s">Bekijk alle informatie</a></p>', esc_url(get_permalink()));
            if ($comparison_enabled) {
                printf(
                    '<label class="trappie-compare-option" for="trappie-compare-%1$d"><input id="trappie-compare-%1$d" type="checkbox" value="%1$d" data-compare-station> Vergelijk dit weerstation</label>',
                    $station_id
                );
            }
            echo '</div>';
            echo '</article>';
        }
        echo '</div>';

        if ($comparison_enabled) {
            printf(
                '<div class="trappie-compare-bar" data-compare-toolbar data-compare-url="%s" hidden><p><strong data-compare-count>0</strong> van maximaal 4 geselecteerd</p><a class="trappie-compare-button" href="%s" aria-disabled="true">Vergelijk geselecteerde stations</a></div>',
                esc_url(self::comparison_url()),
                esc_url(self::comparison_url())
            );
        }
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
