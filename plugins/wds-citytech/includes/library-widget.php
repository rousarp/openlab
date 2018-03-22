<?php

/**
 * Functionality related to the Library widgets on sites and course profiles.
 */

add_action( 'openlab_before_group_privacy_settings', 'openlab_group_library_settings' );
add_action( 'widgets_init', 'openlab_register_library_tools_widget' );

/**
 * Checks whether a group has the Library Tools feature enabled on the group profile.
 *
 * Defaults to true for Courses, otherwise defaults to false.
 *
 * @param int $group_id ID of the group.
 * @return bool
 */
function openlab_library_tools_are_enabled_for_group( $group_id ) {
	$setting = groups_get_groupmeta( $group_id, 'library_tools_enabled', true );

	// Courses default to 'yes'.
	if ( ! $setting ) {
		$group_type = openlab_get_group_type( $group_id );
		$setting = 'course' === $group_type ? 'yes' : 'no';
	}

	return 'yes' === $setting;
}

/**
 * Renders the Library Settings section of the group admin.
 */
function openlab_group_library_settings() {
	$group_type_label = openlab_get_group_type_label( array(
		'case' => 'upper',
	) );

	$setting = openlab_library_tools_are_enabled_for_group( bp_get_current_group_id() );

	?>
	<div class="panel panel-default">
		<div class="panel-heading">Nastavení vyhledávání na portále www.gov.cz</div>

		<div class="panel-body">
			<p>Tato nastavení umožní nebo zakáže zobrazení nástroje vyhledávání (gov.cz) na profilu <?php echo esc_html( _x(strtolower($group_type_label),'2J-vy','openlab')); ?>.</p>

			<div class="checkbox">
				<label><input type="checkbox" name="group-show-library-tools" id="group-show-library-tools" value="1" <?php checked( $setting ); ?> /> Povolit nástroje vyhledávání</label>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Adds the library tools display to group sidebars.
 */
function openlab_group_library_tools_display() {
	if ( ! openlab_library_tools_are_enabled_for_group( bp_get_current_group_id() ) ) {
		return;
	}

	?>

	<div id="openlab-library-tools-sidebar-widget" class="sidebar-widget openlab-library-tools">
		<h2 class="sidebar-header">Životní situace</h2>

		<div class="sidebar-block">
			<div class="sidebar-block-content">
				<h3 class="sidebar-block-header">Vyhledávání na portále www.gov.cz</h3>
				<?php openlab_library_search_form(); ?>
			</div>
		</div>

		<div class="sidebar-block">
			<div class="sidebar-block-content">
				<h3 class="sidebar-block-header">Informace o veřejné správě</h3>
				<?php openlab_library_information(); ?>
			</div>
		</div>
	</div>

	<?php
}
add_action( 'bp_group_options_nav', 'openlab_group_library_tools_display', 80 );

/**
 * Registers the Library Tools widget for WP sites.
 */
function openlab_register_library_tools_widget() {
	register_widget( 'OpenLab_Library_Tools_Widget' );
}

/**
 * Outputs the markup for the Library Search box.
 */
function openlab_library_search_form() {
	?>

<form action="https://gov.cz/obcan/hledat?q=" enctype="application/x-www-form-urlencoded; charset=utf-8" method="get" name="searchForm" role="search">

<input label= "q" id="q" class="focus form-control" name="q" type="text" placeholder="Vyhledat životní situace, návody a vše ostatní" aria-label="Zadejte vyhledávací dotaz zde"/>

<select name="selectStyle" class="form-control" aria-label="Vyhledávání podle typu">
<option label="Občan">Občan</option>
<option label="Podnikatel">Podnikatel</option>
<option label="Cizinec">Cizinec</option>
<option label="Úředník">Úředník</option>
</select>

<div class="library-search-actions">
	<input alt="Search" class="btn btn-primary" id="submit" class="library-search-submit" title="Vyhledat životní situace, návody a vše ostatní" type="submit" value="Vyhledat" />

	<a class="library-search-advanced-link" href="https://gov.cz/otevrena-data/">Otevřená data</a>
</div>

</form>

	<?php
}

/**
 * Outputs the markup for the Library Information box.
 */
function openlab_library_information() {
	?>
	<div class="openlab-library-information">
		<a class="bold" href="https://urad.online" target="_blank">Portál www.urad.online</a><br />
		<a class="bold" href="https://gov.cz" target="_blank">Portál www.gov.cz</a><br />


		<ul>
			<li><a href="https://gov.cz/obcan/" target="_blank">Občan</a></li>
			<li><a href="https://gov.cz/podnikani/" target="_blank">Podnikatel</a></li>
			<li><a href="https://gov.cz/cizinec/" target="_blank">Cizinec</a></li>
			<li><a href="https://gov.cz/urednik//" target="_blank">Úředník</a></li>

		</ul>
	</div>
	<?php
}

/**
 * Library Tools widget class.
 */
class OpenLab_Library_Tools_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'openlab-library-tools-widget',
			'Library Tools',
			array(
				'class' => 'openlab-library-tools-widget',
			)
		);
	}

	public function parse_settings( $settings ) {
		$merged = array_merge( array(
			'find_library_materials' => true,
			'library_information' => true,
		), $settings );

		// boolval() available only on PHP 5.5+
		foreach ( $merged as &$m ) {
			$m = (bool) $m;
		}

		return $merged;
	}

	public function widget( $args, $instance ) {
		$settings = $this->parse_settings( $instance );

		?>

		<?php if ( $settings['find_library_materials'] ) : ?>
			<?php /* Divs with ids help with CSS specificity and theme overrides */ ?>
			<div id="openlab-library-find-widget-content">
				<?php echo str_replace( 'id="', 'id="find-', $args['before_widget'] ); ?>
				<?php echo $args['before_title']; ?>Hledat informace o veřejné správě<?php echo $args['after_title']; ?>

				<?php openlab_library_search_form(); ?>

				<?php echo $args['after_widget']; ?>
			</div>
		<?php endif; ?>

		<?php if ( $settings['library_information'] ) : ?>
			<div id="openlab-library-information-widget-content">
				<?php echo str_replace( 'id="', 'id="information-', $args['before_widget'] ); ?>
				<?php echo $args['before_title']; ?>Vyhledávání gov.cz<?php echo $args['after_title']; ?>

				<?php openlab_library_information(); ?>

				<?php echo $args['after_widget']; ?>
			</div>
		<?php endif; ?>

		<style type="text/css">
			.widget_openlab-library-tools-widget input[type="text"],
			.widget_openlab-library-tools-widget select {
				margin-bottom: .5rem;
			}

			#openlab-library-information-widget-content ul {
				list-style-type: disc;
				margin-top: .5rem;
				padding-left: 20px;
			}

			.library-search-advanced-link {
				font-size: .9rem;
				white-space: nowrap;
			}
		</style>

		<?php

	}

	public function form( $instance ) {
		$settings = $this->parse_settings( $instance );

		?>

		<p>
			<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'find_library_materials' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'find_library_materials' ) ); ?>" value="1" <?php checked( $settings['find_library_materials'] ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'find_library_materials' ) ); ?>">Hledat informace o veřejné správě</label>
		</p>

		<p>
			<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'library_information' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'library_information' ) ); ?>" value="1" <?php checked( $settings['library_information'] ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'library_information' ) ); ?>">Informace o veřejné správě</label>
		</p>

		<?php wp_nonce_field( 'openlab_library_widget', 'openlab-library-widget-nonce', false ); ?>

		<?php
	}

	public function update( $new_instance, $old_instance ) {
		if ( empty( $_POST['openlab-library-widget-nonce'] ) ) {
			return $old_instance;
		}

		if ( ! wp_verify_nonce( $_POST['openlab-library-widget-nonce'], 'openlab_library_widget' ) ) {
			return $old_instance;
		}

		$passed = array(
			'find_library_materials' => ! empty( $new_instance['find_library_materials'] ),
			'library_information' => ! empty( $new_instance['library_information'] ),
		);

		return $this->parse_settings( $passed );
	}
}
