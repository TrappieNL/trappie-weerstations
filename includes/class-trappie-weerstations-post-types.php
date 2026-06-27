<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Trappie_Weerstations_Post_Types
{
    public const STATION_POST_TYPE = 'weerstation';
    public const SOURCE_POST_TYPE = 'crawler_source';
    public const CANDIDATE_POST_TYPE = 'crawler_candidate';
    public const OBSERVATION_POST_TYPE = 'crawler_observation';
    public const GALLERY_META_KEY = 'weerstation_afbeeldingen';

    public const TAXONOMIES = [
        'merk' => 'Merken',
        'sensoren' => 'Sensoren',
        'connectiviteit' => 'Connectiviteit',
        'weerplatformen' => 'Weerplatformen',
        'gebruikstype' => 'Gebruikstype',
    ];

    public const STATION_FIELDS = [
        'merk' => ['label' => 'Merk', 'type' => 'text'],
        'model' => ['label' => 'Model', 'type' => 'text'],
        'omschrijving' => ['label' => 'Omschrijving', 'type' => 'textarea'],
        'fabrikant_url' => ['label' => 'Fabrikant URL', 'type' => 'url'],
        'verkrijgbaar_in_nederland' => ['label' => 'Verkrijgbaar in Nederland', 'type' => 'checkbox'],
        'indicatieve_prijsklasse' => ['label' => 'Indicatieve prijsklasse', 'type' => 'text'],
        'meetwaarden' => ['label' => 'Meetwaarden', 'type' => 'textarea'],
        'refresh_rate' => ['label' => 'Refresh rate', 'type' => 'text'],
        'rf_frequentie' => ['label' => 'RF frequentie', 'type' => 'text'],
        'wifi' => ['label' => 'Wifi', 'type' => 'checkbox'],
        'ethernet' => ['label' => 'Ethernet', 'type' => 'checkbox'],
        'bluetooth' => ['label' => 'Bluetooth', 'type' => 'checkbox'],
        'batterij' => ['label' => 'Batterij', 'type' => 'checkbox'],
        'zonnepaneel' => ['label' => 'Zonnepaneel', 'type' => 'checkbox'],
        'uitbreidbare_sensoren' => ['label' => 'Uitbreidbare sensoren', 'type' => 'checkbox'],
        'compatible_weather_underground' => ['label' => 'Weather Underground', 'type' => 'checkbox'],
        'compatible_weathercloud' => ['label' => 'Weathercloud', 'type' => 'checkbox'],
        'compatible_ecowitt' => ['label' => 'Ecowitt', 'type' => 'checkbox'],
        'compatible_wow' => ['label' => 'WOW', 'type' => 'checkbox'],
        'compatible_home_assistant' => ['label' => 'Home Assistant', 'type' => 'checkbox'],
        'bron_url' => ['label' => 'Bron URL', 'type' => 'url'],
        'laatst_gecontroleerd' => ['label' => 'Laatst gecontroleerd', 'type' => 'date'],
        'betrouwbaarheidsscore' => ['label' => 'Betrouwbaarheidsscore', 'type' => 'number'],
        'opmerkingen' => ['label' => 'Opmerkingen', 'type' => 'textarea'],
    ];

    public static function init(): void
    {
        add_action('init', [self::class, 'register']);
        add_action('add_meta_boxes', [self::class, 'add_meta_boxes']);
        add_action('save_post_' . self::STATION_POST_TYPE, [self::class, 'save_station_meta']);
        add_action('save_post_' . self::CANDIDATE_POST_TYPE, [self::class, 'save_candidate_meta']);
    }

    public static function register(): void
    {
        register_post_type(self::STATION_POST_TYPE, [
            'labels' => [
                'name' => 'Weerstations',
                'singular_name' => 'Weerstation',
                'add_new_item' => 'Nieuw weerstation toevoegen',
                'edit_item' => 'Weerstation bewerken',
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'weerstations'],
            'menu_icon' => 'dashicons-cloud',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        ]);

        self::register_internal_post_type(self::SOURCE_POST_TYPE, 'Crawler bronnen', 'Crawler bron', 'dashicons-rss');
        self::register_internal_post_type(self::CANDIDATE_POST_TYPE, 'Crawler kandidaten', 'Crawler kandidaat', 'dashicons-search');
        self::register_internal_post_type(self::OBSERVATION_POST_TYPE, 'Crawler observaties', 'Crawler observatie', 'dashicons-visibility');

        foreach (self::TAXONOMIES as $taxonomy => $label) {
            register_taxonomy($taxonomy, [self::STATION_POST_TYPE], [
                'labels' => [
                    'name' => $label,
                    'singular_name' => rtrim($label, 'en'),
                ],
                'public' => true,
                'show_ui' => true,
                'show_in_rest' => true,
                'hierarchical' => false,
                'rewrite' => ['slug' => $taxonomy],
            ]);
        }

        self::register_meta_fields();
    }

    private static function register_internal_post_type(string $post_type, string $plural, string $singular, string $icon): void
    {
        register_post_type($post_type, [
            'labels' => [
                'name' => $plural,
                'singular_name' => $singular,
                'add_new_item' => $singular . ' toevoegen',
                'edit_item' => $singular . ' bewerken',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_in_menu' => 'edit.php?post_type=' . self::STATION_POST_TYPE,
            'show_in_admin_bar' => false,
            'menu_icon' => $icon,
            'supports' => ['title', 'editor', 'custom-fields'],
        ]);
    }

    private static function register_meta_fields(): void
    {
        foreach (array_keys(self::STATION_FIELDS) as $key) {
            register_post_meta(self::STATION_POST_TYPE, $key, [
                'single' => true,
                'type' => 'string',
                'show_in_rest' => true,
                'sanitize_callback' => [self::class, 'sanitize_meta_value'],
                'auth_callback' => [self::class, 'can_edit_meta'],
            ]);

            register_post_meta(self::CANDIDATE_POST_TYPE, $key, [
                'single' => true,
                'type' => 'string',
                'show_in_rest' => true,
                'sanitize_callback' => [self::class, 'sanitize_meta_value'],
                'auth_callback' => [self::class, 'can_edit_meta'],
            ]);
        }

        foreach (['candidate_status', 'linked_station_id', 'source_url', 'external_id', 'candidate_id', 'source_id', 'observation_type', 'observed_value'] as $key) {
            foreach ([self::SOURCE_POST_TYPE, self::CANDIDATE_POST_TYPE, self::OBSERVATION_POST_TYPE] as $post_type) {
                register_post_meta($post_type, $key, [
                    'single' => true,
                    'type' => 'string',
                    'show_in_rest' => true,
                    'sanitize_callback' => [self::class, 'sanitize_meta_value'],
                    'auth_callback' => [self::class, 'can_edit_meta'],
                ]);
            }
        }

        register_post_meta(self::STATION_POST_TYPE, self::GALLERY_META_KEY, [
            'single' => true,
            'type' => 'array',
            'show_in_rest' => [
                'schema' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                ],
            ],
            'sanitize_callback' => [self::class, 'sanitize_gallery_ids'],
            'auth_callback' => [self::class, 'can_edit_meta'],
        ]);
    }

    public static function sanitize_gallery_ids($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $image_ids = array_values(array_unique(array_filter(array_map('absint', $value))));

        return array_values(array_filter($image_ids, 'wp_attachment_is_image'));
    }

    public static function sanitize_meta_value($value): string
    {
        if (is_array($value) || is_object($value)) {
            $value = wp_json_encode($value);
        }

        return sanitize_textarea_field((string) $value);
    }

    public static function can_edit_meta(...$args): bool
    {
        return current_user_can('edit_posts');
    }

    public static function add_meta_boxes(): void
    {
        add_meta_box('trappie_station_details', 'Weerstation gegevens', [self::class, 'render_station_meta_box'], self::STATION_POST_TYPE, 'normal', 'high');
        add_meta_box('trappie_candidate_details', 'Kandidaat gegevens', [self::class, 'render_candidate_meta_box'], self::CANDIDATE_POST_TYPE, 'normal', 'high');
    }

    public static function render_station_meta_box(WP_Post $post): void
    {
        wp_nonce_field('trappie_save_station_meta', 'trappie_station_meta_nonce');
        self::render_fields($post, self::STATION_FIELDS);
    }

    public static function render_candidate_meta_box(WP_Post $post): void
    {
        wp_nonce_field('trappie_save_candidate_meta', 'trappie_candidate_meta_nonce');
        self::render_status_field($post);
        self::render_fields($post, self::STATION_FIELDS);
    }

    private static function render_status_field(WP_Post $post): void
    {
        $status = get_post_meta($post->ID, 'candidate_status', true) ?: 'nieuw';
        $statuses = Trappie_Weerstations_Admin::candidate_statuses();
        echo '<p><label for="trappie_candidate_status"><strong>Status</strong></label><br>';
        echo '<select id="trappie_candidate_status" name="trappie_meta[candidate_status]">';
        foreach ($statuses as $key => $label) {
            printf('<option value="%s" %s>%s</option>', esc_attr($key), selected($status, $key, false), esc_html($label));
        }
        echo '</select></p>';
    }

    private static function render_fields(WP_Post $post, array $fields): void
    {
        echo '<div class="trappie-meta-grid">';
        foreach ($fields as $key => $field) {
            $value = get_post_meta($post->ID, $key, true);
            echo '<p>';
            printf('<label for="trappie_%1$s"><strong>%2$s</strong></label><br>', esc_attr($key), esc_html($field['label']));
            if ($field['type'] === 'textarea') {
                printf('<textarea id="trappie_%1$s" name="trappie_meta[%1$s]" rows="4" style="width:100%%;">%2$s</textarea>', esc_attr($key), esc_textarea($value));
            } elseif ($field['type'] === 'checkbox') {
                printf('<label><input type="checkbox" id="trappie_%1$s" name="trappie_meta[%1$s]" value="1" %2$s> Ja</label>', esc_attr($key), checked($value, '1', false));
            } else {
                printf('<input id="trappie_%1$s" name="trappie_meta[%1$s]" type="%2$s" value="%3$s" style="width:100%%;">', esc_attr($key), esc_attr($field['type']), esc_attr($value));
            }
            echo '</p>';
        }
        echo '</div>';
    }

    public static function save_station_meta(int $post_id): void
    {
        if (!self::can_save($post_id, 'trappie_station_meta_nonce', 'trappie_save_station_meta')) {
            return;
        }

        self::save_meta_array($post_id, self::STATION_FIELDS);
    }

    public static function save_candidate_meta(int $post_id): void
    {
        if (!self::can_save($post_id, 'trappie_candidate_meta_nonce', 'trappie_save_candidate_meta')) {
            return;
        }

        self::save_meta_array($post_id, array_merge(self::STATION_FIELDS, ['candidate_status' => ['type' => 'text']]));
    }

    private static function can_save(int $post_id, string $nonce_name, string $action): bool
    {
        if (!isset($_POST[$nonce_name]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_name])), $action)) {
            return false;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        return current_user_can('edit_post', $post_id);
    }

    public static function save_meta_array(int $post_id, array $fields): void
    {
        $posted = isset($_POST['trappie_meta']) && is_array($_POST['trappie_meta']) ? wp_unslash($_POST['trappie_meta']) : [];
        foreach ($fields as $key => $field) {
            $type = $field['type'] ?? 'text';
            if ($type === 'checkbox') {
                update_post_meta($post_id, $key, isset($posted[$key]) ? '1' : '0');
                continue;
            }

            if (!isset($posted[$key])) {
                continue;
            }

            update_post_meta($post_id, $key, self::sanitize_by_type($posted[$key], $type));
        }
    }

    public static function sanitize_by_type($value, string $type): string
    {
        $value = is_scalar($value) ? (string) $value : '';
        if ($type === 'url') {
            return esc_url_raw($value);
        }
        if ($type === 'number') {
            return (string) max(0, min(100, (int) $value));
        }
        if ($type === 'date') {
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
        }

        return $type === 'textarea' ? sanitize_textarea_field($value) : sanitize_text_field($value);
    }
}
