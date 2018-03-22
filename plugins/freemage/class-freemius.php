<?php
/**
 * Freemage Freemius class
 *
 * @package FreemageFreemius
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}

// Initializes Freemius.
if ( ! class_exists( 'FreemageFreemius' ) ) {

	/**
	 * This is where Freemius is integrated.
	 */
	class FreemageFreemius {

		/**
		 * Hook into WordPress.
		 *
		 * @return	void
		 * @since	1.0
		 */
		function __construct() {
			$this->init_freemius();

			// Include our uninstall logic.
			global $fre_fs;
			$fre_fs->add_action( 'after_uninstall', 'freemage_uninstall' );

			if ( get_transient( 'freemage_just_upgraded' ) ) {
				add_action( 'admin_notices', array( $this, 'add_download_premium_notice' ) );
			}
		}


		/**
		 * Initializes Freemius
		 */
		public function init_freemius() {
			global $fre_fs;

			if ( ! isset( $fre_fs ) ) {

				// Include Freemius SDK.
				require_once dirname( __FILE__ ) . '/freemius/start.php';

				$fre_fs = fs_dynamic_init( array(
					'id' => '377',
					'slug' => 'freemage',
					'type' => 'plugin',
					'public_key' => 'pk_9ced5e53d15e8bcc691642330549d',
					'is_live' => true,
					'is_premium' => true,
					'has_addons' => false,
					'has_paid_plans' => true,
					'menu' => array(
						'slug' => 'freemage',
						'first-path' => 'options-general.php?page=freemage',
						'support' => false,
					),
				) );
			}
		}


		/**
		 * Admin notice for lite installs but premium user.
		 */
		public function add_download_premium_notice() {
			global $fre_fs;
			if ( ! $fre_fs->is_not_paying() && FREEMAGE_IS_LITE ) {
				?>
				<div class="notice notice-warning is-dismissible">
					<p><?php esc_html_e( 'Freemage: Hey premium user, you have the lite version installed. Download and activate the premium version from the email you got.', FREEMAGE ) ?></p>
				</div>
				<?php
			}
		}
	}

	new FreemageFreemius();

}
?>
