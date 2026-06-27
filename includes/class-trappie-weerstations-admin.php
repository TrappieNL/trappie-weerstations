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
        add_action('admin_post_trappie_bulk_candidates', [self::class, 'bulk_create_and_publish']);
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
            wp_enqueue_script('trappie-admin', TRAPPIE_WEERSTATIONS_URL . 'assets/admin.js', [], TRAPPIE_WEERSTATIONS_VERSION, true);
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
        self::render_bulk_notice();
        self::render_status_filter($status_filter);
        self::render_bulk_controls();
        echo '<table class="widefat fixed striped trappie-candidates"><thead><tr>';
        echo '<td class="manage-column column-cb check-column"><input type="checkbox" id="trappie-select-all"><span class="screen-reader-text">Alles selecteren</span></td>';
        echo '<th>Titel</th><th>Status</th><th>Merk/model</th><th>Bron</th><th>Acties</th>';
        echo '</tr></thead><tbody>';

        if (!$query->have_posts()) {
            echo '<tr><td colspan="6">Geen kandidaten gevonden.</td></tr>';
        }

        while ($query->have_posts()) {
            $query->the_post();
            $candidate_id = get_the_ID();
            $status = get_post_meta($candidate_id, 'candidate_status', true) ?: 'nieuw';
            $merk = get_post_meta($candidate_id, 'merk', true);
            $model = get_post_meta($candidate_id, 'model', true);
            $source = get_post_meta($candidate_id, 'bron_url', true) ?: get_post_meta($candidate_id, 'source_url', true);
            $linked_station_id = absint(get_post_meta($candidate_id, 'linked_station_id', true));
            $linked_station = $linked_station_id ? get_post($linked_station_id) : null;
            $station_is_published = $linked_station &&
                $linked_station->post_type === Trappie_Weerstations_Post_Types::STATION_POST_TYPE &&
                $linked_station->post_status === 'publish';

            echo '<tr>';
            echo '<th scope="row" class="check-column">';
            if (!$station_is_published) {
                printf(
                    '<input form="trappie-bulk-form" class="trappie-candidate-checkbox" type="checkbox" name="candidate_ids[]" value="%d"><span class="screen-reader-text">%s selecteren</span>',
                    $candidate_id,
                    esc_html(get_the_title())
                );
            }
            echo '</th>';
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

    private static function render_bulk_controls(): void
    {
        echo '<form id="trappie-bulk-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="trappie-bulk-form">';
        wp_nonce_field('trappie_bulk_candidates', 'trappie_bulk_nonce');
        echo '<input type="hidden" name="action" value="trappie_bulk_candidates">';
        echo '<select name="bulk_action" required>';
        echo '<option value="">Bulkacties</option>';
        echo '<option value="create_publish">Maak en publiceer weerstations</option>';
        echo '</select>';
        echo '<button type="submit" class="button action">Toepassen</button>';
        echo '</form>';
    }

    private static function render_bulk_notice(): void
    {
        if (!isset($_GET['trappie_bulk_done'])) {
            return;
        }

        $published = isset($_GET['published']) ? absint($_GET['published']) : 0;
        $skipped = isset($_GET['skipped']) ? absint($_GET['skipped']) : 0;
        $failed = isset($_GET['failed']) ? absint($_GET['failed']) : 0;
        $class = $failed > 0 ? 'notice notice-warning is-dismissible' : 'notice notice-success is-dismissible';

        printf(
            '<div class="%s"><p>%s</p></div>',
            esc_attr($class),
            esc_html(sprintf('%d weerstation(s) gepubliceerd, %d overgeslagen en %d mislukt.', $published, $skipped, $failed))
        );
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

    public static function bulk_create_and_publish(): void
    {
        if (!current_user_can('publish_posts')) {
            wp_die(esc_html__('Onvoldoende rechten om weerstations te publiceren.', 'trappie-weerstations'));
        }

        check_admin_referer('trappie_bulk_candidates', 'trappie_bulk_nonce');

        $bulk_action = isset($_POST['bulk_action']) ? sanitize_key(wp_unslash($_POST['bulk_action'])) : '';
        $raw_ids = isset($_POST['candidate_ids']) && is_array($_POST['candidate_ids']) ? wp_unslash($_POST['candidate_ids']) : [];
        $candidate_ids = array_values(array_unique(array_filter(array_map('absint', $raw_ids))));

        if ($bulk_action !== 'create_publish' || !$candidate_ids) {
            wp_die(esc_html__('Selecteer minimaal één kandidaat en een geldige bulkactie.', 'trappie-weerstations'));
        }

        $published = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($candidate_ids as $candidate_id) {
            $candidate = get_post($candidate_id);
            if (
                !$candidate ||
                $candidate->post_type !== Trappie_Weerstations_Post_Types::CANDIDATE_POST_TYPE ||
                !current_user_can('edit_post', $candidate_id)
            ) {
                $failed++;
                continue;
            }

            $linked_station_id = absint(get_post_meta($candidate_id, 'linked_station_id', true));
            if ($linked_station_id) {
                $linked_station = get_post($linked_station_id);
                if (!$linked_station || $linked_station->post_type !== Trappie_Weerstations_Post_Types::STATION_POST_TYPE) {
                    $failed++;
                    continue;
                }

                if ($linked_station->post_status === 'publish') {
                    update_post_meta($candidate_id, 'candidate_status', 'gepubliceerd');
                    $skipped++;
                    continue;
                }

                if (!current_user_can('edit_post', $linked_station_id)) {
                    $failed++;
                    continue;
                }

                $result = wp_update_post([
                    'ID' => $linked_station_id,
                    'post_status' => 'publish',
                ], true);

                if (is_wp_error($result)) {
                    $failed++;
                    continue;
                }

                update_post_meta($candidate_id, 'candidate_status', 'gepubliceerd');
                $published++;
                continue;
            }

            $station_id = wp_insert_post([
                'post_type' => Trappie_Weerstations_Post_Types::STATION_POST_TYPE,
                'post_status' => 'publish',
                'post_title' => $candidate->post_title,
                'post_content' => $candidate->post_content,
            ], true);

            if (is_wp_error($station_id)) {
                $failed++;
                continue;
            }

            self::copy_station_meta($candidate_id, (int) $station_id);
            update_post_meta($candidate_id, 'candidate_status', 'gepubliceerd');
            update_post_meta($candidate_id, 'linked_station_id', (string) $station_id);
            $published++;
        }

        $redirect = add_query_arg([
            'post_type' => Trappie_Weerstations_Post_Types::STATION_POST_TYPE,
            'page' => 'trappie-kandidaten',
            'trappie_bulk_done' => '1',
            'published' => $published,
            'skipped' => $skipped,
            'failed' => $failed,
        ], admin_url('edit.php'));

        wp_safe_redirect($redirect);
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
