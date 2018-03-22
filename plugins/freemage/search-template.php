<?php
/**
 * Template file for the widget picker modal popup.
 *
 * @package Freemage
 */

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly.
}

global $fre_fs;

$freemage_lite = '';
if ( $fre_fs->is_not_paying() ) {
	$freemage_lite = 'freemage-lite';
}

?>
<script type="text/html" id="tmpl-freemage-search">
	<div class="media-toolbar freemage-media-toolbar <?php echo esc_attr( $freemage_lite ) ?>">
		<div class="media-toolbar-secondary">
			<label for="freemage-provider-filters" class="screen-reader-text"><?php esc_html_e( 'Filter by provider', FREEMAGE ) ?></label>
			<select id="freemage-provider-filters" class="attachment-filters">
				<option value=""><?php esc_html_e( 'All image providers', FREEMAGE ) ?></option>

				<?php
				global $freemage_all_providers, $freemage_lite_providers;
				$options = get_option( 'freemage' );
				if ( empty( $options ) ) {
					$options = array(
						'providers' => array_keys( $freemage_all_providers ),
					);
				} else if ( empty( $options['providers'] ) ) {
					$options['providers'] = array_keys( $freemage_all_providers );
				}

				if ( in_array( 'flickr', $options['providers'], true ) ) {
					?>
					<option value="flickr"><?php esc_html_e( 'Flickr', FREEMAGE ) ?></option>
					<?php
				}
				if ( in_array( 'giphy', $options['providers'], true ) ) {
					?>
					<option value="giphy"><?php esc_html_e( 'Giphy', FREEMAGE ); ?></option>
					<?php
				}

				$disabled = 'disabled="disabled"';
				$suffix = esc_html__( '(Go premium to unlock)', FREEMAGE );

				if ( ! $fre_fs->is_not_paying() && FREEMAGE_IS_LITE ) {
					$suffix = esc_html__( '(Activate the premium version first)', FREEMAGE );
				} else if ( ! $fre_fs->is_not_paying() ) {
					$disabled = '';
					$suffix = '';
				}


				if ( in_array( 'pixabay', $options['providers'], true ) || $fre_fs->is_not_paying() ) {
					?>
					<option value="pixabay" <?php echo $disabled // WPCS: XSS ok. ?>><?php esc_html_e( 'Pixabay', FREEMAGE ); ?> <?php echo $suffix // WPCS: XSS ok. ?></option>
					<?php
				}
				if ( in_array( 'pexels', $options['providers'], true ) || $fre_fs->is_not_paying() ) {
					?>
					<option value="pexels" <?php echo $disabled // WPCS: XSS ok. ?>><?php esc_html_e( 'Pexels', FREEMAGE ); ?> <?php echo $suffix // WPCS: XSS ok. ?></option>
					<?php
				}
				if ( in_array( 'unsplash', $options['providers'], true ) || $fre_fs->is_not_paying() ) {
					?>
					<option value="unsplash" <?php echo $disabled // WPCS: XSS ok. ?>><?php esc_html_e( 'Unsplash', FREEMAGE ); ?> <?php echo $suffix // WPCS: XSS ok. ?></option>
					<?php
				}
				if ( in_array( 'fivehundredpx', $options['providers'], true ) || $fre_fs->is_not_paying() ) {
					?>
					<option value="fivehundredpx" <?php echo $disabled // WPCS: XSS ok. ?>><?php esc_html_e( '500px', FREEMAGE ); ?> <?php echo $suffix // WPCS: XSS ok. ?></option>
					<?php
				}

				?>
			</select>
			<label for="freemage-license-filters" class="screen-reader-text"><?php esc_html_e( 'Filter by license', FREEMAGE ) ?></label>
			<select id="freemage-license-filters" class="attachment-filters">
				<option value=""><?php esc_html_e( 'Licenses that allow commercial use', FREEMAGE ) ?></option>
				<option value="noncommercial"><?php esc_html_e( 'Include noncommercial licenses', FREEMAGE ) ?></option>
				<option value="noattribution"><?php esc_html_e( 'Licenses with no attribution required', FREEMAGE ) ?></option>
			</select>
			<span class="spinner"></span>
		</div>
		<div class="media-toolbar-primary search-form">
			<label for="media-search-input" class="screen-reader-text"><?php esc_html_e( 'Search Image', FREEMAGE ) ?></label>
			<input type="search" placeholder="Search" id="media-search-input" class="search"/>
		</div>
	</div>
	<div class="media-sidebar">
		<div class="freemage-details" style="display: none;">
			<h2><?php esc_html_e( 'Image Details', FREEMAGE ) ?></h2>
			<div class='attachment-info'>
				<div class='thumbnail thumbnail-image'>
					<img class='preview' draggable="false"/>
				</div>
			</div>
			<div class='source'><strong><?php esc_html_e( 'Source:', FREEMAGE ) ?></strong> <span></span></div>
			<div class='provider'><strong><?php esc_html_e( 'Image Provider:', FREEMAGE ) ?></strong> <span></span></div>
			<div class='title'><strong><?php esc_html_e( 'Title:', FREEMAGE ) ?></strong> <span></span></div>
			<div class='date'><strong><?php esc_html_e( 'Date:', FREEMAGE ) ?></strong> <span></span></div>
			<div class='owner'><strong><?php esc_html_e( 'Owner:', FREEMAGE ) ?></strong> <span></span></div>
			<div class='license'><strong><?php esc_html_e( 'License:', FREEMAGE ) ?></strong> <span></span></div>
			<div class='sizes'><strong><?php esc_html_e( 'Sizes:', FREEMAGE ) ?></strong> <span></span></div>
		</div>
	</div>
	<ul tabindex="-1" class="attachments freemage-result-container <?php echo esc_attr( $freemage_lite ) ?>">
	</ul>
	<div class="freemage-no-results freemage-hidden"><?php esc_html_e( 'No images found', FREEMAGE ) ?></div>
	<?php
	if ( $fre_fs->is_not_paying() ) {
		?>
		<div class="freemage-upgrade-note"><?php printf( esc_html__( '%sUpgrade to Premium%s to search images from more providers: Pexels, Giphy, Pixabay, Unsplash, and 500px', FREEMAGE ), '<a href="' . esc_url( $fre_fs->get_upgrade_url() ) . '">', '</a>' ) ?></div>
		<?php
	}
	?>
