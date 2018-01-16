<?php
/**
 * Plugin Name:  WP GitHub Sync Meta
 * Plugin URI:   https://github.com/lite3/wp-github-sync-meta
 * Description:  Adds support for custom post meta, tags and categories
 * Version:      1.2
 * Author:       litefeel
 * Author URI:   https://www.litefeel.com/
 * License:      GPL2
 * Text Domain:  wp-github-sync-meta
 */

// add tags and categories to github
add_filter('wpghs_post_meta', function ($meta, $wpghs_post) {
    $tags = array();
    $list =  wp_get_post_terms( $wpghs_post->post->ID, 'help_tags' );
    if ( ! empty($list)) {
        foreach ($list as $value) {
            $tags[] = $value->name;
        }
    }
    $meta['help_tags'] = $tags;



    $categories = array();
    $list = wp_get_post_terms( $wpghs_post->post->ID, 'help_category' );
    if ( ! empty( $list ) ) {
        foreach( $list as $value ) {
            $categories[] = $value->name;
        }
    }
    $meta['help_category'] = $categories;

    return $meta;
}, 10, 2);


// github tags and categories to post
add_filter('wpghs_pre_import_args', function ($args, $wpghs_post) {

    $meta = $wpghs_post->get_meta();

    // update tags
    if (!empty($meta['help_tags'])) {
        $args['tags_input'] = $meta['help_tags'];
    }

    // update categories
    if (!empty($meta['help_category'])) {
        $categories = $meta['help_category'];
        if (!is_array($categories)) {
            $categories = array($categories);
        }
        $terms = get_terms(array(
            'taxonomy' => 'help_category',
            'fields' => 'id=>name',
            'hide_empty' => 0,
            'name' => $categories
            )
        );
        $map = array();
        foreach ($categories as $name) {
            $map[$name] = 1;
        }

        $ids = array();
        if (!empty($terms)) {
            foreach ($terms as $id => $name) {
                $ids[] = $id;
                unset($map[$name]);
            }
        }

        // create new terms
        if (!empty($map)) {
            foreach ($map as $name => $value) {
                $term = wp_insert_term($name, 'help_category', array('parent' => 0));
                // array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);
                $ids[] = $term['term_id'];
            }
        }

        $args['help_category'] = $ids;
    }

    return $args;
}, 10, 2);

// github meta to post
add_filter('wpghs_pre_import_meta', function ($meta, $wpghs_post) {
    unset($meta['help_tags']);
    unset($meta['help_category']);

    // unset wordpress github sync meta
    unset($meta['author']);
    unset($meta['post_date']);
    unset($meta['post_excerpt']);
    unset($meta['permalink']);
    return $meta;
}, 10, 2);

// modify edit post link
//    $wpghs_post = new WordPress_GitHub_Sync_Post( $postID, WordPress_GitHub_Sync::$instance->api() );
//    return $wpghs_post->github_edit_url();
//}, 10, 3);

// load_plugin_textdomain('wp-github-sync-meta');
