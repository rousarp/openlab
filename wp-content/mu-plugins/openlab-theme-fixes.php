<?php

/**
 * Loads theme fixes for OpenLab site themes
 */
function openlab_load_theme_fixes() {
	$t = get_stylesheet();

	switch ( $t ) {
		case 'carrington-blog' :
		case 'coraline' :
		case 'herothemetrust' :
		case 'motion' :
		case 'pilcrow' :
		case 'sliding-door' :
		case 'themorningafter' :
		case 'wu-wei' :
                case 'twentyfifteen':

			echo '<link rel="stylesheet" id="' . $t . '-fixes" type="text/css" media="screen" href="' . get_home_url() . '/wp-content/mu-plugins/theme-fixes/' . $t . '.css" />
';

			break;
	}
}
add_action( 'wp_print_styles', 'openlab_load_theme_fixes', 9999 );

/**
 * Arrange themes so that preferred themes appear first in the list.
 */
function openlab_reorder_theme_selections( $themes ) {
	$preferred_themes = array(
		'twentyfifteen',
		'filtered',
		'herothemetrust',
		'twentyeleven',
		'twentyfourteen',
		'twentysixteen',
		'twentythirteen',
		'twentytwelve',
	);

	$t1 = $t2 = array();

	foreach ( $themes as $theme_name => $theme ) {
		if ( in_array( $theme_name, $preferred_themes, true ) ) {
			$t1[ $theme_name ] = $theme;
		} else {
			$t2[ $theme_name ] = $theme;
		}
	}

	// Sort the $t1 array to match the preferred order.
	uasort( $t1, function( $a, $b ) use ( $preferred_themes ) {
		$apos = array_search( $a['id'], $preferred_themes );
		$bpos = array_search( $b['id'], $preferred_themes );

		return ( $apos < $bpos ) ? -1 : 1;
	} );

	return array_merge( $t1, $t2 );
}
add_filter( 'wp_prepare_themes_for_js', 'openlab_reorder_theme_selections' );

/**
 * Hemingway: When there's no nav menu, ensure that Course Profile and Home links appear.
 *
 * This theme uses wp_list_pages() rather than a normal WP function for building
 * the default menu.
 */
function openlab_fix_fallback_menu_for_hemingway( $output, $r, $pages ) {
	if ( 'hemingway' !== get_template() ) {
		return $output;
	}

	$dbs = debug_backtrace();
	$gp_key = null;
	foreach ( $dbs as $key => $db ) {
		if ( 'wp_list_pages' === $db['function'] ) {
			$lp_key = $key;
			break;
		}
	}

	if ( null === $lp_key ) {
		return $output;
	}

	// It really doesn't get any worse than this.
	if ( ! isset( $dbs[ $lp_key + 4 ] ) || 'get_header' !== $dbs[ $lp_key + 4 ]['function'] ) {
		return $output;
	}

	// Fake pages.
	$group_id = openlab_get_group_id_by_blog_id( get_current_blog_id() );
	if ( ! $group_id ) {
		return $output;
	}

	$home_link = sprintf(
		'<li><a title="Site Home" href="%s">Home</a></li>',
		esc_url( trailingslashit( get_option( 'home' ) ) )
	);

	$group_type_label = openlab_get_group_type_label( array(
		'group_id' => $group_id,
		'case' => 'upper',
	) );
	$group_link = bp_get_group_permalink( groups_get_group( array( 'group_id' => $group_id ) ) );

	$profile_link = sprintf(
		'<li id="menu-item-group-profile-link" class="group-profile-link"><a href="%s">%s</a>',
		esc_url( $group_link ),
		sprintf( '%s Profile', $group_type_label )
	);

	$output = $profile_link . "\n" . $home_link . "\n" . $output;

	return $output;
}
add_filter( 'wp_list_pages', 'openlab_fix_fallback_menu_for_hemingway', 10, 3 );

/**
 * Prevent Sliding Door from showing plugin installation notice.
 */
function openlab_remove_sliding_door_plugin_installation_notice() {
	if ( 'sliding-door' === get_template() ) {
		remove_action( 'tgmpa_register', 'my_theme_register_required_plugins' );
	}
}
add_action( 'after_setup_theme', 'openlab_remove_sliding_door_plugin_installation_notice', 100 );

/**
 * Sliding Door requires the Page Links To plugin.
 */
function openlab_activate_page_links_to_on_sliding_door() {
	if ( 'sliding-door' !== get_template() ) {
		return;
	}

	if ( ! is_admin() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	if ( ! is_plugin_active( 'page-links-to/page-links-to.php' ) ) {
		activate_plugin( 'page-links-to/page-links-to.php' );
	}
}
add_action( 'after_setup_theme', 'openlab_activate_page_links_to_on_sliding_door', 50 );

/**
 * Override Pilcrow's fallback page menu overrides.
 */
function openlab_pilcrow_page_menu_args( $args ) {
	remove_filter( 'wp_page_menu_args', 'pilcrow_page_menu_args' );
	$args['depth']     = 0;
	return $args;
}
add_filter( 'wp_page_menu_args', 'openlab_pilcrow_page_menu_args', 5 );
