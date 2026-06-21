<?php
add_action( 'admin_menu', 'hashbar_wpnbp_add_admin_menu' );
add_action( 'admin_init', 'hashbar_wpnbp_settings_init' );



function hashbar_wpnbp_add_admin_menu(  ) { 

	add_submenu_page( 'edit.php?post_type=wphash_ntf_bar', 'Settings', 'Settings', 'manage_options', 'hashbar_options_page', 'hashbar_wpnbp_options_page' );
	if(hashbar_get_opt('enable_analytics')){
		add_submenu_page( 'edit.php?post_type=wphash_ntf_bar', 'HashBar Analytics', 'Analytics', 'manage_options', 'hashbar_analytics_page', 'hashbar_wpnbp_analytics_page' );
	}

}


function hashbar_wpnbp_settings_init(  ) { 

	register_setting( 'options_group_1', 'hashbar_wpnbp_opt' );

	add_settings_section(
		'hashbar_wpnbp_options_group_1_section', 
		'', 
		null, 
		'options_group_1'
	);

	add_settings_field( 
		'dont_show_bar_after_close', 
		__( 'Don\'t Show Notification After Close', 'hashbar' ), 
		'hashbar_wpnbp_checkbox_render', 
		'options_group_1', 
		'hashbar_wpnbp_options_group_1_section' 
	);

	add_settings_field( 
		'cn_cookies_expire_time', 
		__( 'Closed Notification Cookies Expire Time.', 'hashbar' ), 
		'hashbar_cn_cookies_expire_time_render', 
		'options_group_1', 
		'hashbar_wpnbp_options_group_1_section' 
	);

	add_settings_field( 
		'keep_closed_bar', 
		__( 'Keep The Notification Bar Minimize', 'hashbar' ), 
		'hashbar_wpnbp_bar_closed_checkbox_render', 
		'options_group_1', 
		'hashbar_wpnbp_options_group_1_section' 
	);

	add_settings_field( 
		'enable_analytics', 
		__( 'Enable Analytics', 'hashbar' ), 
		'hashbar_analytics_checkbox_render', 
		'options_group_1', 
		'hashbar_wpnbp_options_group_1_section' 
	);

	add_settings_field( 
		'count_onece_byip', 
		__( 'Count only 1 from each IP', 'hashbar' ), 
		'count_onece_byip_checkbox_render', 
		'options_group_1', 
		'hashbar_wpnbp_options_group_1_section' 
	);

	add_settings_field( 
		'analytics_from', 
		__( 'Analytics From', 'hashbar' ), 
		'analytics_from_options_render', 
		'options_group_1', 
		'hashbar_wpnbp_options_group_1_section' 
	);

	add_settings_field( 
		'mobile_device_breakpoint', 
		__( 'Mobile device breakpoint (px)', 'hashbar' ), 
		'hashbar_wpnbp_text_render', 
		'options_group_1', 
		'hashbar_wpnbp_options_group_1_section' 
	);

	// posts_limit
	add_settings_field( 
		'posts_limit', 
		__( 'Limit "Posts" List', 'hashbar' ), 
		'hashbar_wpnbp_posts_limit_render', 
		'options_group_1', 
		'hashbar_wpnbp_options_group_1_section' 
	);

	// pages_limit
	add_settings_field( 
		'pages_limit', 
		__( 'Limit "Pages" List', 'hashbar' ), 
		'hashbar_wpnbp_pages_limit_render', 
		'options_group_1', 
		'hashbar_wpnbp_options_group_1_section' 
	);

	if(is_plugin_active( 'woocommerce/woocommerce.php' )){
		// product_limit
		add_settings_field( 
			'product_limit', 
			__( 'Limit "Products" List', 'hashbar' ), 
			'hashbar_wpnbp_product_limit_render', 
			'options_group_1', 
			'hashbar_wpnbp_options_group_1_section' 
		);
	}

	// dismiss the admin notice for user
	$user_id = get_current_user_id();
    if ( isset( $_GET['hthbp-notice-dismissed'] ) ){
        add_user_meta( $user_id, 'hthbp_notice_dismissed', 'true', true );
    }
    if ( isset( $_GET['hthbp-lnotice-dismissed'] ) ){
        add_user_meta( $user_id, 'hthbp-lnotice-dismissed', 'true', true );
    }

}


