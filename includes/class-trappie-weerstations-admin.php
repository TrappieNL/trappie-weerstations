<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Trappie_Weerstations_Admin
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'admin_menu']);
        add_action('admin_post_trappie_create_station', [self::class, 'create_station_from_candidate']);
        add_action('admin_post_trappie_link_station', [self::class, 'link_candidate_to_station']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
    }

    public static function candidate_statuses(): array
    {
        return [
            'nieuw' => 'Nieuw',
            'controleren' => 'Controleren',
            'afgekeurd' => 'Afgekeurd',
            'goedgekeurd' => 'Goedgekeurd',
            'gepubliceerd' => 'Gepubliceerd',
        ];
    }

    public static function admin_menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . Trappie_Weerstations_Post_Types::STATION_POST_TYPE,
            'Gevonden kandidaten',
            'Gevonden kandidaten',
            'edit_posts',
            'trappie-kandidaten',
            [self::class, 'render_candidates_page']
        );
    }

    public static function enqueue_admin_assets(string $hook): void
    {
        if ($hook === 'weerstation_page_trappie-kandidaten') {
            wp_enqueue_style('trappie-admin', TRAPPIE_WEERSTATIONS_URL . 'assets/admin.css', [], TRAPPIE_WEERSTATIONS_VERSION);
        }
    }

    public static function render_candidates_page(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('Onvoldoende rechten.', 'trappie-weerstations'));
        }

        $status_filter = isset($_GET['candidate_status']) ? sanitize_key(wp_unslash($_GET['candidate_status'])) : '';
        $meta_query = [];
        if ($status_filter && isset(self::candidate_statuses()[$status_filter])) {
            $meta_query[] = [
                'key' => 'candidate_status',
                'value' => $status_filter,
            ];
        }

        $query = new WP_Query([
            'post_type' => Trappie_Weerstations_Post_Types::CANDIDATE_POST_TYPE,
            'post_status' => ['publish', 'draft', 'pending'],
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $meta_query,
        ]);

        echo '<div class="wrap"><h1>Gevonden kandidaten</h1>';
        self::render_status_filter($status_filter);
        echo '<table class="widefat fixed striped trappie-candidates"><thead><tr>';
        echo '<th>Titel</th><th>Status</th><th>Merk/model</th><th>Bron</th><th>Acties</th>';
        echo '</tr></thead><tbody>';

        if (!$query->have_posts()) {
            echo '<tr><td colspan="5">Geen kandidaten gevonden.</td></tr>';
        }

        while ($query->have_posts()) {
            $query->the_post();
            $candidate_id = get_the_ID();
            $status = get_post_meta($candidate_id, 'candidate_status', true) ?: 'nieuw';
            $merk = get_post_meta($candidate_id, 'merk', true);
            $model = get_post_meta($candidate_id, 'model', true);
            $source = get_post_meta($candidate_id, 'bron_url', true) ?: get_post_meta($candidate_id, 'source_url', true);

            echo '<tr>';
            printf('<td><a href="%s">%s</a></td>', esc_url(get_edit_post_link($candidate_id)), esc_html(get_the_title()));
            printf('<td>%s</td>', esc_html(self::candidate_statuses()[$status] ?? $status));
            printf('<td>%s</td>', esc_html(trim($merk . ' ' . $model)));
            printf('<td>%s</td>', $source ? '<a href="' . esc_url($source) . '" target="_blank" rel="noopener noreferrer">bron</a>' : '-');
            echo '<td class="trappie-actions">';
            self::render_create_form($candidate_id);
            self::render_link_form($candidate_id);
            echo '</td></tr>';
        }
        wp_reset_postdata();

        echo '</tbody></table></div>';
    }

    private static function render_status_filter(string $current): void
    {
        echo '<form method="get" class="trappie-status-filter">';
        printf('<input type="hidden" name="post_type" value="%s">', esc_attr(Trappie_Weerstations_Post_Types::STATION_POST_TYPE));
        echo '<input type="hidden" name="page" value="trappie-kandidaten">';
        echo '<select name="candidate_status"><option value="">Alle statussen</option>';
        foreach (self::candidate_statuses() as $key => $label) {
            printf('<option value="%s" %s>%s</option>', esc_attr($key), selected($current, $key, false), esc_html($label));
        }
        echo '</select> <button class="button">Filter</button></form>';
    }

    private static function render_create_form(int $candidate_id): void
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('trappie_create_station_' . $candidate_id);
        echo '<input type="hidden" name="action" value="trappie_create_station">';
        printf('<input type="hidden" name="candidate_id" value="%d">', $candidate_id);
        echo '<button class="button button-primary">Maak weerstation aan</button></form>';
    }

    private static function render_link_form(int $candidate_id): void
    {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('trappie_link_station_' . $candidate_id);
        echo '<input type="hidden" name="action" value="trappie_link_station">';
        printf('<input type="hidden" name="candidate_id" value="%d">', $candidate_id);
        echo '<input type="number" min="1" name="station_id" placeholder="Weerstation ID" required>';
        echo '<button class="button">Koppel aan bestaand weerstation</button></form>';
    }

    public static function create_station_from_candidate(): void
    {
        $candidate_id = isset($_POST['candidate_id']) ? absint($_POST['candidate_id']) : 0;
        self::verify_action($candidate_id, 'trappie_create_station_' . $candidate_id);

        $candidate = get_post($candidate_id);
        if (!$candidate || $candidate->post_type !== Trappie_Weerstations_Post_Types::CANDIDATE_POST_TYPE) {
            wp_die(esc_html__('Kandidaat niet gevonden.', 'trappie-weerstations'));
        }

        $station_id = wp_insert_post([
            'post_type' => Trappie_Weerstations_Post_Types::STATION_POST_TYPE,
            'post_status' => 'draft',
            'post_title' => $candidate->post_title,
            'post_content' => $candidate->post_content,
        ], true);

        if (is_wp_error($station_id)) {
            wp_die(esc_html($station_id->get_error_message()));
        }

        self::copy_station_meta($candidate_id, (int) $station_id);
        update_post_meta($candidate_id, 'candidate_status', 'gepubliceerd');
        update_post_meta($candidate_id, 'linked_station_id', (string) $station_id);

        wp_safe_redirect(admin_url('post.php?post=' . (int) $station_id . '&action=edit'));
        exit;
    }

    public static function link_candidate_to_station(): void
    {
        $candidate_id = isset($_POST['candidate_id']) ? absint($_POST['candidate_id']) : 0;
        $station_id = isset($_POST['station_id']) ? absint($_POST['station_id']) : 0;
        self::verify_action($candidate_id, 'trappie_link_station_' . $candidate_id);

        $station = get_post($station_id);
        if (!$station || $station->post_type !== Trappie_Weerstations_Post_Types::STATION_POST_TYPE) {
            wp_die(esc_html__('Weerstation niet gevonden.', 'trappie-weerstations'));
        }

        self::copy_station_meta($candidate_id, $station_id, false);
        update_post_meta($candidate_id, 'candidate_status', 'goedgekeurd');
        update_post_meta($candidate_id, 'linked_station_id', (string) $station_id);

        wp_safe_redirect(admin_url('edit.php?post_type=weerstation&page=trappie-kandidaten'));
        exit;
    }

    private static function verify_action(int $candidate_id, string $action): void
    {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('Onvoldoende rechten.', 'trappie-weerstations'));
        }
        check_admin_referer($action);
        if (!$candidate_id) {
            wp_die(esc_html__('Ongeldige kandidaat.', 'trappie-weerstations'));
        }
    }

    private static function copy_station_meta(int $candidate_id, int $station_id, bool $overwrite = true): void
    {
        foreach (array_keys(Trappie_Weerstations_Post_Types::STATION_FIELDS) as $key) {
            $value = get_post_meta($candidate_id, $key, true);
            if ($value === '') {
                continue;
            }
            if (!$overwrite && get_post_meta($station_id, $key, true) !== '') {
                continue;
            }
            update_post_meta($station_id, $key, $value);
        }
    }
}
