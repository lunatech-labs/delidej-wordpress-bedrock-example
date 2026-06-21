<?php
function hashbar_wpnbp_allporduct_by_cat( $terms = array(), $field = 'slug' ){
    $get_products = get_posts( array(
      'post_type'   => 'product',
      'numberposts' => -1,
      'post_status' => 'publish',
      'tax_query'   => array(
        array(
            'taxonomy' => 'product_cat',
            'field'    => $field,
            'terms'    => $terms,
            'operator' => 'IN',
        )
      ),
    ) );

    $all_product_list = array();
    foreach ($get_products as $porduct) {
        array_push($all_product_list,$porduct->ID);
    }

    return $all_product_list;
}

function hashbar_wpnbp_porduct_by_cat($cat_name){
  $get_products = get_posts( array(
    'post_type'   => 'product',
    'numberposts' => -1,
    'post_status' => 'publish',
    'tax_query'   => array(
      array(
          'taxonomy' => 'product_cat',
          'field'    => 'slug',
          'terms'    => $cat_name,
          'operator' => 'IN',
      )
    ),
  ) );

  $category_product_list = array();
  foreach ($get_products as $porduct) {
      $category_product_list[$porduct->ID] = $porduct->post_title;
  }
  return $category_product_list;
}

function hashbar_wpnbp_check_pro_post(){
  $get_custom_post_type = isset($_GET['post']) ? get_post($_GET['post'])->post_type : '';

  if((isset($_GET['post_type']) && $_GET['post_type'] == 'wphash_ntf_bar') || ( $get_custom_post_type && $get_custom_post_type == 'wphash_ntf_bar' )){
    return true;
  }
  return false;
}

if ( !function_exists( 'hashbar_render_html_attr' ) ){
  function hashbar_render_html_attr($attr_name, $var){
    if( $var ){
      printf( '%s="%s"', $attr_name, $var);
    }
  }
}

if ( !function_exists( 'hashbar_do_shortcode' ) ){
  function hashbar_do_shortcode( $tag, array $atts = array(), $content = null ) {
    global $shortcode_tags;

    if ( ! isset( $shortcode_tags[ $tag ] ) ) {
      return false;
    }

    return call_user_func( $shortcode_tags[ $tag ], $atts, $content, $tag );
  }
}
if ( !function_exists( 'hashbar_generate_css' ) ){
  function hashbar_generate_css($value, $selector, $css_attr, $important=''){
    if(!empty( $value ) && 'NaN' !== $value && 'px' !== $value ){
      if(is_array($value)){

        if('border' == $css_attr && 'none' !== $value['style'] && $value['color']){
          return "{$selector}{{$css_attr}:{$value['all']}px {$value['style']} {$value['color']} {$important};}";
        }

        if('padding' == $css_attr){
          if( !empty($value['width']) || !empty($value['height']) ){
            $top_bottom = empty($value['width']) ? '0' : $value['width'];
            $left_right = empty($value['height']) ? '0' : $value['height'];
            return "{$selector}{{$css_attr}:{$top_bottom}px {$left_right}px {$important};}";
          }
        }

        if('typography' == $css_attr){
          $typography = '';
          foreach ($value as $key => $typo_item) {

            if( 'type' == $key || 'unit' == $key || empty($typo_item)) continue;

            if('font-size' != $key && 'line-height' != $key && 'letter-spacing' != $key){
              $typography .= "{$key}:{$typo_item};";
            }else{
              $typography .= "{$key}:{$typo_item}px;";
            }

          }
          return "{$selector}{".$typography."}";
        }

        return;
      }
      return "{$selector}{{$css_attr}:{$value} {$important};}";
    }
  }
}

function hashbar_wpnbp_is_classic_editor_plugin_active() {
  if ( ! function_exists( 'is_plugin_active' ) ) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
  }

  if ( is_plugin_active( 'classic-editor/classic-editor.php' ) ) {
    return true;
  }

  return false;
}

function hashbar_wpnbp_check_post(){
  $get_custom_post_type = isset($_GET['post']) ? get_post($_GET['post'])->post_type : '';

  if((isset($_GET['post_type']) && $_GET['post_type'] == 'wphash_ntf_bar') || ($get_custom_post_type !== '' && $get_custom_post_type == 'wphash_ntf_bar')){

    return true;
  }
  return false;
}

/**
 * Get Post List
 * return array
 * @param string $post_type
 * @return array
 * since 1.4.8
 */
/**
 * Get list of countries for geo-targeting
 *
 * @param bool $include_all Whether to include "All Countries" option
 * @return array Country code => Country name
 * @since 2.0.0
 */