function hashbar_wpnbp_checkbox_render(  ) { 

	$options = get_option( 'hashbar_wpnbp_opt' );
	$checkbox_val = isset($options['dont_show_bar_after_close']) ? $options['dont_show_bar_after_close'] : '';
	?>
	<input type='checkbox' name='hashbar_wpnbp_opt[dont_show_bar_after_close]'  <?php checked($checkbox_val, 1) ?> value='1'>
	<p class="description">If check this option. The notification will not appear again on a page, after closing the notification.</p>
	<?php

}

function hashbar_cn_cookies_expire_time_render(  ) {

	$options = get_option( 'hashbar_wpnbp_opt' );
	$cookies_expire_time = isset($options['cn_cookies_expire_time']) ? $options['cn_cookies_expire_time'] : 7;
	$cookies_expire_type = isset($options['cn_cookies_expire_type']) ? $options['cn_cookies_expire_type'] : 'days';
	?>
	<div style="display:flex; gap: 5px;">
		<input type='number' name='hashbar_wpnbp_opt[cn_cookies_expire_time]' value="<?php echo esc_attr($cookies_expire_time); ?>" min="1" style="width: 87px;">
		<select name="hashbar_wpnbp_opt[cn_cookies_expire_type]">
			<option value="days" <?php echo $cookies_expire_type == 'days' ? 'selected' : ''; ?>>Days</option>
			<option value="hours" <?php echo $cookies_expire_type == 'hours' ? 'selected' : ''; ?>>Hours</option>
			<option value="minutes" <?php echo $cookies_expire_type == 'minutes' ? 'selected' : ''; ?>>Minutes</option>
		</select>
	</div>
	<p class="description">Specify the duration of the expiration time for the cookie when a user closes the notification bar. <br> After the expiration time has passed, the notification will reappear for that user. (Default: 7 Days).</p>
	<?php

}

function hashbar_wpnbp_bar_closed_checkbox_render(){
	$options = get_option( 'hashbar_wpnbp_opt' );
	$checkbox_val = isset($options['keep_closed_bar']) ? $options['keep_closed_bar'] : '';
	?>
	<input type='checkbox' name='hashbar_wpnbp_opt[keep_closed_bar]'  <?php checked($checkbox_val, 1) ?> value='1'>
	<p class="description">Once you close the notification bar, it will remain minimized on all pages of your site. <br> This option will be effective for the notifications which have set "Show open button = Yes" from the notification metabox options <br> and after <b>closed notification cookies expire time</b>.</p>
	<?php
}

function hashbar_analytics_checkbox_render(){
	$options = get_option( 'hashbar_wpnbp_opt' );
	$checkbox_val = isset($options['enable_analytics']) ? $options['enable_analytics'] : '';
	?>
	<input type='checkbox' name='hashbar_wpnbp_opt[enable_analytics]'  <?php checked($checkbox_val, 1) ?> value='1'>
	<p class="description">Enable Analytics to get the analytical report about your notifications.</p>
	<?php
}

function count_onece_byip_checkbox_render(){
	$options = get_option( 'hashbar_wpnbp_opt' );
	$checkbox_val = isset($options['count_onece_byip']) ? $options['count_onece_byip'] : '';
	?>
	<input type='checkbox' name='hashbar_wpnbp_opt[count_onece_byip]'  <?php checked($checkbox_val, 1) ?> value='1'>
	<p class="description">Enable to count the views and clicks only once from each IP-address.</p>
	<?php
}

function analytics_from_options_render(){
	$options = get_option( 'hashbar_wpnbp_opt' );
	$checkbox_val = isset($options['analytics_from']) ? $options['analytics_from'] : '';
	?>
	<select name="hashbar_wpnbp_opt[analytics_from]">
	  <option value="everyone" <?php echo $checkbox_val == 'everyone' ? 'selected' : ''; ?>>Everyone</option>
	  <option value="guests" <?php echo $checkbox_val == 'guests' ? 'selected' : ''; ?>>Guests Only</option>
	  <option value="registered_users" <?php echo $checkbox_val == 'registered_users' ? 'selected' : ''; ?>>Rigestered Users Only</option>
	</select>
	<?php
}

function hashbar_wpnbp_text_render(  ) { 

	$options = get_option( 'hashbar_wpnbp_opt' );
	$text_val = isset($options['mobile_device_breakpoint']) ? $options['mobile_device_breakpoint'] : '';
	?>
	<input type='text' name='hashbar_wpnbp_opt[mobile_device_breakpoint]' value="<?php echo esc_attr($text_val); ?>">
	<p class="description">Sets the breakpoint between mobile and desktop devices. Below this breakpoint mobile layout will appear (Default: 767).</p>
	<?php

}

