<?php

/*
 * Function to update countdown time if recurring countdown is enabled and countdown is finished.
 */
add_action('init', 'hashbar_recurring_countdown_callback'); 
function hashbar_recurring_countdown_callback() {
    $args = [
        'post_type' => 'wphash_ntf_bar',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ];
    $notifications = get_posts( $args );
    foreach ($notifications as $id) {
        $recurring_countdown = get_post_meta( $id, '_wphash_recurring_countdown', true);
        if(isset($recurring_countdown) && $recurring_countdown === '1') {
            $recurring_time = (int) get_post_meta( $id, '_wphash_recurring_countdown_time', true);
            $schedule_time = strtotime(get_post_meta( $id, '_wphash_countdown_schedule_datetime', true));
            $current_time = current_time('timestamp');
            if($current_time >= $schedule_time) {
                update_post_meta( $id, '_wphash_countdown_schedule_datetime', date('m/d/Y h:i a', $current_time + $recurring_time));
            }
        }
    }
};
/*
 * Function to add a post meta if recurring countdown is enabled.
 */
add_action( 'save_post', 'hashbar_save_post_callback', 10, 3 );
function hashbar_save_post_callback( $post_id, $post, $update ) {
    if ( $post->post_type !==  'wphash_ntf_bar' ) return;
    if(isset($_POST['_wphash_']) && isset($_POST['_wphash_']['_wphash_recurring_countdown']) && $_POST['_wphash_']['_wphash_recurring_countdown'] == '1') {
        $schedule_time = sanitize_text_field($_POST['_wphash_']['_wphash_countdown_schedule_datetime']);
        $current_time = current_time('timestamp');
        $time_difference = strtotime($schedule_time) - $current_time;
        update_post_meta($post_id, '_wphash_recurring_countdown_time', $time_difference);
    }
}