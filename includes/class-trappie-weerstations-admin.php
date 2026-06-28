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
        add_action('admin_post_trappie_bulk_candidates', [self::class, 'handle_bulk_candidates']);
        add_action('admin_post_trappie_link_station', [self::class, 'link_candidate_to_station']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
        add_action('add_meta_boxes_' . Trappie_Weerstations_Post_Types::STATION_POST_TYPE, [self::class, 'add_gallery_meta_box']);
        add_action('save_post_' . Trappie_Weerstations_Post_Types::STATION_POST_TYPE, [self::class, 'save_gallery'], 20);
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

        add_submenu_page(
            'edit.php?post_type=' . Trappie_Weerstations_Post_Types::STATION_POST_TYPE,
            'Gebruikershandleiding',
            'Handleiding',
            'edit_posts',
            'trappie-handleiding',
            [self::class, 'render_manual_page']
        );
    }

    public static function enqueue_admin_assets(string $hook): void
    {
        $screen = get_current_screen();
        $is_station_editor = $screen &&
            $screen->post_type === Trappie_Weerstations_Post_Types::STATION_POST_TYPE &&
            in_array($hook, ['post.php', 'post-new.php'], true);
        $is_plugin_page = in_array($hook, [
            'weerstation_page_trappie-kandidaten',
            'weerstation_page_trappie-handleiding',
        ], true);

        if (!$is_station_editor && !$is_plugin_page) {
            return;
        }

        wp_enqueue_style('trappie-admin', TRAPPIE_WEERSTATIONS_URL . 'assets/admin.css', [], TRAPPIE_WEERSTATIONS_VERSION);
        wp_enqueue_script('trappie-admin', TRAPPIE_WEERSTATIONS_URL . 'assets/admin.js', [], TRAPPIE_WEERSTATIONS_VERSION, true);
        wp_localize_script('trappie-admin', 'trappieAdmin', [
            'mediaTitle' => 'Kies afbeeldingen voor dit weerstation',
            'mediaButton' => 'Afbeeldingen gebruiken',
            'removeImage' => 'Afbeelding verwijderen',
            'confirmPermanentDelete' => 'Weet je zeker dat je de geselecteerde kandidaten permanent wilt verwijderen? Dit kan niet ongedaan worden gemaakt.',
        ]);

        if ($is_station_editor) {
            wp_enqueue_media();
        }
    }

    public static function add_gallery_meta_box(): void
    {
        add_meta_box(
            'trappie_station_gallery',
            'Weerstation afbeeldingen',
            [self::class, 'render_gallery_meta_box'],
            Trappie_Weerstations_Post_Types::STATION_POST_TYPE,
            'normal',
            'default'
        );
    }

    public static function render_gallery_meta_box(WP_Post $post): void
    {
        wp_nonce_field('trappie_save_gallery', 'trappie_gallery_nonce');
        $image_ids = get_post_meta($post->ID, Trappie_Weerstations_Post_Types::GALLERY_META_KEY, true);
        $image_ids = Trappie_Weerstations_Post_Types::sanitize_gallery_ids($image_ids);
        ?>
        <div class="trappie-gallery-field" data-gallery-field>
            <input type="hidden" name="trappie_gallery_ids" value="<?php echo esc_attr(implode(',', $image_ids)); ?>" data-gallery-input>
            <ul class="trappie-gallery-list" data-gallery-list>
                <?php foreach ($image_ids as $image_id) : ?>
                    <?php if (!wp_attachment_is_image($image_id)) { continue; } ?>
                    <li data-image-id="<?php echo esc_attr((string) $image_id); ?>">
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                        <button type="button" class="button-link-delete" data-remove-image aria-label="<?php esc_attr_e('Afbeelding verwijderen', 'trappie-weerstations'); ?>">Verwijderen</button>
                    </li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="button" data-add-images>Afbeeldingen kiezen</button>
            <p class="description">Selecteer meerdere afbeeldingen uit de mediabibliotheek. De uitgelichte afbeelding blijft het hoofdbeeld; zonder uitgelichte afbeelding wordt de eerste galerijafbeelding als hoofdbeeld gebruikt.</p>
        </div>
        <?php
    }

    public static function save_gallery(int $post_id): void
    {
        if (
            !isset($_POST['trappie_gallery_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['trappie_gallery_nonce'])), 'trappie_save_gallery') ||
            (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
            !current_user_can('edit_post', $post_id)
        ) {
            return;
        }

        $raw_ids = isset($_POST['trappie_gallery_ids'])
            ? sanitize_text_field(wp_unslash($_POST['trappie_gallery_ids']))
            : '';
        $image_ids = Trappie_Weerstations_Post_Types::sanitize_gallery_ids(explode(',', $raw_ids));
        $image_ids = array_values(array_filter($image_ids, 'wp_attachment_is_image'));

        if ($image_ids) {
            update_post_meta($post_id, Trappie_Weerstations_Post_Types::GALLERY_META_KEY, $image_ids);
        } else {
            delete_post_meta($post_id, Trappie_Weerstations_Post_Types::GALLERY_META_KEY);
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
            echo '<tr>';
            echo '<th scope="row" class="check-column">';
            printf(
                '<input form="trappie-bulk-form" class="trappie-candidate-checkbox" type="checkbox" name="candidate_ids[]" value="%d"><span class="screen-reader-text">%s selecteren</span>',
                $candidate_id,
                esc_html(get_the_title())
            );
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
        echo '<option value="set_status">Status veranderen</option>';
        echo '<option value="trash">Naar prullenbak</option>';
        echo '<option value="delete_permanently">Permanent verwijderen</option>';
        echo '</select>';
        echo '<select name="target_status" data-bulk-status hidden>';
        echo '<option value="">Kies nieuwe status</option>';
        foreach (self::candidate_statuses() as $key => $label) {
            printf('<option value="%s">%s</option>', esc_attr($key), esc_html($label));
        }
        echo '</select>';
        echo '<button type="submit" class="button action">Toepassen</button>';
        echo '</form>';
    }

    private static function render_bulk_notice(): void
    {
        if (!isset($_GET['trappie_bulk_done'])) {
            return;
        }

        $operation = isset($_GET['bulk_operation']) ? sanitize_key(wp_unslash($_GET['bulk_operation'])) : '';
        $processed = isset($_GET['processed']) ? absint($_GET['processed']) : 0;
        $skipped = isset($_GET['skipped']) ? absint($_GET['skipped']) : 0;
        $failed = isset($_GET['failed']) ? absint($_GET['failed']) : 0;
        $class = $failed > 0 ? 'notice notice-warning is-dismissible' : 'notice notice-success is-dismissible';

        if ($operation === 'set_status') {
            $target_status = isset($_GET['target_status']) ? sanitize_key(wp_unslash($_GET['target_status'])) : '';
            $status_label = self::candidate_statuses()[$target_status] ?? $target_status;
            $message = sprintf('%d kandidaat/kandidaten gewijzigd naar status %s, %d overgeslagen en %d mislukt.', $processed, $status_label, $skipped, $failed);
        } elseif ($operation === 'trash') {
            $message = sprintf('%d kandidaat/kandidaten naar de prullenbak verplaatst en %d mislukt.', $processed, $failed);
        } elseif ($operation === 'delete_permanently') {
            $message = sprintf('%d kandidaat/kandidaten permanent verwijderd en %d mislukt.', $processed, $failed);
        } else {
            $message = sprintf('%d weerstation(s) gepubliceerd, %d overgeslagen en %d mislukt.', $processed, $skipped, $failed);
        }

        printf(
            '<div class="%s"><p>%s</p></div>',
            esc_attr($class),
            esc_html($message)
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
            'post_title' => self::station_title_from_candidate($candidate_id, $candidate->post_title),
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

    public static function handle_bulk_candidates(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('Onvoldoende rechten.', 'trappie-weerstations'));
        }

        check_admin_referer('trappie_bulk_candidates', 'trappie_bulk_nonce');

        $bulk_action = isset($_POST['bulk_action']) ? sanitize_key(wp_unslash($_POST['bulk_action'])) : '';
        $target_status = isset($_POST['target_status']) ? sanitize_key(wp_unslash($_POST['target_status'])) : '';
        $raw_ids = isset($_POST['candidate_ids']) && is_array($_POST['candidate_ids']) ? wp_unslash($_POST['candidate_ids']) : [];
        $candidate_ids = array_values(array_unique(array_filter(array_map('absint', $raw_ids))));
        $allowed_actions = ['create_publish', 'set_status', 'trash', 'delete_permanently'];

        if (!in_array($bulk_action, $allowed_actions, true) || !$candidate_ids) {
            wp_die(esc_html__('Selecteer minimaal een kandidaat en een geldige bulkactie.', 'trappie-weerstations'));
        }

        if ($bulk_action === 'set_status' && !isset(self::candidate_statuses()[$target_status])) {
            wp_die(esc_html__('Kies een geldige nieuwe status.', 'trappie-weerstations'));
        }

        if ($bulk_action === 'create_publish' && !current_user_can('publish_posts')) {
            wp_die(esc_html__('Onvoldoende rechten om weerstations te publiceren.', 'trappie-weerstations'));
        }

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($candidate_ids as $candidate_id) {
            $candidate = get_post($candidate_id);
            if (!$candidate || $candidate->post_type !== Trappie_Weerstations_Post_Types::CANDIDATE_POST_TYPE) {
                $failed++;
                continue;
            }

            if (in_array($bulk_action, ['trash', 'delete_permanently'], true)) {
                if (!current_user_can('delete_post', $candidate_id)) {
                    $failed++;
                    continue;
                }

                $result = $bulk_action === 'trash'
                    ? wp_trash_post($candidate_id)
                    : wp_delete_post($candidate_id, true);

                if (!$result) {
                    $failed++;
                } else {
                    $processed++;
                }
                continue;
            }

            if (!current_user_can('edit_post', $candidate_id)) {
                $failed++;
                continue;
            }

            if ($bulk_action === 'set_status') {
                $current_status = get_post_meta($candidate_id, 'candidate_status', true) ?: 'nieuw';
                if ($current_status === $target_status) {
                    $skipped++;
                    continue;
                }

                update_post_meta($candidate_id, 'candidate_status', $target_status);
                $processed++;
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
                $processed++;
                continue;
            }

            $station_id = wp_insert_post([
                'post_type' => Trappie_Weerstations_Post_Types::STATION_POST_TYPE,
                'post_status' => 'publish',
                'post_title' => self::station_title_from_candidate($candidate_id, $candidate->post_title),
                'post_content' => $candidate->post_content,
            ], true);

            if (is_wp_error($station_id)) {
                $failed++;
                continue;
            }

            self::copy_station_meta($candidate_id, (int) $station_id);
            update_post_meta($candidate_id, 'candidate_status', 'gepubliceerd');
            update_post_meta($candidate_id, 'linked_station_id', (string) $station_id);
            $processed++;
        }

        $redirect = add_query_arg([
            'post_type' => Trappie_Weerstations_Post_Types::STATION_POST_TYPE,
            'page' => 'trappie-kandidaten',
            'trappie_bulk_done' => '1',
            'bulk_operation' => $bulk_action,
            'target_status' => $target_status,
            'processed' => $processed,
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

        self::copy_candidate_images($candidate_id, $station_id, $overwrite);
    }

    private static function station_title_from_candidate(int $candidate_id, string $fallback): string
    {
        $merk = trim((string) get_post_meta($candidate_id, 'merk', true));
        $model = trim((string) get_post_meta($candidate_id, 'model', true));
        $title = trim($merk . ' ' . $model);

        return $title !== '' ? $title : $fallback;
    }

    private static function copy_candidate_images(int $candidate_id, int $station_id, bool $overwrite): void
    {
        $image_ids = [];
        $thumbnail_id = get_post_thumbnail_id($candidate_id);
        if ($thumbnail_id) {
            $image_ids[] = (int) $thumbnail_id;
        }

        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_parent' => $candidate_id,
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'orderby' => 'menu_order ID',
            'order' => 'ASC',
            'fields' => 'ids',
        ]);
        $image_ids = array_values(array_unique(array_merge($image_ids, array_map('absint', $attachments))));
        if (!$image_ids) {
            return;
        }

        $gallery_key = Trappie_Weerstations_Post_Types::GALLERY_META_KEY;
        if ($overwrite || !get_post_meta($station_id, $gallery_key, true)) {
            update_post_meta($station_id, $gallery_key, $image_ids);
        }
        if ($overwrite || !has_post_thumbnail($station_id)) {
            set_post_thumbnail($station_id, $image_ids[0]);
        }
    }

    public static function render_manual_page(): void
    {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('Onvoldoende rechten.', 'trappie-weerstations'));
        }
        ?>
        <div class="wrap trappie-manual">
            <h1>Gebruikershandleiding Trappie Weerstations</h1>
            <p class="trappie-manual-intro">Deze plugin beheert weerstations, crawlerkandidaten en de publieke overzichten. De plugin is informatief en bevat geen webshopfuncties.</p>

            <nav class="trappie-manual-nav" aria-label="Onderdelen van de handleiding">
                <a href="#stations">Weerstations</a>
                <a href="#afbeeldingen">Afbeeldingen</a>
                <a href="#kandidaten">Kandidaten</a>
                <a href="#prijzen">Prijzen en teksten</a>
                <a href="#shortcodes">Pagina's</a>
            </nav>

            <section id="stations">
                <h2>Een weerstation beheren</h2>
                <ol>
                    <li>Open <strong>Weerstations</strong> in het WordPress-menu.</li>
                    <li>Kies een bestaand weerstation of klik op <strong>Nieuw weerstation</strong>.</li>
                    <li>Vul titel, volledige beschrijving en de technische velden in.</li>
                    <li>Gebruik <strong>Uitgelichte afbeelding</strong> voor het belangrijkste productbeeld.</li>
                    <li>Vink <strong>Uitgelicht weerstation</strong> aan voor opname in de selectie op de website.</li>
                    <li>Klik op <strong>Publiceren</strong> of <strong>Bijwerken</strong>.</li>
                </ol>
            </section>

            <section id="afbeeldingen">
                <h2>Afbeeldingen toevoegen</h2>
                <ol>
                    <li>Open het weerstation dat je wilt aanpassen.</li>
                    <li>Ga naar het blok <strong>Weerstation afbeeldingen</strong>.</li>
                    <li>Klik op <strong>Afbeeldingen kiezen</strong> en selecteer een of meerdere afbeeldingen.</li>
                    <li>Verwijder een afbeelding met de knop onder de miniatuur.</li>
                    <li>Sla het weerstation op. De afbeeldingen verschijnen op de detailpagina.</li>
                </ol>
            </section>

            <section id="kandidaten">
                <h2>Kandidaten controleren en publiceren</h2>
                <p>Open <strong>Weerstations &gt; Gevonden kandidaten</strong>. Controleer bron, merk, model, prijs en omschrijving voordat je publiceert.</p>
                <p>Voor bulkpublicatie selecteer je meerdere kandidaten, kies je <strong>Maak en publiceer weerstations</strong> en klik je op <strong>Toepassen</strong>.</p>
            </section>

            <section id="prijzen">
                <h2>Prijzen en afgekorte teksten</h2>
                <p>De plugin vult geen standaardprijs in. De indicatieve prijsklasse komt uit de opgeslagen kandidaat- of crawlinformatie en moet handmatig worden gecontroleerd.</p>
                <p>Overzichtskaarten tonen bewust een korte samenvatting. De detailpagina toont de volledige opgeslagen inhoud. Eindigt ook de detailtekst op drie puntjes, dan is de aangeleverde broninformatie al afgekapt.</p>
            </section>

            <section id="shortcodes">
                <h2>Publieke pagina's en shortcodes</h2>
                <table class="widefat striped">
                    <thead><tr><th>Functie</th><th>Shortcode</th></tr></thead>
                    <tbody>
                        <tr><td>Overzicht</td><td><code>[weerstations_overzicht]</code></td></tr>
                        <tr><td>Uitgelicht</td><td><code>[weerstations_uitgelicht aantal="4"]</code></td></tr>
                        <tr><td>Filter</td><td><code>[weerstations_filter]</code></td></tr>
                        <tr><td>Vergelijking</td><td><code>[weerstations_vergelijking]</code></td></tr>
                        <tr><td>Contactformulier</td><td><code>[trappie_contactformulier]</code></td></tr>
                    </tbody>
                </table>
            </section>
        </div>
        <?php
    }
}