</script>


<script type="text/html" id="tmpl-freemage-search-result">
	<div class="attachment-preview {{ data.orientation }}">
		<div class="thumbnail">
			<div class="centered">
				<img src="{{ data.preview }}" draggable="false" alt="" width="{{ data.preview_width }}" height="{{ data.preview_height }}">
			</div>
		</div>
		<div class="freemage-overlay">
			<div class="freemage-provider-{{ data.provider }}" role="presentation" title="{{ freemageParams.providers[ data.provider ] }}"></div>
			<div class="freemage-info"></div>
			<div class="freemage-downloading"></div>
			<div class="freemage-download-label"><?php esc_html_e( 'Download', FREEMAGE ) ?></div>
			<div class="freemage-download" title="<?php esc_attr_e( 'Download to site', FREEMAGE ) ?>"></div>
			<# if ( data.badges.indexOf( 'attribution' ) !== -1 ) { #>
				<div class="freemage-badge-cc" role="presentation" title="<?php echo esc_attr( __( 'Needs attribution', FREEMAGE ) ) ?>"></div>
			<# } #>
			<# if ( data.badges.indexOf( 'noncommercial' ) !== -1 ) { #>
				<div class="freemage-badge-noncommercial" role="presentation" title="<?php echo esc_attr( __( 'Noncommercial', FREEMAGE ) ) ?>"></div>
			<# } #>
			<# if ( data.badges.indexOf( 'zero' ) !== -1 ) { #>
				<!-- <div class="freemage-badge-zero" role="presentation" title="<?php echo esc_attr( __( 'Public Domain', FREEMAGE ) ) ?>"></div> -->
			<# } #>
			<# if ( data.badges.indexOf( 'warning' ) !== -1 ) { #>
				<div class="freemage-badge-warning" role="presentation" title="<?php echo esc_attr( __( 'Check License', FREEMAGE ) ) ?>"></div>
			<# } #>
		</div>
	</div>
</script>
