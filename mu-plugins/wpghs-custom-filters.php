<?php
/**
 * Plugin Name:  WordPress-GitHub Sync Custom Filters
 * Plugin URI:   https://github.com/benbalter/wordpress-github-sync
 * Description:  Adds support for custom post types and statuses
 * Version:      1.0.0
 * Author:       James DiGioia
 * Author URI:   https://jamesdigioia.com/
 * License:      GPL2
 */

add_filter('wpghs_whitelisted_post_types', function ($supported_post_types) {
  return array_merge($supported_post_types, array(
    // add your custom post types here
    'help'
  ));
});

add_filter( 'the_content', function( $content ) {
  if( is_page() || is_single() ) {
    $content .= get_the_github_edit_link();
  }
  return $content;
}, 1000 );