function hashbar_wpnbp_posts_limit_render(  ) { 

	$options = get_option( 'hashbar_wpnbp_opt' );
	$text_val = isset($options['posts_limit']) ? $options['posts_limit'] : '';
	?>
	<input type='number' name='hashbar_wpnbp_opt[posts_limit]' value="<?php echo esc_attr($text_val); ?>" min="-1">
	<p class="description"><?php echo esc_html__('Leave it empty for default. Default = 150', 'hashbar'); ?> <br> <?php echo esc_html__('Use -1 to load all posts into the dropdown options.', 'hashbar'); ?></p>
	<?php

}

function hashbar_wpnbp_pages_limit_render(  ) { 

	$options = get_option( 'hashbar_wpnbp_opt' );
	$text_val = isset($options['pages_limit']) ? $options['pages_limit'] : '';
	?>
	<input type='number' name='hashbar_wpnbp_opt[pages_limit]' value="<?php echo esc_attr($text_val); ?>" min="-1">
	<p class="description"><?php echo esc_html__('Leave it empty for default. Default = 150', 'hashbar'); ?> <br> <?php echo esc_html__('Use -1 to load all pages into the dropdown options.', 'hashbar'); ?></p>
	<?php

}

function hashbar_wpnbp_product_limit_render(  ) { 

	$options = get_option( 'hashbar_wpnbp_opt' );
	$text_val = isset($options['product_limit']) ? $options['product_limit'] : '';
	?>
	<input type='number' name='hashbar_wpnbp_opt[product_limit]' value="<?php echo esc_attr($text_val); ?>" min="-1">
	<p class="description"><?php echo esc_html__('Leave it empty for default. Default = 150', 'hashbar'); ?> <br> <?php echo esc_html__('Use -1 to load all products into the dropdown options.', 'hashbar'); ?></p>
	<?php

}


function hashbar_wpnbp_options_page(  ) { 

	?>
	<div class="wrap">
		<?php do_action('hashbar_pro_admin_notices') ?>
		<form action='options.php' method='post'>

			<h2><?php echo esc_html__( 'HashBar Pro Global Options', 'hashbar' ) ?></h2>

			<?php
			settings_fields( 'options_group_1' );
			do_settings_sections( 'options_group_1' );
			submit_button();
			?>

		</form>
	</div>
	<?php

}

