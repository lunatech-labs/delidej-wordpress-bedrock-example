<?php 
// Prevent direct output
if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="hashbar-sidebar-adds-area">
    <div class="htoption-rating-area hashbar-opt-sidebar-item">
        <div class="htoption-rating-icon">
            <img src="<?php echo esc_url(HASHBAR_WPNBP_URI . '/admin/settings-panel/assets/images/rating.png'); ?>" alt="<?php echo esc_attr__('Rating icon', 'hashbar'); ?>">
        </div>
        <div class="htoption-rating-intro">
        <h3 class="hashbar-rating-title"><?php esc_html_e( 'Have We Fully Met Your Expectations?', 'hashbar' ) ?></h3>
        <p class="hashbar-rating-desc">
            <?php echo esc_html__('Thank you for choosing our plugin! If it makes your work easier, please share your happiness with a 5-star rating on WordPress. It’ll take just 2 minutes & means a lot to us!','hashbar'); ?></p>
            <a href="https://wordpress.org/support/plugin/hashbar-wp-notification-bar/reviews/?filter=5#new-post" class="hashbar-admin-pro-rating-bution hashbar-doc-btn" target="_blank"><?php esc_html_e( 'Provide Your Feedback', 'hashbar' ) ?></a>
       </div>
    </div>

</div>