if ( ! function_exists( 'hashbar_get_countries_list' ) ) {
  function hashbar_get_countries_list( $include_all = false ) {
    $countries = array(
      'US' => esc_html__( 'United States', 'hashbar' ),
      'GB' => esc_html__( 'United Kingdom', 'hashbar' ),
      'CA' => esc_html__( 'Canada', 'hashbar' ),
      'AU' => esc_html__( 'Australia', 'hashbar' ),
      'DE' => esc_html__( 'Germany', 'hashbar' ),
      'FR' => esc_html__( 'France', 'hashbar' ),
      'ES' => esc_html__( 'Spain', 'hashbar' ),
      'IT' => esc_html__( 'Italy', 'hashbar' ),
      'NL' => esc_html__( 'Netherlands', 'hashbar' ),
      'BE' => esc_html__( 'Belgium', 'hashbar' ),
      'CH' => esc_html__( 'Switzerland', 'hashbar' ),
      'AT' => esc_html__( 'Austria', 'hashbar' ),
      'SE' => esc_html__( 'Sweden', 'hashbar' ),
      'NO' => esc_html__( 'Norway', 'hashbar' ),
      'DK' => esc_html__( 'Denmark', 'hashbar' ),
      'FI' => esc_html__( 'Finland', 'hashbar' ),
      'PL' => esc_html__( 'Poland', 'hashbar' ),
      'CZ' => esc_html__( 'Czech Republic', 'hashbar' ),
      'IE' => esc_html__( 'Ireland', 'hashbar' ),
      'JP' => esc_html__( 'Japan', 'hashbar' ),
      'CN' => esc_html__( 'China', 'hashbar' ),
      'IN' => esc_html__( 'India', 'hashbar' ),
      'BR' => esc_html__( 'Brazil', 'hashbar' ),
      'MX' => esc_html__( 'Mexico', 'hashbar' ),
      'KR' => esc_html__( 'South Korea', 'hashbar' ),
      'SG' => esc_html__( 'Singapore', 'hashbar' ),
      'HK' => esc_html__( 'Hong Kong', 'hashbar' ),
      'TH' => esc_html__( 'Thailand', 'hashbar' ),
      'MY' => esc_html__( 'Malaysia', 'hashbar' ),
      'ID' => esc_html__( 'Indonesia', 'hashbar' ),
      'PH' => esc_html__( 'Philippines', 'hashbar' ),
      'VN' => esc_html__( 'Vietnam', 'hashbar' ),
      'NZ' => esc_html__( 'New Zealand', 'hashbar' ),
      'ZA' => esc_html__( 'South Africa', 'hashbar' ),
      'RU' => esc_html__( 'Russia', 'hashbar' ),
      'UA' => esc_html__( 'Ukraine', 'hashbar' ),
      'GR' => esc_html__( 'Greece', 'hashbar' ),
      'PT' => esc_html__( 'Portugal', 'hashbar' ),
      'AR' => esc_html__( 'Argentina', 'hashbar' ),
      'CL' => esc_html__( 'Chile', 'hashbar' ),
      'CO' => esc_html__( 'Colombia', 'hashbar' ),
      'PE' => esc_html__( 'Peru', 'hashbar' ),
      'TR' => esc_html__( 'Turkey', 'hashbar' ),
      'AE' => esc_html__( 'United Arab Emirates', 'hashbar' ),
      'SA' => esc_html__( 'Saudi Arabia', 'hashbar' ),
      'IL' => esc_html__( 'Israel', 'hashbar' ),
      'BD' => esc_html__( 'Bangladesh', 'hashbar' ),
      'PK' => esc_html__( 'Pakistan', 'hashbar' ),
      'LK' => esc_html__( 'Sri Lanka', 'hashbar' ),
    );

    if ( $include_all ) {
      $countries = array_merge( array( 'all' => esc_html__( 'All Countries', 'hashbar' ) ), $countries );
    }

    return $countries;
  }
}

/**
 * Get list of PRO countries (all countries are PRO in free version)
 *
 * @return array Country codes
 * @since 2.0.0
 */
if ( ! function_exists( 'hashbar_get_pro_countries' ) ) {
  function hashbar_get_pro_countries() {
    return array_keys( hashbar_get_countries_list( false ) );
  }
}

if ( !function_exists( 'hashbar_post_list' ) ){
  function hashbar_post_list( $post_type = 'post', $limit = 20 ){
    static $cache = array();
    $cache_key = $post_type . '_' . $limit;
    if ( isset( $cache[$cache_key] ) ) {
        return $cache[$cache_key];
    }

    $options = array();
    if ( 'product_cat' == $post_type ){

      $categories = get_terms( array(
          'taxonomy' => 'product_cat',
          'hide_empty' => false,
      ) );
      if ( ! empty( $categories ) && ! is_wp_error( $categories ) ){
          foreach ( $categories as $term ) {
              $options[ $term->term_id ] = $term->name;
          }
          $cache[$cache_key] = $options;
          return $options;
      }
    }

    if ( 'post_cat' == $post_type ){
      $categories = get_terms( array(
          'taxonomy' => 'category',
          'hide_empty' => false,
      ) );
      if ( ! empty( $categories ) && ! is_wp_error( $categories ) ){
          foreach ( $categories as $term ) {
              $options[ $term->term_id ] = $term->name;
          }
          $cache[$cache_key] = $options;
          return $options;
      }
    }

    if ( 'post_tags' == $post_type ){
      $tags = get_terms( array(
          'taxonomy' => 'post_tag',
          'hide_empty' => false,
      ) );
      if ( ! empty( $tags ) && ! is_wp_error( $tags ) ){
          foreach ( $tags as $term ) {
              $options[ $term->term_id ] = $term->name;
          }
          $cache[$cache_key] = $options;
          return $options;
      }
    }

    $all_post = array( 'posts_per_page' => $limit, 'post_type'=> $post_type, 'post_status' => 'publish' );
    $post_terms = get_posts( $all_post );
    if ( ! empty( $post_terms ) && ! is_wp_error( $post_terms ) ){
        foreach ( $post_terms as $term ) {
            $options[ $term->ID ] = $term->post_title;
        }
        $cache[$cache_key] = $options;
        return $options;
    }
  }
}