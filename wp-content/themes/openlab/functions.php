<?php

function openlab_core_setup() {
    add_theme_support('post-thumbnails');
    global $content_width;
    register_nav_menus(array(
        'main' => __('Main Menu', 'openlab'),
        'aboutmenu' => __('About Menu', 'openlab'),
        'helpmenu' => __('Help Menu', 'openlab'),
        'helpmenusec' => __('Help Menu Secondary', 'openlab')
    ));
}

add_action('after_setup_theme', 'openlab_core_setup');

/* * creating a library to organize functions* */
require_once( STYLESHEETPATH . '/lib/course-clone.php' );
require_once( STYLESHEETPATH . '/lib/header-funcs.php' );
require_once( STYLESHEETPATH . '/lib/post-types.php' );
require_once( STYLESHEETPATH . '/lib/menus.php' );
require_once( STYLESHEETPATH . '/lib/content-processing.php' );
require_once( STYLESHEETPATH . '/lib/nav.php' );
require_once( STYLESHEETPATH . '/lib/breadcrumbs.php' );
require_once( STYLESHEETPATH . '/lib/group-funcs.php' );
require_once( STYLESHEETPATH . '/lib/ajax-funcs.php' );
require_once( STYLESHEETPATH . '/lib/help-funcs.php' );
require_once( STYLESHEETPATH . '/lib/member-funcs.php' );
require_once( STYLESHEETPATH . '/lib/page-funcs.php' );
require_once( STYLESHEETPATH . '/lib/admin-funcs.php' );

function openlab_load_scripts() {
    /**
     * scripts, additional functionality
     */
    if (!is_admin()) {

        //need to turn less.js (local only) off for now until issues with comments in Bootstrap is resolved
        $local_off = false;

        //less for local dev
        //Local dev less debugging
        if ($local_off) {
            wp_register_style('main-styles', get_stylesheet_directory_uri() . '/less/style.less', array(), '20130604', 'all');
            wp_enqueue_style('main-styles');
        } else {
            wp_register_style('main-styles', get_stylesheet_uri(), array(), '20130604', 'all');
            wp_enqueue_style('main-styles');
        }


        if ($local_off) {
            wp_register_script('less-config-js', get_stylesheet_directory_uri() . '/js/less.config.js', array('jquery'));
            wp_enqueue_script('less-config-js');
            wp_register_script('less-js', get_stylesheet_directory_uri() . '/js/less-1.7.0.js', array('jquery'));
            wp_enqueue_script('less-js');
        }

        wp_register_script('bootstrap-js', get_stylesheet_directory_uri() . '/js/bootstrap.min.js', array('jquery'));
        wp_enqueue_script('bootstrap-js');
    }
}

add_action('wp_enqueue_scripts', 'openlab_load_scripts');
