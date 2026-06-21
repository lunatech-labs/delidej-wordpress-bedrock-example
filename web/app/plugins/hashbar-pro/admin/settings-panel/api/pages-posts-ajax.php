<?php
namespace Hashbar\Pro\API;

use WP_Query;
use Exception;

/**
 * AJAX handler for fetching pages and posts
 */
class PagesPostsAJAX {

    /**
     * Handler for gathering pages and posts
     * Supports optional 'search' and 'post_type' parameters for server-side search
     */
    public static function get_pages_posts() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            wp_die();
        }

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';

        // Read saved limits from settings
        $hashbar_options = get_option('hashbar_wpnb_opt', array());
        $limits = array(
            'post'    => isset($hashbar_options['posts_limit']) && $hashbar_options['posts_limit'] !== '' ? intval($hashbar_options['posts_limit']) : 20,
            'page'    => isset($hashbar_options['pages_limit']) && $hashbar_options['pages_limit'] !== '' ? intval($hashbar_options['pages_limit']) : 20,
            'product' => isset($hashbar_options['product_limit']) && $hashbar_options['product_limit'] !== '' ? intval($hashbar_options['product_limit']) : 20,
        );

        // When searching, use a higher limit to find matches beyond the initial load
        $is_searching = !empty($search);

        // Determine which post types to query
        $allowed_types = ['post', 'page', 'product'];
        if ($post_type && in_array($post_type, $allowed_types)) {
            $types_to_query = [$post_type];
        } else {
            $types_to_query = ['page', 'post'];
        }

        try {
            $items = [];
            $show_type_prefix = count($types_to_query) > 1;

            foreach ($types_to_query as $type) {
                // Use saved limit for initial load, 100 for search
                $per_page = $is_searching ? 100 : $limits[$type];

                $args = [
                    'post_type'      => $type,
                    'posts_per_page' => $per_page,
                    'post_status'    => 'publish',
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                ];

                if ($search) {
                    $args['s'] = $search;
                }

                $query = new WP_Query($args);

                if ($query->have_posts()) {
                    foreach ($query->posts as $post) {
                        $label = $show_type_prefix
                            ? '[' . ucfirst($type) . '] ' . sanitize_text_field($post->post_title)
                            : sanitize_text_field($post->post_title);

                        $items[] = [
                            'value' => (string)$post->ID,
                            'label' => $label,
                        ];
                    }
                }
            }

            // Sort alphabetically
            usort($items, function($a, $b) {
                return strcmp($a['label'], $b['label']);
            });

            // Return response
            echo json_encode([
                'success' => true,
                'data'    => $items,
                'total'   => count($items),
            ]);
            wp_die();

        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
            wp_die();
        }
    }
}

// Register AJAX handlers
add_action('wp_ajax_hashbar_get_pages_posts', [ PagesPostsAJAX::class, 'get_pages_posts' ], 10);
add_action('wp_ajax_nopriv_hashbar_get_pages_posts', [ PagesPostsAJAX::class, 'get_pages_posts' ], 10);
