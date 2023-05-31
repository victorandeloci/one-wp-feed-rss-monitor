<?php

/*
  * Plugin Name: One WP Feed RSS Monitor
  * Description: Monitor and auto-publish podcast episodes as wordpress posts
  * Version: 1.0.0
  * Author: Victor Andeloci
  * Author URI: https://github.com/victorandeloci
*/

if ( !function_exists('one_wp_feed_rss_monitor_page') ) {
  function one_wp_feed_rss_monitor_page() {
    // user permissions
    if (!current_user_can('manage_options')) {
      return;
    }

    wp_add_inline_script(
      'map-scripts',
      'const ajax_info = ' . json_encode(array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('one_wp_feed_rss_monitor_nonce_handler')
       )),
      'before'
    );

    wp_enqueue_script(
      'one_wp_feed_rss_monitor_main_js',
      plugin_dir_url(__FILE__) . 'js/main.js',
      [],
      '1.0.0',
      true
    );

    $options = [
      'feed_url' => get_option('one_wp_feed_rss_monitor_feed_url', ''),
      'default_category_id' => get_option('one_wp_feed_rss_monitor_default_cat', ''),
      'ids_to_terms' => get_option('one_wp_feed_rss_monitor_ids_to_terms', ''),
    ];

    $categories = get_categories();

    // form render
    include_once('templates/settings_form.php');
  }
}

function one_wp_feed_rss_monitor_save() {
  try {
    update_option('one_wp_feed_rss_monitor_feed_url', sanitize_text_field($_POST['feed_url']));
    update_option('one_wp_feed_rss_monitor_default_cat', sanitize_text_field($_POST['default_category_id']));
    update_option('one_wp_feed_rss_monitor_ids_to_terms', stripslashes($_POST['ids_to_terms']));

    echo 'Saved!';
  } catch (\Throwable $th) {
    echo 'Error during save... ' . $th;
  }

  die();
}
add_action('wp_ajax_one_wp_feed_rss_monitor_save', 'one_wp_feed_rss_monitor_save');
add_action('wp_ajax_nopriv_one_wp_feed_rss_monitor_save', 'one_wp_feed_rss_monitor_save');

if ( !function_exists('one_wp_feed_rss_monitor_menu') ) {
  function one_wp_feed_rss_monitor_menu() {    
    add_menu_page(
      'One WP Feed RSS Monitor',
      'Feed RSS Monitor',
      'manage_options',
      'one_wp_feed_rss_monitor',
      'one_wp_feed_rss_monitor_page',
      'dashicons-media-code',
      28
    );
  }
}
add_action( 'admin_menu', 'one_wp_feed_rss_monitor_menu' );
