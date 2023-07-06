<?php

/*
  * Plugin Name: One WP Feed RSS Monitor
  * Description: Monitor and auto-publish podcast episodes as wordpress posts
  * Version: 1.0.1
  * Author: Victor Andeloci
  * Author URI: https://github.com/victorandeloci
*/

function one_wp_feed_rss_monitor_xml_attribute($object, $attribute) {
  if (isset($object[$attribute]))
    return (string) $object[$attribute];
}

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

    // cron job exec
    include_once('templates/cron_exec.php');
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

// get feed rss eps
function one_wp_feed_rss_monitor_get_podcast_episodes($feed_url) {
  $rss = simplexml_load_file($feed_url);
  $episodes = [];

  foreach ($rss->channel->item as $item) {
    $episode = [];
    $episode['default_title'] = (string) $item->title;
    $episode['title'] = (string) str_replace('”', '"', 
                          str_replace('“', '"', 
                            str_replace('‘', "'", 
                              str_replace('…', '...', 
                                str_replace('’', "'", 
                                  str_replace('–', '-', 
                                    trim($item->title)))))));
    $episode['description'] = (string) $item->description;
    $episode['link'] = (string) $item->link;
    $episode['mp3_url'] = (string) one_wp_feed_rss_monitor_xml_attribute($item->enclosure, 'url');
    $episode['duration'] = (string) $item->children('itunes', true)->duration;
    $episode['image_url'] = (string) $item->children('itunes', true)->image->attributes()->href;
    $episode['pub_date'] = (string) $item->pubDate;
    $episode['tags'] = [];

    // verify if episode post exists
    $existing_post = get_page_by_title($episode['default_title'], OBJECT, 'post');
    if (!$existing_post)
      $existing_post = get_page_by_title($episode['title'], OBJECT, 'post');

    if (!$existing_post) {
      // get episode description tags (#tag1, #tag2)
      $description = $episode['description'];
      $tags_start = strpos($description, '#');
      if ($tags_start !== false) {
        $tags_end = strpos($description, "\n", $tags_start);
        if ($tags_end === false) {
          $tags_end = strlen($description);
        }
        $tags_str = substr($description, $tags_start, $tags_end - $tags_start);
        $tags = explode(' ', $tags_str);
        foreach ($tags as $tag) {
          $episode['tags'][] = str_replace('#', '', $tag);
        }
      }

      $episodes[] = $episode;
    }
  }

  return $episodes;
}

function one_wp_feed_rss_monitor_create_podcast_post($episode) {
  try {
    $post_data = array(
      'post_title' => $episode['title'],
      'post_content' => $episode['description'],
      'post_status' => 'publish',
      'post_type' => 'post',
      'post_date' => date('Y-m-d', strtotime($episode['pub_date'])),
      'post_author' => (get_current_user_id() ?? 1),
      'meta_input' => array(
        'episode_link' => $episode['link'],
        'episode_mp3_url' => $episode['mp3_url'],
        'episode_duration' => $episode['duration'],
        'episode_cover' => $episode['image_url']
      )
    );

    $post_id = wp_insert_post($post_data, true);

    if ($post_id && !is_wp_error($post_id)) {
      // feat. image using "itunes:image"
      $image_url = $episode['image_url'];
      if ($image_url) {
        $image_id = media_sideload_image($image_url, $post_id, null, 'id');
        if (!is_wp_error($image_id)) {
          set_post_thumbnail($post_id, $image_id);
        }
      }

      // tags
      $tags = $episode['tags'];
      if (!empty($tags)) {
        wp_set_post_tags($post_id, $tags);
      }

      // episode / post category based on title search by term (defined in settings)
      $idsToTermsData = get_option('one_wp_feed_rss_monitor_ids_to_terms', '');
      if ($idsToTermsData != null && $idsToTermsData != '') {
        $idsToTerms = json_decode($idsToTermsData);
        // default category defined in settings
        $defaultCategory = get_option('one_wp_feed_rss_monitor_default_cat', '');
        $postCategories = [];
        if ($defaultCategory != null && $defaultCategory != '')
          $postCategories[] = $defaultCategory;

        foreach ($idsToTerms as $id => $term) {
          if (strpos($episode['title'], $term) !== false) {
            $postCategories[] = $id;
          }
          wp_set_post_categories($post_id, $postCategories);
        }
      } else {
        echo 'Terms not defined!<br>';
      }

      return true;
    } else {
      echo 'Could not create post <strong>' . $episode['title'] . '</strong> - ' . $post_id->get_error_message() . '<br>';
    }
  } catch (\Throwable $th) {
    echo 'Fatal error during post creation: ' . $th . '<br>';
  }
}

function one_wp_feed_rss_monitor_update_posts_episodes() {
  $feed_url = get_option('one_wp_feed_rss_monitor_feed_url', '');
  if (!empty($feed_url)) {
    // get feed RSS eps
    $episodes = one_wp_feed_rss_monitor_get_podcast_episodes($feed_url);
    if (!empty($episodes)) {
      // create posts foreach ep
      $podcastPostCount = 0;
      foreach ($episodes as $episode) {
        if (one_wp_feed_rss_monitor_create_podcast_post($episode))
          $podcastPostCount++;
      }
      echo $podcastPostCount . ' post(s) created!';
    } else {
      echo 'Could not find new episodes...';
    }
  } else {
    echo 'Feed RSS URL not defined!';
  }

  die();
}
add_action('wp_ajax_one_wp_feed_rss_monitor_update_posts_episodes', 'one_wp_feed_rss_monitor_update_posts_episodes');
add_action('wp_ajax_nopriv_one_wp_feed_rss_monitor_update_posts_episodes', 'one_wp_feed_rss_monitor_update_posts_episodes');
