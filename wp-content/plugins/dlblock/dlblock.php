<?php
/*
Plugin Name: DLBlock
Version: 0.1-alpha
Description: Block downloads
Author: Boone Gorges
Author URI: http://boone.gorg.es
Text Domain: dlblock
Domain Path: /languages
*/

define( 'DLBLOCK_DIR', plugin_dir_path( __FILE__ ) );

function dlblock_admin_init() {
	$blog_public = dlblock_blog_public();

	if ( $blog_public >= 0 ) {
		return;
	}
}
add_action( 'admin_init', 'dlblock_admin_init' );

function dlblock_blog_public() {
	return floatval( get_option( 'blog_public' ) );
}

/**
 * Test whether the attachment upload directory is protected.
 *
 * We create a dummy file in the directory, and then test to see
 * whether we can fetch a copy of the file with a remote request.
 *
 * @since 1.6.0
 *
 * @param bool $force_check True to skip the cache.
 * @return True if protected, false if not.
 */
function dlblock_check_is_protected( $force_check = true ) {
	global $is_apache;

	// Fall back on cached value if it exists
	if ( ! $force_check ) {
		$is_protected = get_option( 'dlblock_protection' );
		if ( '' === $is_protected ) {
			return (bool) $is_protected;
		}
	}

	// This should get abstracted out
	$uploads = wp_upload_dir();
	$test_file = $uploads['basedir'] . DIRECTORY_SEPARATOR . 'test.html';
	$test_text = 'This is a test file for DLBlock. Please do not remove.';

	if ( ! file_exists( $test_file ) ) {
		// Create an .htaccess, if we can
		if ( $is_apache ) {
			dlblock_create_htaccess_file( $uploads['basedir'] );
		}

		// Make a dummy file
		file_put_contents( $uploads['basedir'] . DIRECTORY_SEPARATOR . 'test.html', $test_text );
	}

	$test_url = $uploads['baseurl'] . '/test.html';
	$r = wp_remote_get( $test_url );

	// If the response body includes our test text, we have a problem
	$is_protected = true;
	if ( ! is_wp_error( $r ) && $r['body'] === $test_text ) {
		$is_protected = false;
	}

	// Cache
	$cache = $is_protected ? '1' : '0';
	bp_update_option( 'dlblock_protection', $cache );
	var_dump( $is_protected );

	return $is_protected;
}

function dlblock_create_htaccess_file( $dir ) {
	if ( ! file_exists( 'insert_with_markers' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/misc.php' );
	}

	$site_url = parse_url( site_url() );
	$path = ( !empty( $site_url['path'] ) ) ? $site_url['path'] : '/';

	$rules = dlblock_generate_htaccess_rules( $path );

	insert_with_markers( trailingslashit( $dir ) . '.htaccess', 'DLBlock', $rules );
}

function dlblock_generate_htaccess_rules( $rewrite_base = '/' ) {
	$rules = array(
		'RewriteEngine On',
		'RewriteBase ' . $rewrite_base,
		'RewriteRule (.+) ?dlb_download=$1 [R=302,NC]',
	);

	return $rules;
}

function dlblock_process_download_request() {
	global $current_blog;

	if ( empty( $_GET['dlb_download'] ) ) {
		return;
	}

	$file = urldecode( $_GET['dlb_download'] );

	if ( ! dlblock_user_has_access( $file ) ) {
		// @todo send forbidden headers
		return;
	}

	$uploads = wp_upload_dir();
	$path = $uploads['basedir'] . '/' . $file;

	// x-sendfile
	if ( apache_mod_loaded( 'mod_xsendfile' ) ) {
		require DLBLOCK_DIR . 'lib/xSendfile/xSendfile.php';
		\XSendfile\XSendfile::xSendfile( $path );
		exit;
	} else {
                ms_file_constants();

                error_reporting( 0 );

                if ( $current_blog->archived == '1' || $current_blog->spam == '1' || $current_blog->deleted == '1' ) {
                        status_header( 404 );
                        die( '404 &#8212; File not found.' );
                }

                $file = rtrim( BLOGUPLOADDIR, '/' ) . '/' . str_replace( '..', '', $_GET[ 'dlb_download' ] );
                if ( !is_file( $file ) ) {
                        status_header( 404 );
                        die( '404 &#8212; File not found.' );
                }

                $mime = wp_check_filetype( $file );
                if( false === $mime[ 'type' ] && function_exists( 'mime_content_type' ) )
                        $mime[ 'type' ] = mime_content_type( $file );

                if( $mime[ 'type' ] )
                        $mimetype = $mime[ 'type' ];
                else
                        $mimetype = 'image/' . substr( $file, strrpos( $file, '.' ) + 1 );

                header( 'Content-Type: ' . $mimetype ); // always send this
                if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) )
                        header( 'Content-Length: ' . filesize( $file ) );

                // Optional support for X-Sendfile and X-Accel-Redirect
                if ( WPMU_ACCEL_REDIRECT ) {
                        header( 'X-Accel-Redirect: ' . str_replace( WP_CONTENT_DIR, '', $file ) );
                        exit;
                } elseif ( WPMU_SENDFILE ) {
                        header( 'X-Sendfile: ' . $file );
                        exit;
                }

                $last_modified = gmdate( 'D, d M Y H:i:s', filemtime( $file ) );
                $etag = '"' . md5( $last_modified ) . '"';
                header( "Last-Modified: $last_modified GMT" );
                header( 'ETag: ' . $etag );
                header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );

                // Support for Conditional GET - use stripslashes to avoid formatting.php dependency
                $client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;

                if( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
                        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;

                $client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
                // If string is empty, return 0. If not, attempt to parse into a timestamp
                $client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;

                // Make a timestamp for our most recent modification...
                $modified_timestamp = strtotime($last_modified);

                if ( ( $client_last_modified && $client_etag )
                        ? ( ( $client_modified_timestamp >= $modified_timestamp) && ( $client_etag == $etag ) )
                        : ( ( $client_modified_timestamp >= $modified_timestamp) || ( $client_etag == $etag ) )
                        ) {
                        status_header( 304 );
                        exit;
                }

                // If we made it this far, just serve the file
                readfile( $file );
                exit;
	}
}
add_action( 'init', 'dlblock_process_download_request', 0 );