function hashbar_wpnbp_analytics_page(){
	$total_traking 		= false != get_transient( 'total_ht_traking_count' ) ? get_transient( 'total_ht_traking_count' ) : array();
	$postwise_traking   = false != get_transient( 'postwise_ht_traking_count' ) ? get_transient( 'postwise_ht_traking_count' ) : array(); 
	$country_traking    = false != get_transient( 'countrywise_ht_traking_count' ) ? get_transient( 'countrywise_ht_traking_count' ) : array();

	$trk_lenght   = count($total_traking);
	$total_clicks = $trk_lenght > 0 ? $total_traking[0]['totalclicks'] : 0;
	$total_views  = $trk_lenght > 0 ? $total_traking[0]['totalviews'] : 0;
	$total_clthrt = $trk_lenght > 0 ? round(($total_traking[0]['totalclicks']/$total_traking[0]['totalviews'])*100, 2) : 0;

	?>
	<div class="hthb--site-wrapper-reveal">
	    <div class="hthb--container">
	    	<div class="hthb-analytics-title">
    			<h2><?php echo esc_html__( 'Analytics Overview','hashbar'); ?></h2>
    		</div>
	        <div class="hthb--row">
	            <div class="hthb--col-lg-4 hthb--col-md-4 hthb--col-sm-6 ">
	                <div class="hthb-card__box">
	                    <div class="hthb-card__icon">
	                        <img src="<?php echo HASHBAR_WPNBP_URI; ?>/admin/img/click-icons.png" alt="">
	                    </div>
	                    <div class="hthb-card__content">
	                        <h6 class="hthb-card__title"><?php echo esc_html__( 'Total Clicks','hashbar') ?></h6>
	                        <h4 class="hthb-card__nubmer"><?php echo $total_clicks; ?></h4>
	                    </div>
	                    <div class="hthb-card__inner-image">
	                        <img src="<?php echo HASHBAR_WPNBP_URI; ?>/admin/img/views-icons-bg.png" alt="">
	                    </div>
	                </div>
	            </div>
	            <div class="hthb--col-lg-4 hthb--col-md-4 hthb--col-sm-6 ">
	                <div class="hthb-card__box hthb-card__box--two">
	                    <div class="hthb-card__icon hthb-card__icon--two">
	                        <img src="<?php echo HASHBAR_WPNBP_URI; ?>/admin/img/views-icons.png" alt="">
	                    </div>
	                    <div class="hthb-card__content">
	                        <h6 class="hthb-card__title"><?php echo esc_html__( 'Total Views','hashbar') ?></h6>
	                        <h4 class="hthb-card__nubmer hthb-card__nubmer--two"><?php echo $total_views; ?></h4>
	                    </div>
	                    <div class="hthb-card__inner-image">
	                        <img src="<?php echo HASHBAR_WPNBP_URI; ?>/admin/img/click-icons-bg.png" alt="">
	                    </div>
	                </div>
	            </div>
	            <div class="hthb--col-lg-4 hthb--col-md-4 hthb--col-sm-6 ">
	                <div class="hthb-card__box hthb-card__box--three">
	                    <div class="hthb-card__icon hthb-card__icon--three">
	                        <img src="<?php echo HASHBAR_WPNBP_URI; ?>/admin/img/rate-icons.png" alt="">
	                    </div>
	                    <div class="hthb-card__content">
	                        <h6 class="hthb-card__title"><?php echo esc_html__( 'Click Through Rate','hashbar') ?></h6>
	                        <h4 class="hthb-card__nubmer hthb-card__nubmer--three"><?php echo $total_clthrt; ?>%</h4>
	                    </div>
	                    <div class="hthb-card__inner-image">
	                        <img src="<?php echo HASHBAR_WPNBP_URI; ?>/admin/img/rate-icons-bg.png" alt="">
	                    </div>
	                </div>
	            </div>
	        </div>
	    </div>
	</div>

	<div class="hthb-traking-by-notification-area">
	    <div class="hthb--container">
	        <div class="hthb--row">
	            <div class="hthb--col-lg-8">
	                <div class="hthb-traking">
	                    <h6 class="hthb-traking__heading"><?php echo esc_html__( 'Traking By Notification Bars','hashbar') ?></h6>

	                    <div class="hthb-traking__wrap">
	                        <div class="hthb-traking__header">
	                            <div class="hthb-traking__header-item hthb-traking__header-item--name">
	                                <?php echo esc_html__( 'Name','hashbar'); ?>
	                            </div>
	                            <div class="hthb-traking__header-item hthb-traking__header-item--views">
	                                <?php echo esc_html__( 'Total Views','hashbar'); ?>
	                            </div>
	                            <div class="hthb-traking__header-item hthb-traking__header-item--clicks">
	                                <?php echo esc_html__( 'Total Clicks','hashbar'); ?>
	                            </div>
	                            <div class="hthb-traking__header-item hthb-traking__header-item--through-rate">
	                                <?php echo esc_html__( 'Through Rate','hashbar'); ?>
	                            </div>
	                        </div>
	                        <div class="hthb-traking__body">
	                        	<?php foreach ($postwise_traking as $postwise_count):?>
	                        		<?php if('publish' == get_post_status($postwise_count['post_id'])): ?>
			                            <div class="hthb-traking__items">
			                                <div class="hthb-traking__item hthb-traking__item--title">
			                                    <?php echo get_the_title($postwise_count['post_id']); ?>
			                                </div>
			                                <div class="hthb-traking__item hthb-traking__item--total-views-number">
			                                    <?php echo $postwise_count['totalviews']; ?>
			                                </div>
			                                <div class="hthb-traking__item hthb-traking__item--total-clicks-number">
			                                    <?php echo $postwise_count['totalclicks']; ?>
			                                </div>
			                                <div class="hthb-traking__item hthb-traking__item--through-rate-numbmer">
			                                    <?php echo round(($postwise_count['totalclicks']/$postwise_count['totalviews'])*100, 2); ?> %
			                                </div>
			                            </div>
			                        <?php endif; ?>
		                        <?php endforeach; ?>
	                        </div>
	                    </div>
	                </div>
	            </div>
	            <div class="hthb--col-lg-4 ">
	                <div class="hthb-top-countries">
	                    <h6 class="hthb-top-countries__heading"><?php echo esc_html__( 'Top 10 Countries','hashbar') ?></h6>
	                    <?php foreach ($country_traking as $countrywise_count):?>
		                    <div class="hthb-top-countries__list-wrap">
		                        <div class="hthb-top-countries__item">
		                            <div class="hthb-top-countries__name"><?php echo $countrywise_count['country']; ?></div>
		                        </div>
		                    </div>
		                <?php endforeach; ?>
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
	<?php
}

?>