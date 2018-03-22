<?php
/**
 * Freemage Settings class
 *
 * @package FreemageSettings
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}

// Initializes settings class.
if ( ! class_exists( 'FreemageSettings' ) ) {

	/**
	 * This is where all the settings happen.
	 */
	class FreemageSettings {


	    /**
	     * Holds the values to be used in the fields callbacks
		 *
		 * @var array
	     */
	    private $options;


		/**
		 * Hook into WordPress.
		 *
		 * @return	void
		 * @since	1.0
		 */
		function __construct() {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_options' ) );
		}


		/**
		 * Create the admin menu.
		 */
		public function admin_menu() {
			add_menu_page(
				esc_html__( 'Freemage', FREEMAGE ), // Page title.
				esc_html__( 'Freemage', FREEMAGE ), // Menu title.
				'manage_options', // Permissions.
				'freemage', // Slug.
				array( $this, 'create_admin_page' ), // Page creation function.
				'dashicons-images-alt'
			);

			add_submenu_page(
				'freemage', // Parent slug.
				esc_html__( 'Freemage', FREEMAGE ), // Page title.
				esc_html__( 'Settings', FREEMAGE ), // Menu title.
				'manage_options', // Permissions.
				'freemage', // Slug.
				array( $this, 'create_admin_page' ) // Page creation function.
			);
		}


		/**
		 * Create the options.
		 */
		public function register_options() {
	        register_setting(
	            'freemage_group', // Option group.
	            'freemage', // Option name.
	            array( $this, 'sanitize' ) // Sanitization func.
	        );

	        add_settings_section(
	            'freemage_general_section', // Section ID.
	            esc_html__( 'General Settings', FREEMAGE ), // Title.
	            '__return_false', // Callback.
	            'freemage' // Admin page.
	        );

	        add_settings_field(
	            'providers', // ID
	            esc_html__( 'Image Providers', FREEMAGE ), // Title
	            array( $this, 'providers_callback' ), // Callback
	            'freemage', // Admin Page.
	            'freemage_general_section' // Section ID.
	        );
		}


		/**
		 * The admin page settings content.
		 */
		public function create_admin_page() {

	        $this->options = get_option( 'freemage' );

			global $fre_fs;

			if ( empty( $this->options['providers'] ) ) {
				if ( $fre_fs->is_not_paying() ) {
					global $freemage_lite_providers;
					$this->options['providers'] = $freemage_lite_providers;
				} else {
					global $freemage_all_providers;
					$this->options['providers'] = array_keys( $freemage_all_providers );
				}
			}

			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Freemage Settings', FREEMAGE ) ?></h1>

				<?php
				$lite_class = $fre_fs->is_not_paying() ? 'freemage-lite' : '';
				if ( $fre_fs->is_not_paying() ) {
					$this->create_upgrade_sidebar();
				}
				?>

	            <form method="post" action="options.php" class="freemage-settings-form <?php echo esc_attr( $lite_class ) ?>">
	            <?php
	                settings_fields( 'freemage_group' );
	                do_settings_sections( 'freemage' );
	                submit_button();
	            ?>
	            </form>
			</div>
			<?php
		}


		/**
		 * Upgrade sidebar, for lite only.
		 */
		public function create_upgrade_sidebar() {
			global $fre_fs;
			?>
			<div class="freemage-settings-upgrade-area card">
				<img src="//plugins.svn.wordpress.org/freemage/assets/icon-128x128.png"/>
				<h3><?php esc_html_e( 'Freemage Premium', FREEMAGE ) ?></h3>
				<p><?php esc_html_e( 'See those disabled fields on the left? You can unlock those to get even more image search results from those image providers.', FREEMAGE ) ?></p>
				<a class="button button-primary" href="<?php echo esc_url( $fre_fs->get_upgrade_url() ) ?>"><?php esc_html_e( 'Check out the features', FREEMAGE ) ?></a>
			</div>
			<?php
		}


	    /**
	     * Sanitize each setting field as needed
	     *
	     * @param array $input Contains all settings fields as array keys.
	     */
	    public function sanitize( $input ) {
	        $new_input = array();

			if ( isset( $input['providers'] ) ) {
				$new_input['providers'] = array();
				foreach ( $input['providers'] as $key => $val ) {
					$new_input['providers'][ $key ] = ( isset( $input['providers'][ $key ] ) ) ? sanitize_text_field( $val ) : '';
				}
			}

	        return $new_input;
	    }

	    /**
	     * Get the settings option array and print one of its values
	     */
	    public function providers_callback() {
			?>
			<fieldset>
				<legend class="screen-reader-text">
					<span><?php esc_html_e( 'Image Providers', FREEMAGE ) ?></span>
				</legend>
				<?php
				global $fre_fs, $freemage_all_providers, $freemage_lite_providers;
				foreach ( $freemage_all_providers as $value => $label ) {
					$disabled = disabled( ! in_array( $value, $freemage_lite_providers, true ) && $fre_fs->is_not_paying(), true, false );
					$checked = checked( in_array( $value, $this->options['providers'], true ), true, false );
					?>
					<label>
						<input type="checkbox" name="freemage[providers][]" value="<?php echo esc_attr( $value ) ?>"
						<?php echo $disabled; // WPCS: XSS ok. ?>
						<?php echo $checked; // WPCS: XSS ok. ?>
						/>
						<?php echo esc_html( $label ) ?>
					</label>
					<br>
					<?php
				}
				?>
			</fieldset>
			<p class="description"><?php esc_html_e( 'Checked image providers will be used when searching.', FREEMAGE ) ?></p>
			<?php
	    }
	}

	new FreemageSettings();

}
?>