/**
 * Generate download headers
 *
 * @since 1.4
 * @param string $filename Full path to file
 * @return array Headers in key=>value format
 */
function dlblock_generate_headers( $filename ) {
	// Disable compression
	if ( function_exists( 'apache_setenv' ) ) {
		@apache_setenv( 'no-gzip', 1 );
	}
	@ini_set( 'zlib.output_compression', 'Off' );

	// @todo Make this more configurable
	$headers = wp_get_nocache_headers();

	// Content-Disposition
	$filename_parts = pathinfo( $filename );
	$headers['Content-Disposition'] = 'attachment; filename="' . $filename_parts['basename'] . '"';

	// Content-Type
	$filetype = wp_check_filetype( $filename );
	$headers['Content-Type'] = $filetype['type'];

	// Content-Length
	$filesize = filesize( $filename );
	$headers['Content-Length'] = $filesize;

	return $headers;
}

function dlblock_user_has_access( $file ) {
	$user_has_access = true;

	// @todo filter and separate MPO stuff
	$blog_public = dlblock_blog_public();

	switch ( $blog_public ) {
		case -1 :
			$user_has_access = is_user_logged_in();
			break;

		case -2 :
			$user_has_access = is_user_member_of_blog( get_current_user_id(), get_current_blog_id() );
			break;

		case -3 :
			$user_has_access = current_user_can( 'manage_options' );
			break;
	}

	return $user_has_access;
}

/** Enabling/disabling protection ********************************************/

function dlblock_update_blog_public( $value, $old_value ) {
	// @todo Apache check
	$uploads = wp_upload_dir();

	$htaccess_path = $uploads['basedir'] . '/.htaccess';
	if ( floatval( $value ) < 0 ) {
		dlblock_create_htaccess_file( $uploads['basedir'] );
	} else if ( file_exists( $htaccess_path ) ) {
		$htaccess_contents = file_get_contents( $htaccess_path );
		$htaccess_contents = preg_replace( '|# BEGIN DLBlock.*?END DLBlock|s', '', $htaccess_contents );
		if ( '' == trim( $htaccess_contents ) ) {
			@unlink( $htaccess_path );
		} else {
			file_put_contents( $htaccess_path, $htaccess_contents );
		}
	}

        return $value;
}
add_action( 'pre_update_option_blog_public', 'dlblock_update_blog_public', 10, 2 );

function dlblock_network_migrate() {
	if ( ! is_super_admin() ) {
		return;
	}

	if ( empty( $_GET['dlblock_network_migrate'] ) || 1 != $_GET['dlblock_network_migrate'] ) {
		return;
	}

	global $wpdb;
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
	echo '<pre>';
	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );

		$blog_public = get_option( 'blog_public' );

		// Fixing broken items
		if ( '' === $blog_public ) {
			$blog_public = 1;
		}

		echo "Updated blog $blog_id to $blog_public\n\r";

		update_option( 'blog_public', $blog_public );

		restore_current_blog();
	}
	echo '</pre>';
}
add_action( 'admin_init', 'dlblock_network_migrate' );
