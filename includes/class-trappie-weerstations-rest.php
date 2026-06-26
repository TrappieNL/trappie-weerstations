<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Trappie_Weerstations_REST
{
    private const NAMESPACE = 'trappie-weerstations/v1';

    public static function init(): void
    {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes(): void
    {
        register_rest_route(self::NAMESPACE, '/sources', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'upsert_source'],
            'permission_callback' => [self::class, 'can_write'],
        ]);

        register_rest_route(self::NAMESPACE, '/candidates', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'create_candidate'],
            'permission_callback' => [self::class, 'can_write'],
        ]);

        register_rest_route(self::NAMESPACE, '/candidates/(?P<id>\d+)', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [self::class, 'update_candidate'],
            'permission_callback' => [self::class, 'can_write'],
        ]);

        register_rest_route(self::NAMESPACE, '/observations', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [self::class, 'create_observation'],
            'permission_callback' => [self::class, 'can_write'],
        ]);
    }

    public static function can_write(): bool
    {
        return current_user_can('edit_posts');
    }

    public static function upsert_source(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_json_params() ?: $request->get_params();
        $source_url = isset($params['source_url']) ? esc_url_raw($params['source_url']) : '';
        $external_id = isset($params['external_id']) ? sanitize_text_field($params['external_id']) : '';
        $title = isset($params['title']) ? sanitize_text_field($params['title']) : ($source_url ?: 'Crawler bron');

        $existing_id = self::find_existing(Trappie_Weerstations_Post_Types::SOURCE_POST_TYPE, $external_id, $source_url);
        $postarr = [
            'post_type' => Trappie_Weerstations_Post_Types::SOURCE_POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $title,
            'post_content' => isset($params['notes']) ? sanitize_textarea_field($params['notes']) : '',
        ];
        if ($existing_id) {
            $postarr['ID'] = $existing_id;
        }

        $post_id = $existing_id ? wp_update_post($postarr, true) : wp_insert_post($postarr, true);
        if (is_wp_error($post_id)) {
            return self::error_response($post_id);
        }

        self::update_meta((int) $post_id, [
            'source_url' => $source_url,
            'external_id' => $external_id,
        ]);

        return new WP_REST_Response(['id' => (int) $post_id, 'updated' => (bool) $existing_id], $existing_id ? 200 : 201);
    }

    public static function create_candidate(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_json_params() ?: $request->get_params();
        $title = isset($params['title']) ? sanitize_text_field($params['title']) : self::candidate_title($params);

        $post_id = wp_insert_post([
            'post_type' => Trappie_Weerstations_Post_Types::CANDIDATE_POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $title,
            'post_content' => isset($params['omschrijving']) ? sanitize_textarea_field($params['omschrijving']) : '',
        ], true);

        if (is_wp_error($post_id)) {
            return self::error_response($post_id);
        }

        self::save_candidate_payload((int) $post_id, $params);
        update_post_meta((int) $post_id, 'candidate_status', 'nieuw');

        return new WP_REST_Response(['id' => (int) $post_id], 201);
    }

    public static function update_candidate(WP_REST_Request $request): WP_REST_Response
    {
        $candidate_id = absint($request['id']);
        $candidate = get_post($candidate_id);
        if (!$candidate || $candidate->post_type !== Trappie_Weerstations_Post_Types::CANDIDATE_POST_TYPE) {
            return new WP_REST_Response(['message' => 'Kandidaat niet gevonden.'], 404);
        }

        $params = $request->get_json_params() ?: $request->get_params();
        if (isset($params['title']) || isset($params['omschrijving'])) {
            $postarr = ['ID' => $candidate_id];
            if (isset($params['title'])) {
                $postarr['post_title'] = sanitize_text_field($params['title']);
            }
            if (isset($params['omschrijving'])) {
                $postarr['post_content'] = sanitize_textarea_field($params['omschrijving']);
            }
            wp_update_post($postarr);
        }

        self::save_candidate_payload($candidate_id, $params);
        if (isset($params['candidate_status']) && isset(Trappie_Weerstations_Admin::candidate_statuses()[sanitize_key($params['candidate_status'])])) {
            update_post_meta($candidate_id, 'candidate_status', sanitize_key($params['candidate_status']));
        }

        return new WP_REST_Response(['id' => $candidate_id, 'updated' => true], 200);
    }

    public static function create_observation(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_json_params() ?: $request->get_params();
        $title = isset($params['title']) ? sanitize_text_field($params['title']) : 'Crawler observatie';

        $post_id = wp_insert_post([
            'post_type' => Trappie_Weerstations_Post_Types::OBSERVATION_POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $title,
            'post_content' => isset($params['notes']) ? sanitize_textarea_field($params['notes']) : '',
        ], true);

        if (is_wp_error($post_id)) {
            return self::error_response($post_id);
        }

        self::update_meta((int) $post_id, [
            'candidate_id' => isset($params['candidate_id']) ? (string) absint($params['candidate_id']) : '',
            'source_id' => isset($params['source_id']) ? (string) absint($params['source_id']) : '',
            'observation_type' => isset($params['observation_type']) ? sanitize_key($params['observation_type']) : '',
            'observed_value' => isset($params['observed_value']) ? Trappie_Weerstations_Post_Types::sanitize_meta_value($params['observed_value']) : '',
            'source_url' => isset($params['source_url']) ? esc_url_raw($params['source_url']) : '',
        ]);

        return new WP_REST_Response(['id' => (int) $post_id], 201);
    }

    private static function save_candidate_payload(int $post_id, array $params): void
    {
        $meta = [];
        foreach (Trappie_Weerstations_Post_Types::STATION_FIELDS as $key => $field) {
            if (!array_key_exists($key, $params)) {
                continue;
            }
            $type = $field['type'];
            $meta[$key] = $type === 'checkbox'
                ? (!empty($params[$key]) ? '1' : '0')
                : Trappie_Weerstations_Post_Types::sanitize_by_type($params[$key], $type);
        }

        foreach (['source_url', 'external_id'] as $key) {
            if (isset($params[$key])) {
                $meta[$key] = $key === 'source_url' ? esc_url_raw($params[$key]) : sanitize_text_field($params[$key]);
            }
        }

        self::update_meta($post_id, $meta);
    }

    private static function update_meta(int $post_id, array $meta): void
    {
        foreach ($meta as $key => $value) {
            if ($value !== '') {
                update_post_meta($post_id, $key, $value);
            }
        }
    }

    private static function find_existing(string $post_type, string $external_id, string $source_url): int
    {
        $meta_query = ['relation' => 'OR'];
        if ($external_id !== '') {
            $meta_query[] = ['key' => 'external_id', 'value' => $external_id];
        }
        if ($source_url !== '') {
            $meta_query[] = ['key' => 'source_url', 'value' => $source_url];
        }
        if (count($meta_query) === 1) {
            return 0;
        }

        $query = new WP_Query([
            'post_type' => $post_type,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => $meta_query,
        ]);

        return $query->posts ? (int) $query->posts[0] : 0;
    }

    private static function candidate_title(array $params): string
    {
        $merk = isset($params['merk']) ? sanitize_text_field($params['merk']) : '';
        $model = isset($params['model']) ? sanitize_text_field($params['model']) : '';
        $title = trim($merk . ' ' . $model);

        return $title ?: 'Nieuwe weerstation kandidaat';
    }

    private static function error_response(WP_Error $error): WP_REST_Response
    {
        return new WP_REST_Response(['message' => $error->get_error_message()], 400);
    }
}
