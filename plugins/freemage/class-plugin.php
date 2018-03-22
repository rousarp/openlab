<?php
/**
 * The main plugin file
 *
 * @package Freemage
 */

/**
Plugin Name: Freemage
Description: Search for Creative Commons Images inside your Media Manager
Author: Gambit Technologies
Version: 1.0
Author URI: http://gambit.ph
Plugin URI: http://wordpress.org/plugins/freemage
Text Domain: freemage
Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}

// Identifies the current plugin version.
defined( 'VERSION_FREEMAGE' ) or define( 'VERSION_FREEMAGE', '1.0' );

// The slug used for translations & other identifiers.
defined( 'FREEMAGE' ) or define( 'FREEMAGE', 'freemage' );

// Used for lite vs. premium logic.
defined( 'FREEMAGE_IS_LITE' ) or define( 'FREEMAGE_IS_LITE', true );

// This is the main plugin functionality.
require_once( 'class-freemius.php' );
require_once( 'class-freemage.php' );
require_once( 'class-settings.php' );


global $freemage_all_providers;
$freemage_all_providers = array(
	'flickr' => __( 'Flickr', FREEMAGE ),
	'giphy' => __( 'Giphy', FREEMAGE ),
	'unsplash' => __( 'Unsplash', FREEMAGE ),
	'pixabay' => __( 'Pixabay', FREEMAGE ),
	'pexels' => __( 'Pexels', FREEMAGE ),
	'fivehundredpx' => __( '500px', FREEMAGE ),
);

global $freemage_lite_providers;
$freemage_lite_providers = array(
	'flickr',
	'giphy',
);

// Initializes plugin class.
if ( ! class_exists( 'FreemagePlugin' ) ) {

	/**
	 * Initializes core plugin that is readable by WordPress.
	 *
	 * @return	void
	 * @since	1.0
	 */
	class FreemagePlugin {

		/**
		 * Hook into WordPress.
		 *
		 * @return	void
		 * @since	1.0
		 */
		function __construct() {

			// Our translations.
			add_action( 'plugins_loaded', array( $this, 'load_text_domain' ), 1 );
		}


		/**
		 * Loads the translations.
		 *
		 * @return	void
		 * @since	1.0
		 */
		public function load_text_domain() {
			load_plugin_textdomain( FREEMAGE, false, basename( dirname( __FILE__ ) ) . '/languages/' );
		}
	}

	new FreemagePlugin();
}


/**
 * Uninstall method. Instead of using uninstall.php, this is compatible with
 * Freemius.
 */
function freemage_uninstall() {
	// Do nothing for now.
}
