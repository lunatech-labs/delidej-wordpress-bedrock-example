<?php

// register TinyMCE buttons
add_filter( 'mce_buttons', 'hashbar_wpnbp_register_mce_buttons' );
function hashbar_wpnbp_register_mce_buttons( $buttons ) {
    $buttons[] = 'btn_trigger';
    return $buttons;
}

// add new buttons to tinymce
add_filter( 'mce_external_plugins', 'hashbar_wpnbp_register_mce_plugin' );
function hashbar_wpnbp_register_mce_plugin( $plugin_array ) {
   $plugin_array['btn_trigger'] = HASHBAR_WPNBP_URI.'/admin/js/shortcode.js';
   return $plugin_array;
}


add_shortcode( 'hashbar_btn', 'hashbar_wpnbp_btn_shortcode' );
function hashbar_wpnbp_btn_shortcode($atts){
    $atts = shortcode_atts( 
        array(
            'btn_text'  => 'DOWNLOAD NOW!',
            'btn_link'  => 'https://devitems.com/',
            'target'  => '',
            'add_nofollow'  => '',
            'btn_bg_color'  => '',
            'btn_text_color'  => '',
            'btn_rel'  => '',
            'btn_style'  => 'style_2',
        ),
        $atts,
        $shortcode = 'hashbar_btn'
    );

    extract($atts);

    $css = '';
    $css .= $btn_bg_color ? 'background-color:'.$btn_bg_color.';' : '';
    $css .= $btn_text_color ? 'color:'.$btn_text_color.';' : '';

    $nofollow = $add_nofollow == 'yes' ? 'rel="nofollow"' : '';

    return '<a class="ht_btn '.$btn_style.'" href="'.$btn_link.'" rel="'.$btn_rel.'" target="'.$target.'" '. $nofollow .'  style="'.$css.'">'.$btn_text.'</a>';
}

add_shortcode( 'hashbar_socials', 'hashbar_wpnbp_social_bookmarks' );
function hashbar_wpnbp_social_bookmarks($atts){
    $atts = shortcode_atts(
        array(
            'target'  => '_blank',
            'facebook'  => '',
            'twitter'  => '',
            'google_plus'  => '',
            'instagram'  => '',
            'pinterest'  => '',
            'youtube'  => '',
            'vimeo'  => '',
        ),
        $atts,
        'hashbar_socials'
    );

    extract($atts);

    ob_start();
    ?>
    <span class="ht-notification-social">
        <?php if($facebook): ?>
        <a href="<?php echo esc_url($facebook); ?>" target="<?php echo esc_attr( $target ); ?>"><i class="zmdi zmdi-facebook"></i></a>
        <?php endif; ?>

        <?php if($twitter): ?>
        <a href="<?php echo esc_url($twitter); ?>" target="<?php echo esc_attr( $target ); ?>"><i class="zmdi zmdi-twitter"></i></a>
        <?php endif; ?>

        <?php if($google_plus): ?>
        <a href="<?php echo esc_url($google_plus); ?>" target="<?php echo esc_attr( $target ); ?>"><i class="zmdi zmdi-google-plus"></i></a>
        <?php endif; ?>

        <?php if($instagram): ?>
        <a href="<?php echo esc_url($instagram); ?>" target="<?php echo esc_attr( $target ); ?>"><i class="zmdi zmdi-instagram"></i></a>
        <?php endif; ?>

        <?php if($pinterest): ?>
        <a href="<?php echo esc_url($pinterest); ?>" target="<?php echo esc_attr( $target ); ?>"><i class="zmdi zmdi-pinterest"></i></a>
        <?php endif; ?>

        <?php if($youtube): ?>
        <a href="<?php echo esc_url($youtube); ?>" target="<?php echo esc_attr( $target ); ?>"><i class="zmdi zmdi-youtube"></i></a>
        <?php endif; ?>

        <?php if($vimeo): ?>
        <a href="<?php echo esc_url($vimeo); ?>" target="<?php echo esc_attr( $target ); ?>"><i class="zmdi zmdi-vimeo"></i></a>
        <?php endif; ?>
    </span>
    <?php

    return ob_get_clean();
}



// youtube shortcode
add_shortcode( 'hashbar_fb_likebox', 'hashbar_wpnbp_facebook_likebox' );
function hashbar_wpnbp_facebook_likebox($atts){
    $atts = shortcode_atts(
        array(
            'page_name' => 'devitems',
            'width' => 300,
            'height' => '',
        ),
        $atts,
        'hashbar_fb_likebox'
    );

    extract($atts);

    ob_start();
    ?>
    <span class="ht-fb-likebox">
        <div class="fb-page" data-href="<?php echo esc_url( 'https://www.facebook.com/'.$page_name ); ?>" data-tabs="" data-width="<?php echo esc_attr( $width ); ?>" data-height="<?php echo esc_attr( $height ); ?>"></div>
    </span>
    <div id="fb-root"></div>
    <script>(function(d, s, id) {
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) return;
      js = d.createElement(s); js.id = id;
      js.src = 'https://connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v2.11';
      fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
    </script>
    <?php

    return ob_get_clean();
}

// Countdown Shortcode
add_shortcode( 'hashbar_countdown', 'hashbar_countdown_sc' );
if( !function_exists('hashbar_countdown_sc') ){
    function hashbar_countdown_sc($attr){

        $notification_id      = get_the_id();
        $countdown_opt_enable = get_post_meta($notification_id, '_wphash_count_down', true );
        $customize_time       = get_post_meta($notification_id, '_wphash_countdown_cudtomize_label', true);
        $countdown            = get_post_meta($notification_id, '_wphash_countdown_schedule_datetime', true);
        $timer_style          = get_post_meta($notification_id, '_wphash_countdown_timer_style', true);

        if(is_array($timer_style)){
            extract($timer_style);
        }else{
            $timer_style = array(); 
        }

        $default = array(
            'time_color'                    => get_post_meta($notification_id, '_wphash_cd_time_color', true),
            'countdown_hide_time'           => get_post_meta($notification_id, '_wphash_countdown_time', true)?get_post_meta($notification_id, '_wphash_countdown_time', true):array(),
            'countdown_date'                => date('m/d/Y H:i', strtotime($countdown)),

            'countdown_hide_label'          => get_post_meta($notification_id, '_wphash_countdown_time_label', true),

            'countdown_day_label'           => $customize_time ? get_post_meta($notification_id, '_wphash_notification_countdown_day_txt', true) : "Days",
            'countdown_hour_labl'           => $customize_time ? get_post_meta($notification_id, '_wphash_notification_countdown_hour_txt', true): "Hours",
            'countdown_mins_labl'           => $customize_time ? get_post_meta($notification_id, '_wphash_notification_countdown_mins_txt', true): "Minutes",
            'countdown_sec_label'           => $customize_time ? get_post_meta($notification_id, '_wphash_notification_countdown_sec_txt', true):  "Seconds",

            'before_txt'                    => "",
            'after_txt'                     => "",
            'position'                       => "normal",
            'countdown_style'               => get_post_meta($notification_id, '_wphash_countdown_style', true),
            'countdown_bg_color'            => array_key_exists('countdown_bg_color', $timer_style) ? $countdown_bg_color : '',
            'countdown_brdr_rdis'           => array_key_exists('countdown_brdr_rdus',$timer_style) ? $countdown_brdr_rdus : '',
            'countdown_timr_bg_clr'         => array_key_exists('countdown_timer_bg_color', $timer_style) ? $countdown_timer_bg_color : '',
            'countdown_timr_txt_clr'        => array_key_exists('countdown_timer_txt_color', $timer_style) ? $countdown_timer_txt_color : '',
            'countdown_timr_brdr_rdis'      => array_key_exists('countdown_timer_border_radius',$timer_style) ? $countdown_timer_border_radius : '',
            'countdown_timr_typography'     => array_key_exists('timr_number_typhography', $timer_style) ? $timr_number_typhography : '',
            'countdown_label_clr'           => array_key_exists('countdown_label_color',$timer_style) ? $countdown_label_color : '',
            'countdown_box_space'           => array_key_exists('countdown_box_spacing', $timer_style) ? $countdown_box_spacing : '',
            'countdown_box_height'          => array_key_exists('countdown_box_height', $timer_style) ? $countdown_box_height : '',
            'countdown_box_width'           => array_key_exists('countdown_box_width', $timer_style) ? $countdown_box_width : '',
            'countdown_box_border'          => array_key_exists('countdown_box_border', $timer_style) ? $countdown_box_border : '',
            'countdown_box_padding'         => array_key_exists('countdown_box_padding',$timer_style) ? $countdown_box_padding : '',
            'countdown_timr_box_padding'    => array_key_exists('countdown_timer_box_padding', $timer_style) ? $countdown_timer_box_padding : '',
            'countdown_timr_box_border'     => array_key_exists('countdown_tmr_box_border',$timer_style) ? $countdown_tmr_box_border : '',
            'countdown_timr_box_width'      => array_key_exists('countdown_timer_min_width',$timer_style) ? $countdown_timer_min_width : '',
            'countdown_position'            => get_post_meta($notification_id, '_wphash_countdown_position', true),
            'countdown_lbl_padig'           => array_key_exists('countdown_timer_label_padding', $timer_style) ? $countdown_timer_label_padding : '',
            'countdown_lbl_brdr'            => array_key_exists('countdown_tmr_label_border', $timer_style) ? $countdown_tmr_label_border : '',
            'countdown_lbl_bg'              => array_key_exists('countdown_label_bg_color',$timer_style) ? $countdown_label_bg_color : '',
            'countdown_lbl_brdr_rdis'       => array_key_exists('countdown_label_border_radius', $timer_style) ? $countdown_label_border_radius : '',
            'countdown_lbl_typography'      => array_key_exists('countdown_timr_label_typhography',$timer_style) ? $countdown_timr_label_typhography : '',
        );

        extract( shortcode_atts( $default, $attr ) );
        wp_enqueue_script('jquery-countdown');
        ob_start();
        ?>
           <div id="hthb-countdown-<?php echo esc_attr( $notification_id ); ?>" class="hthb-countdown-section">
                <?php if($before_txt): ?>
                    <div class="hthb-countdown-before"><?php echo $before_txt ?></div>
                <?php endif; ?>
                <div class="hthb-countdown-wrap <?php echo esc_attr( $countdown_style ); ?>" data-countdown="<?php echo esc_attr( $countdown_date ); ?>" data-custom_label='{"day":"<?php echo esc_attr( $countdown_day_label ); ?>", "hour":"<?php echo esc_attr( $countdown_hour_labl ); ?>", "min":"<?php echo esc_attr( $countdown_mins_labl ); ?>", "sec":"<?php echo esc_attr( $countdown_sec_label ); ?>"}'>
                    <?php if(in_array('hide_day', $countdown_hide_time) === false): ?>
                        <div class="hthb-single-countdown">
                            <span  class="hthb-single-countdown__time countdown-day"><?php echo esc_html__( '00' ); ?></span>
                            <?php if(!$countdown_hide_label): ?>
                                <span class="hthb-single-countdown__text countdown-day-text"><?php echo esc_html__( 'Days' ); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if(in_array('hide_hours', $countdown_hide_time) === false): ?>
                        <div class="hthb-single-countdown">
                            <span class="hthb-single-countdown__time countdown-hour"><?php echo esc_html__( '00' ); ?></span>
                            <?php if(!$countdown_hide_label): ?>
                                <span class="hthb-single-countdown__text countdown-hour-text"><?php echo esc_html__( 'Hours' ); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if(in_array('hide_mins', $countdown_hide_time) === false): ?>
                        <div class="hthb-single-countdown">
                            <span class="hthb-single-countdown__time countdown-minute"><?php echo esc_html__( '00' ); ?></span>
                            <?php if(!$countdown_hide_label): ?>
                                <span class="hthb-single-countdown__text countdown-minite-text"><?php echo esc_html__( 'Minutes' ); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if(in_array('hide_sec', $countdown_hide_time) === false): ?>
                        <div class="hthb-single-countdown">
                            <span class="hthb-single-countdown__time countdown-second"><?php echo esc_html__( '00' ); ?></span>
                            <?php if(!$countdown_hide_label): ?>
                                <span class="hthb-single-countdown__text countdown-second-text"><?php echo esc_html__( 'Seconds' ); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if($after_txt): ?>
                    <div class="hthb-countdown-after"><?php echo $after_txt ?></div>
                <?php endif; ?>
            </div>
            <style type="text/css">

                <?php echo hashbar_generate_css($countdown_bg_color,'#hthb-countdown-'.$notification_id.' .hthb-single-countdown','background-color'); ?>
                <?php
                    if($countdown_opt_enable == 'ntf_countdown_enable'):
                        echo hashbar_generate_css($countdown_brdr_rdis.'px','#hthb-countdown-'.$notification_id.' .hthb-single-countdown','border-radius'); 
                    endif;
                ?>
                <?php 
                    if($countdown_opt_enable == 'ntf_countdown_enable'):
                        echo hashbar_generate_css($countdown_box_space.'px','#hthb-countdown-'.$notification_id.' .hthb-single-countdown','margin-right'); 
                    endif;
                ?>
                <?php echo hashbar_generate_css($countdown_box_height.'px','#hthb-countdown-'.$notification_id.' .hthb-single-countdown','height'); ?>
                <?php echo hashbar_generate_css($countdown_box_width.'px','#hthb-countdown-'.$notification_id.' .hthb-single-countdown','width'); ?>
                <?php 
                    if($countdown_opt_enable == 'ntf_countdown_enable'):
                        echo hashbar_generate_css($countdown_box_padding,'#hthb-countdown-'.$notification_id.' .hthb-single-countdown','padding'); 
                    endif;
                ?>
                <?php
                    if($countdown_opt_enable == 'ntf_countdown_enable'):
                        echo hashbar_generate_css($countdown_box_border,'#hthb-countdown-'.$notification_id.' .hthb-single-countdown','border');
                    endif; 
                ?>
                <?php echo hashbar_generate_css($countdown_timr_bg_clr,'#hthb-countdown-'.$notification_id.' .hthb-single-countdown__time','background-color'); ?>
                <?php
                    if($countdown_opt_enable == 'ntf_countdown_enable'): 
                        echo hashbar_generate_css($countdown_timr_box_padding,'#hthb-countdown-'.$notification_id.' .hthb-single-countdown__time','padding'); 
                    endif;
                ?>
                <?php 
                    if($countdown_opt_enable == 'ntf_countdown_enable'):
                        echo hashbar_generate_css($countdown_timr_box_border,'#hthb-countdown-'.$notification_id.' .hthb-single-countdown__time','border'); 
                    endif;
                ?>
                <?php echo hashbar_generate_css($countdown_timr_txt_clr,'#hthb-countdown-'.$notification_id.' .hthb-single-countdown__time','color'); ?>
                <?php 
                    if($countdown_opt_enable == 'ntf_countdown_enable'):
                        echo hashbar_generate_css($countdown_timr_typography,'#hthb-countdown-'.$notification_id.' .hthb-single-countdown__time','typography'); 
                    endif;
                ?>
                <?php 
                    if($countdown_opt_enable == 'ntf_countdown_enable'):
                        echo hashbar_generate_css($countdown_timr_brdr_rdis.'px','#hthb-countdown-'.$notification_id.' .hthb-single-countdown__time','border-radius');
                    endif 
                ?>
                <?php echo hashbar_generate_css($countdown_timr_box_width.'px','#hthb-countdown-'.$notification_id.' .hthb-single-countdown__time','min-width'); ?>
                <?php echo hashbar_generate_css($countdown_label_clr,'#hthb-countdown-'.$notification_id.' .hthb-single-countdown__text','color'); ?>
                <?php 
                    if($countdown_opt_enable == 'ntf_countdown_enable'):
                        echo hashbar_generate_css($countdown_lbl_padig,'#hthb-countdown-'.$notification_id.' .hthb-single-countdown__text','padding'); 
                    endif;
                ?>
                <?php 
                    if($countdown_opt_enable == 'ntf_countdown_enable'):
                        echo hashbar_generate_css($countdown_lbl_brdr,'#hthb-countdown-'.$notification_id.' .hthb-single-countdown__text','border');
                    endif; 
                ?>
                <?php echo hashbar_generate_css($countdown_lbl_bg,'#hthb-countdown-'.$notification_id.' .hthb-single-countdown__text','background-color'); ?>
                <?php 
                    if($countdown_opt_enable == 'ntf_countdown_enable'):
                        echo hashbar_generate_css($countdown_lbl_brdr_rdis.'px','#hthb-countdown-'.$notification_id.' .hthb-single-countdown__text','border-radius'); 
                    endif;
                ?>
                <?php 
                    if($countdown_opt_enable == 'ntf_countdown_enable'):
                        echo hashbar_generate_css($countdown_lbl_typography,'#hthb-countdown-'.$notification_id.' .hthb-single-countdown__text','typography'); 
                    endif;
                ?>
                <?php 
                    if('row' == $countdown_position || 'row-reverse' == $countdown_position ){
                        if('row-reverse' == $countdown_position){
                            echo hashbar_generate_css($countdown_position,'#notification-'.$notification_id.' .hthb-notification-content.ht-notification-text','flex-direction');
                        }else{
                            echo hashbar_generate_css($countdown_position,'#notification-'.$notification_id.' .hthb-notification-content.ht-notification-text','flex-direction');
                        }
                    }elseif('center' == $countdown_position){
                        echo hashbar_generate_css($countdown_position,'#notification-'.$notification_id.' .hthb-notification-content.ht-notification-text','justify-content');
                    }elseif('shortcode' == $countdown_position){
                        echo hashbar_generate_css($position,'#notification-'.$notification_id.' .hthb-notification-content.ht-notification-text','justify-content');
                    }
                ?>

            </style>
        <?php
        return ob_get_clean();
    }
}
// Control core classes for avoid errors
