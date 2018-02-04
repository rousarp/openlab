<?php
/*
  Plugin Name: WDS CityTech
  Plugin URI: http://citytech.webdevstudios.com
  Description: Custom Functionality for CityTech BuddyPress Site.
  Version: 1.0
  Author: WebDevStudios
  Author URI: http://webdevstudios.com
 */

include 'wds-register.php';
include 'wds-docs.php';
include 'includes/oembed.php';
include 'includes/library-widget.php';

/**
 * Loading BP-specific stuff in the global scope will cause issues during activation and upgrades
 * Ensure that it's only loaded when BP is present.
 * See http://openlab.citytech.cuny.edu/redmine/issues/31
 */
function openlab_load_custom_bp_functions() {
	require( dirname( __FILE__ ) . '/wds-citytech-bp.php' );
	require( dirname( __FILE__ ) . '/includes/email.php' );
	require( dirname( __FILE__ ) . '/includes/groupmeta-query.php' );
	require( dirname( __FILE__ ) . '/includes/group-blogs.php' );
	require( dirname( __FILE__ ) . '/includes/group-types.php' );
	require( dirname( __FILE__ ) . '/includes/portfolios.php' );
	require( dirname( __FILE__ ) . '/includes/related-links.php' );
	require( dirname( __FILE__ ) . '/includes/search.php' );
}

add_action( 'bp_init', 'openlab_load_custom_bp_functions' );

global $wpdb;
//date_default_timezone_set( 'America/New_York' );

function wds_default_signup_avatar( $img ) {
	if ( false !== strpos( $img, 'mystery-man' ) ) {
		$img = "<img src='" . wds_add_default_member_avatar() . "' width='200' height='200'>";
	}

	return $img;
}
add_filter( 'bp_get_signup_avatar', 'wds_default_signup_avatar' );

//
//   This function creates an excerpt of the string passed to the length specified and
//   breaks on a word boundary
//
function wds_content_excerpt( $text, $text_length ) {
	return bp_create_excerpt( $text, $text_length );
}

/**
 * On activation, copies the BP first/last name profile field data into the WP 'first_name' and
 * 'last_name' fields.
 *
 * @todo This should probably be moved to a different hook. This $last_user lookup is hackish and
 *       may fail in some edge cases. I believe the hook bp_activated_user is correct.
 */
function wds_bp_complete_signup() {
	global $bp, $wpdb;

	$last_user = $wpdb->get_results( 'SELECT * FROM wp_users ORDER BY ID DESC LIMIT 1', 'ARRAY_A' );
	$user_id = $last_user[0]['ID'];
	$first_name = xprofile_get_field_data( 'First Name', $user_id );
	$last_name = xprofile_get_field_data( 'Last Name', $user_id );
	$update_user_first = update_user_meta( $user_id, 'first_name', $first_name );
	$update_user_last = update_user_meta( $user_id, 'last_name', $last_name );
}
add_action( 'bp_after_activation_page', 'wds_bp_complete_signup' );


//child theme privacy - if corresponding group is private or hidden restrict access to site
/* add_action( 'init','wds_check_blog_privacy' );
  function wds_check_blog_privacy() {
  global $bp, $wpdb, $blog_id, $user_ID;
  if ( $blog_id! = 1 ) {
  $wds_bp_group_id = get_option( 'wds_bp_group_id' );
  if ( $wds_bp_group_id ) {
  $group = new BP_Groups_Group( $wds_bp_group_id );
  $status = $group->status;
  if ( $status! = "public" ) {
  //check memeber
  if ( !is_user_member_of_blog( $user_ID, $blog_id ) ) {
  echo "<center><img src='http://openlab.citytech.cuny.edu/wp-content/mu-plugins/css/images/cuny-sw-logo.png'><h1>";
  echo "This is a private website, ";
  if ( $user_ID == 0 ) {
  echo "please login to gain access.";
  } else {
  echo "you do not have access.";
  }
  echo "</h1></center>";
  exit();
  }
  }
  }
  }
  } */

/**
 * On secondary sites, add our additional buttons to the site nav
 *
 * This function filters wp_page_menu, which is what shows up when no custom
 * menu has been selected. See cuny_add_group_menu_items() for the
 * corresponding method for custom menus.
 */
function my_page_menu_filter( $menu ) {
	global $bp, $wpdb;

	if ( strpos( $menu, 'Home' ) !== false ) {
		$menu = str_replace( 'Site Home', 'Home', $menu );
		$menu = str_replace( 'Home', 'Site Home', $menu );
	} else {
		$menu = str_replace( '<div class="menu"><ul>', '<div class="menu"><ul><li><a title="Site Home" href="' . site_url() . '">Site Home</a></li>', $menu );
	}
	$menu = str_replace( 'Site Site Home', 'Site Home', $menu );

	// Only say 'Home' on the ePortfolio theme
	// @todo: This will probably get extended to all sites
	$menu = str_replace( 'Site Home', 'Home', $menu );

	$wds_bp_group_id = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM {$bp->groups->table_name_groupmeta} WHERE meta_key = 'wds_bp_group_site_id' AND meta_value = %d", get_current_blog_id() ) );

	if ( $wds_bp_group_id ) {
		$group_type = ucfirst( groups_get_groupmeta( $wds_bp_group_id, 'wds_group_type' ) );
		$group = new BP_Groups_Group( $wds_bp_group_id, true );
		$menu_a = explode( '<ul>', $menu );
		$menu_a = array(
			$menu_a[0],
			'<ul>',
			'<li id="group-profile-link"><a title="Site" href="' . bp_get_root_domain() . '/groups/' . $group->slug . '/">' . $group_type . ' Profile</a></li>',
			$menu_a[1],
		);
		$menu = implode( '', $menu_a );
	}
	return $menu;
}
add_filter( 'wp_page_menu', 'my_page_menu_filter' );

//child theme menu filter to link to website
function cuny_add_group_menu_items( $items, $args ) {
	// The Sliding Door theme shouldn't get any added items
	// See http://openlab.citytech.cuny.edu/redmine/issues/772
	if ( 'custom-sliding-menu' == $args->theme_location ) {
		return $items;
	}

	if ( ! bp_is_root_blog() ) {
		// Only add the Home link if one is not already found
		// See http://openlab.citytech.cuny.edu/redmine/isues/1031
		$has_home = false;
		foreach ( $items as $item ) {
			if ( 'Home' === $item->title && trailingslashit( site_url() ) === trailingslashit( $item->url ) ) {
				$has_home = true;
				break;
			}
		}

		if ( ! $has_home ) {
			$post_args = new stdClass;
			$home_link = new WP_Post( $post_args );
			$home_link->title = 'Home';
			$home_link->url = trailingslashit( site_url() );
			$home_link->slug = 'home';
			$home_link->ID = 'home';
			$items = array_merge( array( $home_link ), $items );
		}

		$items = array_merge( cuny_group_menu_items(), $items );
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'cuny_add_group_menu_items', 10, 2 );

function cuny_group_menu_items() {
	global $bp, $wpdb;

	$items = array();

	$wds_bp_group_id = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM {$bp->groups->table_name_groupmeta} WHERE meta_key = 'wds_bp_group_site_id' AND meta_value = %d", get_current_blog_id() ) );

	if ( $wds_bp_group_id ) {
		$group_type = ucfirst( groups_get_groupmeta( $wds_bp_group_id, 'wds_group_type' ) );
		$group = new BP_Groups_Group( $wds_bp_group_id, true );

		$post_args = new stdClass;
		$profile_item = new WP_Post( $post_args );
		$profile_item->ID = 'group-profile-link';
		$profile_item->title = sprintf( '%s Profile', $group_type );
		$profile_item->slug = 'group-profile-link';
		$profile_item->url = bp_get_group_permalink( $group );

		$items[] = $profile_item;
	}

	return $items;
}

//Default BP Avatar Full
if ( ! defined( 'BP_AVATAR_FULL_WIDTH' ) ) {
	define( 'BP_AVATAR_FULL_WIDTH', 225 );
}

if ( ! defined( 'BP_AVATAR_FULL_HEIGHT' ) ) {
	define( 'BP_AVATAR_FULL_HEIGHT', 225 );
}

/**
 * Don't let child blogs use bp-default or a child thereof
 *
 * @todo Why isn't this done by network disabling BP Default and its child themes?
 * @todo Why isn't BP_DISABLE_ADMIN_BAR defined somewhere like bp-custom.php?
 */
function wds_default_theme() {
	global $wpdb, $blog_id;
	if ( $blog_id > 1 ) {
		if ( ! defined( 'BP_DISABLE_ADMIN_BAR' ) ) {
			define( 'BP_DISABLE_ADMIN_BAR', true );
		}
		$theme = get_option( 'template' );
		if ( 'bp-default' === $theme ) {
			switch_theme( 'twentyten', 'twentyten' );
			wp_redirect( home_url() );
			exit();
		}
	}
}
add_action( 'init', 'wds_default_theme' );

//register.php -hook for new div to show account type fields
add_action( 'bp_after_signup_profile_fields', 'wds__bp_after_signup_profile_fields' );

function wds__bp_after_signup_profile_fields() {
	?>
	<div class="editfield"><div id="wds-account-type" aria-live="polite"></div></div>
	<?php
}

function wds_registration_ajax() {
	wp_print_scripts( array( 'sack' ) );
	$sack = 'var isack = new sack( "' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin-ajax.php" );';
	$loading = '<img src="' . get_bloginfo( 'template_directory' ) . '/_inc/images/ajax-loader.gif">';
	?>
	<?php
}
add_action( 'wp_head', 'wds_registration_ajax' );

function wds_load_default_account_type() {
	$return = '<script type="text/javascript">';

	$account_type = isset( $_POST['field_2'] ) ? $_POST['field_2'] : '';
	$type = '';
	$selected_index = '';

	if ( 'Student' === $account_type ) {
		$type = 'Student';
		$selected_index = 1;
	}

	if ( 'Faculty' === $account_type ) {
		$type = 'Faculty';
		$selected_index = 2;
	}

	if ( 'Staff' === $account_type ) {
		$type = 'Staff';
		$selected_index = 3;
	}

	if ( $type && $selected_index ) {
		$return .= 'var select_box=document.getElementById( \'field_2\' );';
		$return .= 'select_box.selectedIndex = ' . $selected_index . ';';
	}
	$return .= '</script>';
	echo $return;
}
add_action( 'bp_after_registration_submit_buttons', 'wds_load_default_account_type' );

function wds_load_account_type() {
	$return = '';

	$account_type = $_POST['account_type'];
	$post_data = isset( $_POST['post_data'] ) ? wp_unslash( $_POST['post_data'] ) : array();

	if ( $account_type ) {
		$return .= '<div class="sr-only">' . $account_type . ' selected.</div>' . wds_get_register_fields( $account_type, $post_data );
	} else {
		$return = 'Zvolte typ účtu.';
	}
        //@to-do: determine why this is here, and if it can be deprecated
	//$return = str_replace( "'", "\'", $return );
	die( $return );
}
add_action( 'wp_ajax_wds_load_account_type', 'wds_load_account_type' );
add_action( 'wp_ajax_nopriv_wds_load_account_type', 'wds_load_account_type' );

function wds_bp_profile_group_tabs() {
	global $bp, $group_name;
	if ( ! $groups = wp_cache_get( 'xprofile_groups_inc_empty', 'bp' ) ) {
		$groups = BP_XProfile_Group::get( array( 'fetch_fields' => true ) );
		wp_cache_set( 'xprofile_groups_inc_empty', $groups, 'bp' );
	}

	if ( empty( $group_name ) ) {
		$group_name = bp_profile_group_name( false );
	}

	for ( $i = 0; $i < count( $groups ); $i++ ) {
		if ( $group_name == $groups[ $i ]->name ) {
			$selected = ' class="current"';
		} else {
			$selected = '';
		}

		$account_type = bp_get_profile_field_data( 'field=Account Type' );
		if ( $groups[ $i ]->fields ) {
			echo '<li' . $selected . '><a href="' . $bp->displayed_user->domain . $bp->profile->slug . '/edit/group/' . $groups[ $i ]->id . '">' . esc_attr( $groups[ $i ]->name ) . '</a></li>';
		}
	}

	do_action( 'xprofile_profile_group_tabs' );
}

//Group Stuff
function wds_groups_ajax() {
	global $bp;

	if ( ! bp_is_active( 'groups' ) ) {
		return;
	}

	wp_print_scripts( array( 'sack' ) );
	$sack = 'var isack = new sack( "' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin-ajax.php" );';
	$loading = '<img src="' . get_bloginfo( 'template_directory' ) . '/_inc/images/ajax-loader.gif">';
	?>

	<script type="text/javascript">
		//<![CDATA[
		function wds_load_group_type(id) {
			<?php echo $sack; ?>
			var select_box = document.getElementById(id);
			var selected_index = select_box.selectedIndex;
			var selected_value = select_box.options[selected_index].value;
			isack.execute = 1;
			isack.method = 'POST';
			isack.setVar("action", "wds_load_group_type");
			isack.setVar("group_type", selected_value);
			isack.runAJAX();
			return true;
		}

		function wds_load_group_departments(id) {
			<?php
			$group = bp_get_current_group_id();

			//get group type
			if ( ! empty( $_GET['type'] ) ) {
				$group_type = $_GET['type'];
			} else {
				$group_type = 'club';
			}


			echo $sack;
			?>
			var schools = "0";
			for ( school in OLGroupCreate.schools ) {
				if ( ! OLGroupCreate.schools.hasOwnProperty( school ) ) {
					continue;
				}

				var schoolElId = 'school_' + school;
				if ( document.getElementById( schoolElId ).checked ) {
					schools = schools + "," + document.getElementById( schoolElId ).value;
				}
			}
			var group_type = jQuery('input[name="group_type"]').val();
			isack.execute = 1;
			isack.method = 'POST';
			isack.setVar("action", "wds_load_group_departments");
			isack.setVar("schools", schools);
			isack.setVar("group", "<?php echo $group; ?>");
			isack.setVar("is_group_create", "<?php echo intval( bp_is_group_create() ) ?>");
			isack.setVar("group_type", "<?php echo $group_type; ?>");
			isack.runAJAX();
			return true;
		}
	//]]>
	</script>
	<?php
}
add_action( 'wp_head', 'wds_groups_ajax' );

function wds_load_group_departments() {
	global $wpdb, $bp;
	$group = $_POST['group'];
	$schools = $_POST['schools'];
	$group_type = $_POST['group_type'];
	$is_group_create = (bool) $_POST['is_group_create'];
	$schools = str_replace( '0,', '', $schools );
	$schools = explode( ',', $schools );

	$schools_list = '';
	$schools_list_ary = array();

	$schools_canonical = openlab_get_school_list();
	foreach ( $schools as $school ) {
		if ( isset( $schools_canonical[ $school ] ) ) {
			array_push( $schools_list_ary, $schools_canonical[ $school ] );
		}
	}

	$schools_list = implode( ', ', $schools_list_ary );

	$departments_canonical = openlab_get_department_list();

	// We want to prefill the School and Dept fields, which means we have
	// to prefetch the dept field and figure out School backward
	if ( 'portfolio' == strtolower( $group_type ) && $is_group_create ) {
		$account_type = strtolower( bp_get_profile_field_data( array(
			'field' => 'Account Type',
			'user_id' => bp_loggedin_user_id(),
		) ) );
		$dept_field = 'student' == $account_type ? 'Major Program of Study' : 'Department';

		$wds_departments = (array) bp_get_profile_field_data( array(
			'field' => $dept_field,
			'user_id' => bp_loggedin_user_id(),
		) );

		foreach ( $wds_departments as $d ) {
			foreach ( $departments_canonical as $the_school => $the_depts ) {
				if ( in_array( $d, $the_depts, true ) ) {
					$schools[] = $the_school;
				}
			}
		}
	}

	$departments = array();
	foreach ( $schools as $school ) {
		if ( isset( $departments_canonical[ $school ] ) ) {
			$departments = array_merge( $departments, $departments_canonical[ $school ] );
		}
	}
	sort( $departments );

	if ( 'portfolio' == strtolower( $group_type ) && $is_group_create ) {
		$wds_departments = (array) bp_get_profile_field_data( array(
			'field' => $dept_field,
			'user_id' => bp_loggedin_user_id(),
		) );
	} else {
		$wds_departments = groups_get_groupmeta( $group, 'wds_departments' );
		$wds_departments = explode( ',', $wds_departments );
	}

	$return = '<div class="department-list-container checkbox-list-container"><div class="sr-only">' . $schools_list . ' selected</div>';
	foreach ( $departments as $i => $value ) {
		$checked = '';
		if ( in_array( $value, $wds_departments ) ) {
			$checked = 'checked';
		}
		$return .= "<label class='passive block'><input type='checkbox' class='wds-department' name='wds_departments[]' value='$value' $checked> $value</label>";
	}

	$return .= '</div>';
	$return = str_replace( "'", "\'", $return );
	die( "document.getElementById( 'departments_html' ).innerHTML='$return'" );
}
add_action( 'wp_ajax_wds_load_group_departments', 'wds_load_group_departments' );
add_action( 'wp_ajax_nopriv_wds_load_group_departments', 'wds_load_group_departments' );

/**
 * Get a list of schools
 */
function openlab_get_school_list() {
	return array(
		/*
		'tech' => 'Technology & Design',
		'studies' => 'Professional Studies',
		'arts' => 'Arts & Sciences',
		'other' => 'Other',*/
		'procesy'=> 'Procesy',
		'funkce' => 'Funkce',
		'navigace' => 'Navigace',
		'typ-organizace' => 'Typ organizace',
		'potreby' => 'Potřeby',
		'az-seznam' => 'A-Z seznam',
		'financni-prostredky' => 'Finanční prostředky',
		'vzdelani-a-dovednosti' => 'Vzdělání a dovednosti',
		'zdravi-a-pece' => 'Zdraví a péče',
		'bydleni' => 'Bydlení',
		'sluzby' => 'Služby',
		'kriminalita-a-bezpecnost' => 'Kriminalita a bezpečnost',
		'zivotni-prostredi' => 'Životní prostředí',
		'komunita' => 'Komunita',
		'zivotni-podminky' => 'Životní podmínky',
		'rovnost-a-postaveni' => 'Rovnost a postavení',
		'zamestnanost-a-ekonomika' => 'Zaměstnanost a ekonomika',
		'pristup-k-digitalnim-technologiim' => 'Přístup k digitálním technologiím',
		'vyloucene-skupiny' => 'Vyloučené skupiny',
		'rodina-pratele-pecovatele' => 'Rodina, přátelé a pečovatelé',
		'stycni-pracovnici' => 'Styční pracovníci',
		'lokalni-organy' => 'Lokální orgány',
		'organizace-poskytujici-sluzby' => 'Organizace poskytující služby',
		'lokalni-partneri' => 'Lokální partneři',
		'ekonomika-a-spolecnost' => 'Ekonomika a společnost',
	);
}

/**
 * Get a list of departments
 *
 * @param str Optional. Leave out to get all departments
 */
function openlab_get_department_list( $school = '', $label_type = 'full' ) {
	// Sanitize school name
	$schools = openlab_get_school_list();
	if ( isset( $schools[ $school ] ) ) {
		$school = $school;
	} elseif ( in_array( $school, $schools ) ) {
		$school = array_search( $school, $schools );
	}

	$all_departments = array(
/*		'tech' => array(
			'architectural-technology' => array(
				'label' => 'Architectural Technology',
			),
			'communication-design' => array(
				'label' => 'Communication Design',
			),
			'computer-engineering-technology' => array(
				'label' => 'Computer Engineering Technology',
			),
			'computer-systems-technology' => array(
				'label' => 'Computer Systems Technology',
			),
			'construction-management-and-civil-engineering-technology' => array(
				'label' => 'Construction Management and Civil Engineering Technology',
				'short_label' => 'Construction & Civil Engineering Tech',
			),
			'electrical-and-telecommunications-engineering-technology' => array(
				'label' => 'Electrical and Telecommunications Engineering Technology',
				'short_label' => 'Electrical & Telecom Engineering Tech',
			),
			'entertainment-technology' => array(
				'label' => 'Entertainment Technology',
			),
			'environmental-control-technology' => array(
				'label' => 'Environmental Control Technology',
			),
			'mechanical-engineering-technology' => array(
				'label' => 'Mechanical Engineering Technology',
			),
		),
		'studies' => array(
			'business' => array(
				'label' => 'Business',
			),
			'career-and-technology-teacher-education' => array(
				'label' => 'Career and Technology Teacher Education',
			),
			'dental-hygiene' => array(
				'label' => 'Dental Hygiene',
			),
			'health-services-administration' => array(
				'label' => 'Health Services Administration',
			),
			'hospitality-management' => array(
				'label' => 'Hospitality Management',
			),
			'human-services' => array(
				'label' => 'Human Services',
			),
			'law-and-paralegal-studies' => array(
				'label' => 'Law and Paralegal Studies',
			),
			'nursing' => array(
				'label' => 'Nursing',
			),
			'radiologic-technology-and-medical-imaging' => array(
				'label' => 'Radiologic Technology and Medical Imaging',
			),
			'restorative-dentistry' => array(
				'label' => 'Restorative Dentistry',
			),
			'vision-care-technology' => array(
				'label' => 'Vision Care Technology',
			),
		),
		'arts' => array(
			'african-american-studies' => array(
				'label' => 'African American Studies',
			),
			'biological-sciences' => array(
				'label' => 'Biological Sciences',
			),
			'biomedical-informatics' => array(
				'label' => 'Biomedical Informatics',
			),
			'chemistry' => array(
				'label' => 'Chemistry',
			),
			'english' => array(
				'label' => 'English',
			),
			'humanities' => array(
				'label' => 'Humanities',
			),
			'library' => array(
				'label' => 'Library',
			),
			'mathematics' => array(
				'label' => 'Mathematics',
			),
			'professional-and-technical-writing' => array(
				'label' => 'Professional and Technical Writing',
			),
			'physics' => array(
				'label' => 'Physics',
			),
			'social-science' => array(
				'label' => 'Social Science',
			),
		),
		'other' => array(
			'clip' => array(
				'label' => 'CLIP',
			),
		),*/
		'procesy' => array(
			'bezpecnost-a-ochrana-zdravi-pri-praci-a-socialni-sluzby-pro-zamestnance' => array('label' => 'Bezpečnost a ochrana zdraví při práci a sociální služby pro zaměstnance',),
'hodnoceni-vykonnosti-zamestnancu' => array('label' => 'Hodnocení výkonnosti zaměstnanců',),
'interpretace-pravnich-predpisu' => array('label' => 'Interpretace právních předpisů',),
'kontrola-nebo-provereni-(nemovitost,-majetek,-ucty,-dokumenty-nebo-osoby)' => array('label' => 'Kontrola nebo prověření (nemovitost, majetek, účty, dokumenty nebo osoby)',),
'kontrola-rozhodnuti' => array('label' => 'Kontrola rozhodnutí',),
'likvidace-majetku' => array('label' => 'Likvidace majetku',),
'nabor,-vyber-a-prijem-novych-zamestnancu' => array('label' => 'Nábor, výběr a příjem nových zaměstnanců',),
'nerealizace-akci-a-strategie' => array('label' => 'Nerealizace akcí a strategie',),
'odpoved-na-zadost-obcana-zadajici-informaci-nebo-radu' => array('label' => 'Odpověď na žádost občana žádající informaci nebo radu',),
'opravy-nebo-udrzba-majetku' => array('label' => 'Opravy nebo údržba majetku',),
'poskytovani-informaci-o-aktualnim-stavu-sluzeb' => array('label' => 'Poskytování informací o aktuálním stavu služeb',),
'poskytovani-informaci-o-poskytovanych-o-sluzbach' => array('label' => 'Poskytování informací o poskytovaných o službách',),
'posouzeni-zakaznika-nebo-majektu' => array('label' => 'Posouzení zákazníka nebo majektu',),
'procesy' => array('label' => 'Procesy',),
'prosazovani-dodrzovani-rozhodnuti,-pravidel,-predpisu-a-zakonu' => array('label' => 'Prosazování dodržování rozhodnutí, pravidel, předpisů a zákonů',),
'provadejte-planovani-a-nasazeni-provoznich-zdroju' => array('label' => 'Provádějte plánování a nasazení provozních zdrojů',),
'provadeni-plateb-(vydaje)' => array('label' => 'Provádění plateb (výdaje)',),
'prijem-nebo-zaznam-zadosti-o-sluzbu' => array('label' => 'Příjem nebo záznam žádosti o službu',),
'prijem-plateb-(prijmy)' => array('label' => 'Příjem plateb (příjmy)',),
'reakce-na-udalost-tykajici-se-majetku' => array('label' => 'Reakce na událost týkající se majetku',),
'realizace-provoznich-sluzeb' => array('label' => 'Realizace provozních služeb',),
'registrace,-overovani-nebo-autorizace-uzivatelu-sluzeb' => array('label' => 'Registrace, ověřování nebo autorizace uživatelů služeb',),
'rezervace-mistnosti-nebo-zarizeni' => array('label' => 'Rezervace místnosti nebo zařízení',),
'reseni-pochval-na-sluzby' => array('label' => 'Řešení pochval na služby',),
'reseni-stiznosti-na-sluzby' => array('label' => 'Řešení stížností na služby',),
'reseni-zmen-okolnosti' => array('label' => 'Řešení změn okolností',),
'rizeni-rizik-a-planovani-mimoradnych-udalosti' => array('label' => 'Řízení rizik a plánování mimořádných událostí',),
'sprava-a-podpora-projektu-a-programu' => array('label' => 'Správa a podpora projektů a programů',),
'sprava-absenci-zamestnancu' => array('label' => 'Správa absencí zaměstnanců',),
'sprava-discipliny-zamestnancu' => array('label' => 'Správa disciplíny zaměstnanců',),
'sprava-mezd-a-davek' => array('label' => 'Správa mezd a dávek',),
'sprava-odchodu-ze-zamestnani' => array('label' => 'Správa odchodů ze zaměstnání',),
'sprava-rozpoctu' => array('label' => 'Správa rozpočtů',),
'sprava-sluzeb-souvisejicich-se-zadavanim-verejnych-zakazek-a-jejich-implementaci' => array('label' => 'Správa služeb souvisejících se zadáváním veřejných zakázek a  jejich implementací',),
'sprava-stiznosti-zamestnancu' => array('label' => 'Správa stížností zaměstnanců',),
'sprava-volnych-pracovnich-mist' => array('label' => 'Správa volných pracovních míst',),
'sprava-vykonnosti-organizace' => array('label' => 'Správa výkonnosti organizace',),
'stanoveni-naroku-nebo-pozadavku-zadatele-o-sluzbu' => array('label' => 'Stanovení nároku nebo požadavku žadatele o službu',),
'strategicky-plan' => array('label' => 'Strategický plán',),
'skoleni-a-rozvoj-personalu' => array('label' => 'Školení a rozvoj personálu',),
'udelovani-licenci-/-pristupu-/-opravneni' => array('label' => 'Udělování licencí / přístupů / oprávnění',),
'vedeni-financniho-ucetnictvi' => array('label' => 'Vedení finančního účetnictví',),
'vydavani-rozhodnuti-/-usneseni' => array('label' => 'Vydávání rozhodnutí / usnesení',),
'vyhodnocovani-prace-na-jednotlivych-pracovnich-pozicich' => array('label' => 'Vyhodnocování práce na jednotlivých pracovních pozicích',),
'vymahani-pohledavek' => array('label' => 'Vymáhání pohledávek',),
'vyrizovani-nebo-administrace-pozadavku-na-sluzbu-nebo-zaznamu' => array('label' => 'Vyřizování nebo administrace požadavků na službu nebo záznamů',),
'zabezpeceni-majetku' => array('label' => 'Zabezpečení majetku',),
'zapojeni-a-konzultace-s-obcany-a-dalsimi-osobami' => array('label' => 'Zapojení a konzultace s občany a dalšími osobami',),
'zasahy-ke-zmenam-vysledku' => array('label' => 'Zásahy ke změnám výsledků',),
'zpracovavani-zasad-a-pravidel' => array('label' => 'Zpracovávání zásad a pravidel',),
),
		'funkce' => array(
		'alkohol-a-zabava' => array('label' => 'Alkohol a zábava',),
		'azyl-a-pristehovalectvi' => array('label' => 'Azyl a přistěhovalectví',),
		'bezdomovectvi-a-prevence' => array('label' => 'Bezdomovectví a prevence',),
		'bezpecnost-na-silnicich' => array('label' => 'Bezpečnost na silnicích',),
		'bezpecnost-sousedstvi' => array('label' => 'Bezpečnost sousedství',),
		'bezpecnosti-spolecenstvi' => array('label' => 'Bezpečnosti Společenství',),
		'brzy-roky-a-pece-o-deti' => array('label' => 'Brzy roky a péče o děti',),
		'bydleni' => array('label' => 'Bydlení',),
		'bytova-pece-a-bydleni' => array('label' => 'Bytová péče a bydlení',),
		'cestovni-ruch' => array('label' => 'Cestovní ruch',),
		'cyklistika' => array('label' => 'Cyklistika',),
		'clenstvi-v-knihovne' => array('label' => 'Členství v knihovně',),
		'dalnice' => array('label' => 'Dálnice',),
		'dalnicni-politika' => array('label' => 'Dálniční politika',),
		'dedictvi-a-krajina' => array('label' => 'Dědictví a krajina',),
		'demokracie' => array('label' => 'Demokracie',),
		'demokraticke-sluzby' => array('label' => 'Demokratické služby',),
		'deti-a-rodinna-pece' => array('label' => 'Děti a rodinná péče',),
		'dobre-zivotni-podminky-zvirat' => array('label' => 'Dobré životní podmínky zvířat',),
		'dobrovolnictvi-a-dobrovolnicke-organizace' => array('label' => 'Dobrovolnictví a dobrovolnické organizace',),
		'domaci-odpad' => array('label' => 'Domácí odpad',),
		'domovy-pece,-podporovane-a-chranene-bydleni' => array('label' => 'Domovy péče, podporované a chráněné bydlení',),
		'doprava' => array('label' => 'Doprava',),
		'doprava-a-dalnice' => array('label' => 'Doprava a dálnice',),
		'doprava-spolecenstvi' => array('label' => 'Doprava Společenství',),
		'dopravni-systemy' => array('label' => 'Dopravní systémy',),
		'environmentalni-zdravi' => array('label' => 'Environmentální zdraví',),
		'finance' => array('label' => 'Finance',),
		'financovani-bydleni' => array('label' => 'Financování bydlení',),
		'funkce' => array('label' => 'Funkce',),
		'granty-a-pomoc' => array('label' => 'Granty a pomoc',),
		'granty-na-bydleni' => array('label' => 'Granty na bydlení',),
		'granty-pro-deti-a-mladez' => array('label' => 'Granty pro děti a mládež',),
		'granty-pro-zdravotne-postizene-zarizeni' => array('label' => 'Granty pro zdravotně postižené zařízení',),
		'granty-spolecenstvi' => array('label' => 'Granty Společenství',),
		'hazardni-hry-a-loterie' => array('label' => 'Hazardní hry a loterie',),
		'informacni-komunikacni-technologie' => array('label' => 'Informační komunikační technologie',),
		'interni-provoz' => array('label' => 'Interní provoz',),
		'jidlo' => array('label' => 'Jídlo',),
		'kariera-a-zamestnanost' => array('label' => 'Kariéra a zaměstnanost',),
		'knihovny' => array('label' => 'Knihovny',),
		'komercni-nemovitosti' => array('label' => 'Komerční nemovitosti',),
		'komercni-odpady' => array('label' => 'Komerční odpady',),
		'komunikace-a-publicita' => array('label' => 'Komunikace a publicita',),
		'komunitnich-center-a-zarizeni' => array('label' => 'Komunitních center a zařízení',),
		'kontrola-znecisteni' => array('label' => 'Kontrola znečištění',),
		'konzultace' => array('label' => 'Konzultace',),
		'kriminalni-spravedlnost' => array('label' => 'Kriminální spravedlnost',),
		'kurikulum-a-politika' => array('label' => 'Kurikulum a politika',),
		'lekari,-lekari-a-nemocnice' => array('label' => 'Lékaři, lékaři a nemocnice',),
		'licence,-povoleni-a-opravneni' => array('label' => 'Licence, povolení a oprávnění',),
		'lidske-zdroje' => array('label' => 'Lidské zdroje',),
		'mimoskolni-aktivity' => array('label' => 'Mimoškolní aktivity',),
		'mistni-historie-a-dedictvi' => array('label' => 'Místní historie a dědictví',),
		'muzea-a-galerie' => array('label' => 'Muzea a galerie',),
		'nabidky-a-smlouvy' => array('label' => 'Nabídky a smlouvy',),
		'nabozenstvi-a-kultura' => array('label' => 'Náboženství a kultura',),
		'nakladani-s-odpady' => array('label' => 'Nakládání s odpady',),
		'nebezpecne-materialy' => array('label' => 'Nebezpečné materiály',),
		'nebezpecny-odpad' => array('label' => 'Nebezpečný odpad',),
		'nizkoprijmove-vyhody' => array('label' => 'Nízkopříjmové výhody',),
		'nouzove-situace' => array('label' => 'Nouzové situace',),
		'obchod-a-zamestnanost' => array('label' => 'Obchod a zaměstnanost',),
		'obchodni-cinnost' => array('label' => 'Obchodní činnost',),
		'obchodni-normy' => array('label' => 'Obchodní normy',),
		'obchodni-poradenstvi-a-podpora' => array('label' => 'Obchodní poradenství a podpora',),
		'obchodni-sazby' => array('label' => 'Obchodní sazby',),
		'odpad-a-znecisteni' => array('label' => 'Odpad a znečištění',),
		'odpoved-na-incident' => array('label' => 'Odpověď na incident',),
		'ohniva-obsluha' => array('label' => 'Ohnivá obsluha',),
		'ochrana-udaju-a-svoboda-informaci' => array('label' => 'Ochrana údajů a svoboda informací',),
		'ochrana-zivotniho-prostredi' => array('label' => 'Ochrana životního prostředí',),
		'opravneni-a-souhlasy' => array('label' => 'Oprávnění a souhlasy',),
		'parkoviste' => array('label' => 'Parkoviště',),
		'parky-a-otevrene-prostory' => array('label' => 'Parky a otevřené prostory',),
		'pece' => array('label' => 'Péče',),
		'pece-o-ulice-a-cisteni' => array('label' => 'Péče o ulice a čištění',),
		'planovaci-politika' => array('label' => 'Plánovací politika',),
		'planovaci-sluzby' => array('label' => 'Plánovací služby',),
		'planovani-a-rizeni-budov' => array('label' => 'Plánování a řízení budov',),
		'pobrezni-cara' => array('label' => 'Pobřežní čára',),
		'podival-se-na-deti' => array('label' => 'Podíval se na děti',),
		'podnikani-a-trhy' => array('label' => 'Podnikání a trhy',),
		'podnikove-granty' => array('label' => 'Podnikové granty',),
		'podniky' => array('label' => 'Podniky',),
		'podpora-pro-deti-a-mladez' => array('label' => 'Podpora pro děti a mládež',),
		'podpora-spolecenstvi' => array('label' => 'Podpora Společenství',),
		'podpora-vzdelavani' => array('label' => 'Podpora vzdělávání',),
		'podporovani-a-prijeti' => array('label' => 'Podporování a přijetí',),
		'pohrby-a-kremace' => array('label' => 'Pohřby a kremace',),
		'policejni-sluzby' => array('label' => 'Policejní služby',),
		'politika-bydleni' => array('label' => 'Politika bydlení',),
		'politika-odpadu' => array('label' => 'Politika odpadů',),
		'pomoc-a-poradenstvi-pro-dospele' => array('label' => 'Pomoc a poradenství pro dospělé',),
		'poradenstvi-a-socialni-zabezpeceni' => array('label' => 'Poradenství a sociální zabezpečení',),
		'poradenstvi-a-vyhody' => array('label' => 'Poradenství a výhody',),
		'poradenstvi-v-oblasti-bydleni' => array('label' => 'Poradenství v oblasti bydlení',),
		'poskytovani-alternativniho-vzdelavani' => array('label' => 'Poskytování alternativního vzdělávání',),
		'pouziva' => array('label' => 'Používá',),
		'pozarni-bezpecnost' => array('label' => 'Požární bezpečnost',),
		'pozarni-vzdelavani-a-certifikace' => array('label' => 'Požární vzdělávání a certifikace',),
		'pravni' => array('label' => 'Právní',),
		'provoz-pristavu-a-pristavu' => array('label' => 'Provoz přístavu a přístavu',),
		'pridelovani-bydleni' => array('label' => 'Přidělování bydlení',),
		'prijeti-do-skoly' => array('label' => 'Přijetí do školy',),
		'prispevky-na-peci-a-invaliditu' => array('label' => 'Příspěvky na péči a invaliditu',),
		'pristavni-a-pristavni-zarizeni' => array('label' => 'Přístavní a přístavní zařízení',),
		'pristavy-a-pristavy' => array('label' => 'Přístavy a přístavy',),
		'pristup-a-aktualizace-osobnich-informaci' => array('label' => 'Přístup a aktualizace osobních informací',),
		'rada-a-komunitni-bydleni' => array('label' => 'Rada a komunitní bydlení',),
		'recyklace' => array('label' => 'Recyklace',),
		'reference-a-vyzkum' => array('label' => 'Reference a výzkum',),
		'regenerace' => array('label' => 'Regenerace',),
		'registrace' => array('label' => 'Registrace',),
		'rodinne-davky' => array('label' => 'Rodinné dávky',),
		'rovnost-a-rozmanitost' => array('label' => 'Rovnost a rozmanitost',),
		'rozvoj-a-podpora-zaku' => array('label' => 'Rozvoj a podpora žáků',),
		'rizeni-budovy' => array('label' => 'Řízení budovy',),
		'rizeni-mestskych-center' => array('label' => 'Řízení městských center',),
		'rizeni-spolecnosti' => array('label' => 'Řízení společnosti',),
		'rizeni-vyvoje' => array('label' => 'Řízení vývoje',),
		'silnice-a-ulice' => array('label' => 'Silnice a ulice',),
		'silnicni-znacky-a-znacky' => array('label' => 'Silniční značky a značky',),
		'sluzby-bydleni' => array('label' => 'Služby bydlení',),
		'sluzby-namorni-a-vodni-dopravy' => array('label' => 'Služby námořní a vodní dopravy',),
		'sluzby-pro-dospele' => array('label' => 'Služby pro dospělé',),
		'smrt-a-vyhody' => array('label' => 'Smrt a výhody',),
		'socialni-pece-a-vzdelavani' => array('label' => 'Sociální péče a vzdělávání',),
		'socialni-pece-pro-deti-a-mladez' => array('label' => 'Sociální péče pro děti a mládež',),
		'socialni-pece-pro-dospele' => array('label' => 'Sociální péče pro dospělé',),
		'sportovni-a-sportovni-zarizeni' => array('label' => 'Sportovní a sportovní zařízení',),
		'statistiky-a-scitani-lidu' => array('label' => 'Statistiky a sčítání lidu',),
		'stavebni-a-stavebni-prace' => array('label' => 'Stavební a stavební práce',),
		'stezky,-prijezdove-cesty-a-jizdni-drahy' => array('label' => 'Stezky, příjezdové cesty a jízdní dráhy',),
		'stiznosti-a-komplimenty' => array('label' => 'Stížnosti a komplimenty',),
		'skolni-a-specialni-knihovny' => array('label' => 'Školní a speciální knihovny',),
		'skoly' => array('label' => 'Školy',),
		'taxi-a-soukrome-pronajmy' => array('label' => 'Taxi a soukromé pronájmy',),
		'trestne-ciny-mladeze' => array('label' => 'Trestné činy mládeže',),
		'trhy' => array('label' => 'Trhy',),
		'udalosti-a-vystavy' => array('label' => 'Události a výstavy',),
		'udrzba-silnic' => array('label' => 'Údržba silnic',),
		'umeni-a-zabava' => array('label' => 'Umění a zábava',),
		'venkovstvi-a-hospodareni' => array('label' => 'Venkovství a hospodaření',),
		'verejna-bezpecnost' => array('label' => 'Veřejná bezpečnost',),
		'verejna-doprava' => array('label' => 'Veřejná doprava',),
		'verejne-zdravi' => array('label' => 'Veřejné zdraví',),
		'vice-domu-s-obsazenim' => array('label' => 'Více domů s obsazením',),
		'vlada,-obcane-a-prava' => array('label' => 'Vláda, občané a práva',),
		'vnitrozemske-vodni-cesty' => array('label' => 'Vnitrozemské vodní cesty',),
		'vodni-aktivity' => array('label' => 'Vodní aktivity',),
		'volnocasove-aktivity' => array('label' => 'Volnočasové aktivity',),
		'volny-cas-a-kultura' => array('label' => 'Volný čas a kultura',),
		'vybaveni' => array('label' => 'Vybavení',),
		'vybaveni-a-podpurne-sluzby' => array('label' => 'Vybavení a podpůrné služby',),
		'vyhody' => array('label' => 'Výhody',),
		'vyhody-vytapeni-a-bydleni' => array('label' => 'Výhody vytápění a bydlení',),
		'vykonnost' => array('label' => 'Výkonnost',),
		'vysokoskolske-vzdelani' => array('label' => 'Vysokoškolské vzdělání',),
		'vzdelavani-a-uceni' => array('label' => 'Vzdělávání a učení',),
		'vzdelavani-dospelych-a-celozivotni-uceni' => array('label' => 'Vzdělávání dospělých a celoživotní učení',),
		'zadavani-zakazek' => array('label' => 'Zadávání zakázek',),
		'zachovani-a-udrzitelnost' => array('label' => 'Zachování a udržitelnost',),
		'zbozi-a-sluzby' => array('label' => 'Zboží a služby',),
		'zdaneni' => array('label' => 'Zdanění',),
		'zdravi-a-bezpecnost' => array('label' => 'Zdraví a bezpečnost',),
		'zdravi-a-blaho-ve-skole' => array('label' => 'Zdraví a blaho ve škole',),
		'zdravotni-a-socialni-pece' => array('label' => 'Zdravotní a sociální péče',),
		'zlepseni-a-opravy' => array('label' => 'Zlepšení a opravy',),
		'zvirata' => array('label' => 'Zvířata',),
		'zvlastni-vzdelavaci-potreby' => array('label' => 'Zvláštní vzdělávací potřeby',),
		'zvoleni-clenove' => array('label' => 'Zvolení členové',),
	),
	'navigace' => array(
'bezdomovectvi' => array('label' => 'Bezdomovectví',),
'bezpecnost-domova' => array('label' => 'Bezpečnost domova',),
'bezpecnost-na-silnicich' => array('label' => 'Bezpečnost na silnicích',),
'bezpecnosti-a-zlocinu-spolecenstvi' => array('label' => 'Bezpečnosti a zločinu Společenství',),
'bydleni' => array('label' => 'Bydlení',),
'bydleni-a-finance' => array('label' => 'Bydlení a finance',),
'cestovni-ruch-a-cestovani' => array('label' => 'Cestovní ruch a cestování',),
'cestovni-schemata' => array('label' => 'Cestovní schémata',),
'cikani-a-cestujici' => array('label' => 'Cikáni a cestující',),
'cyklistika' => array('label' => 'Cyklistika',),
'dalsi-a-vyssi-vzdelavani' => array('label' => 'Další a vyšší vzdělávání',),
'dane-a-prinosy-rady' => array('label' => 'Daně a přínosy Rady',),
'deti-a-rodiny' => array('label' => 'Děti a rodiny',),
'detska-socialni-pece' => array('label' => 'Dětská sociální péče',),
'dobrovolnictvi' => array('label' => 'Dobrovolnictví',),
'domaci-nasili' => array('label' => 'Domácí násilí',),
'domaci-pece' => array('label' => 'Domácí péče',),
'doprava-a-ulice' => array('label' => 'Doprava a ulice',),
'doprava-spolecenstvi' => array('label' => 'Doprava Společenství',),
'dovednosti-a-skoleni' => array('label' => 'Dovednosti a školení',),
'energetika-a-zmena-klimatu' => array('label' => 'Energetika a změna klimatu',),
'environmentalni-zdravi' => array('label' => 'Environmentální zdraví',),
'granty-spolecenstvi' => array('label' => 'Granty Společenství',),
'hledani-nemovitosti' => array('label' => 'Hledání nemovitostí',),
'chuze' => array('label' => 'Chůze',),
'imigrace-a-azyl' => array('label' => 'Imigrace a azyl',),
'karierni-poradenstvi' => array('label' => 'Kariérní poradenství',),
'knihovny' => array('label' => 'Knihovny',),
'komercni-odpady' => array('label' => 'Komerční odpady',),
'komercni-pozemky-a-nemovitosti' => array('label' => 'Komerční pozemky a nemovitosti',),
'kontaktujte-radu' => array('label' => 'Kontaktujte radu',),
'konzultace-a-zpetna-vazba' => array('label' => 'Konzultace a zpětná vazba',),
'licence-na-nebezpecne-materialy' => array('label' => 'Licence na nebezpečné materiály',),
'licence-na-obchodni-a-trzni-produkty' => array('label' => 'Licence na obchodní a tržní produkty',),
'licence-na-zneskodnovani-odpadu-a-znecisteni' => array('label' => 'Licence na zneškodňování odpadu a znečištění',),
'mistni-ekonomika' => array('label' => 'Místní ekonomika',),
'mistni-historie-a-dedictvi' => array('label' => 'Místní historie a dědictví',),
'mistni-informace-a-statistiky' => array('label' => 'Místní informace a statistiky',),
'misto-konani' => array('label' => 'Místo konání',),
'muzea-a-galerie' => array('label' => 'Muzea a galerie',),
'nabozenstvi,-viry-a-presvedceni' => array('label' => 'Náboženství, víry a přesvědčení',),
'narozeni,-umrti-a-obrady' => array('label' => 'Narození, úmrtí a obřady',),
'navigace' => array('label' => 'Navigace',),
'nouzove-situace' => array('label' => 'Nouzové situace',),
'obecni-dan' => array('label' => 'obecní daň',),
'obchod-a-ekonomika' => array('label' => 'Obchod a ekonomika',),
'obchodni-normy' => array('label' => 'Obchodní normy',),
'obchodni-sazby' => array('label' => 'Obchodní sazby',),
'obchodovani-s-radou' => array('label' => 'Obchodování s radou',),
'odpad-a-recyklace' => array('label' => 'Odpad a recyklace',),
'ochrana-deti' => array('label' => 'Ochrana dětí',),
'parkoviste' => array('label' => 'Parkoviště',),
'parky-a-otevrene-prostory' => array('label' => 'Parky a otevřené prostory',),
'pece' => array('label' => 'Péče',),
'pece-o-ulice-a-cisteni' => array('label' => 'Péče o ulice a čištění',),
'penize-a-dluhove-poradenstvi' => array('label' => 'Peníze a dluhové poradenství',),
'planovaci-politika' => array('label' => 'Plánovací politika',),
'planovani-a-vyvoj' => array('label' => 'Plánování a vývoj',),
'planovani-aplikaci' => array('label' => 'Plánování aplikací',),
'podpora-ozbrojenych-sil' => array('label' => 'Podpora ozbrojených sil',),
'podpora-rodiny' => array('label' => 'Podpora rodiny',),
'podporovane-a-chranene-bydleni' => array('label' => 'Podporované a chráněné bydlení',),
'poradenstvi-v-oblasti-bydleni' => array('label' => 'Poradenství v oblasti bydlení',),
'potraviny,-alkohol-a-zabavni-licence' => array('label' => 'Potraviny, alkohol a zábavní licence',),
'povoleni-a-regulace' => array('label' => 'Povolení a regulace',),
'povoleni-staveb-a-staveb' => array('label' => 'Povolení staveb a staveb',),
'povoleni-zvirat' => array('label' => 'Povolení zvířat',),
'prace-a-kariera' => array('label' => 'Práce a kariéra',),
'prava-pruchodu' => array('label' => 'Práva průchodu',),
'prijeti-a-podpora' => array('label' => 'Přijetí a podpora',),
'priroda-a-ochrana' => array('label' => 'Příroda a ochrana',),
'publikace-rady' => array('label' => 'Publikace Rady',),
'rada' => array('label' => 'Rada',),
'rada-a-socialni-bydleni' => array('label' => 'Rada a sociální bydlení',),
'radni-a-poslanci' => array('label' => 'Radní a poslanci',),
'rozpocty,-vydaje-a-vykonnost' => array('label' => 'Rozpočty, výdaje a výkonnost',),
'rozvoj-podnikani' => array('label' => 'Rozvoj podnikání',),
'rizeni-budovy' => array('label' => 'Řízení budovy',),
'silnice-a-chodniky' => array('label' => 'Silnice a chodníky',),
'skupiny-a-organizace-spolecenstvi' => array('label' => 'Skupiny a organizace Společenství',),
'sluzby-namorni-a-vodni-dopravy' => array('label' => 'Služby námořní a vodní dopravy',),
'socialni-pece-pro-dospele' => array('label' => 'Sociální péče pro dospělé',),
'soukrome-bydleni' => array('label' => 'Soukromé bydlení',),
'specialni-vzdelavaci-potreby' => array('label' => 'Speciální vzdělávací potřeby',),
'spolecenstvi-a-bydleni' => array('label' => 'Společenství a bydlení',),
'sport-a-volny-cas' => array('label' => 'Sport a volný čas',),
'strategie,-plany-a-politiky' => array('label' => 'Strategie, plány a politiky',),
'struktura-rady' => array('label' => 'Struktura Rady',),
'stred-mesta' => array('label' => 'Střed města',),
'skolky-a-pece-o-deti' => array('label' => 'Školky a péče o děti',),
'skoly-a-skolni-dochazky' => array('label' => 'Školy a školní docházky',),
'taxi-a-minicab-licencovani' => array('label' => 'Taxi a minicab licencování',),
'trestne-ciny-mladeze' => array('label' => 'Trestné činy mládeže',),
'trhy' => array('label' => 'Trhy',),
'umeni-a-kultura' => array('label' => 'Umění a kultura',),
'verejna-doprava' => array('label' => 'Veřejná doprava',),
'vodni-hospodarstvi-a-zaplavy' => array('label' => 'Vodní hospodářství a záplavy',),
'vodohospodarske-cinnosti' => array('label' => 'Vodohospodářské činnosti',),
'volby-a-hlasovani' => array('label' => 'Volby a hlasování',),
'volna-mista-rady' => array('label' => 'Volná místa Rady',),
'volny-cas-a-kultura' => array('label' => 'Volný čas a kultura',),
'vyhody' => array('label' => 'Výhody',),
'vzdelavaci-ceny-a-granty' => array('label' => 'Vzdělávací ceny a granty',),
'vzdelavani-a-uceni' => array('label' => 'Vzdělávání a učení',),
'vzdelavani-dospelych' => array('label' => 'Vzdělávání dospělých',),
'zachovani-a-regenerace' => array('label' => 'Zachování a regenerace',),
'zarizeni-a-udalosti-spolecenstvi' => array('label' => 'Zařízení a události Společenství',),
'zdravi-a-bezpecnost-pri-praci' => array('label' => 'Zdraví a bezpečnost při práci',),
'zdravi-a-blaho-deti' => array('label' => 'Zdraví a blaho dětí',),
'zdravotni-a-socialni-pece' => array('label' => 'Zdravotní a sociální péče',),
'zdravotni-postizeni' => array('label' => 'Zdravotní postižení',),
'zdravotni-sluzby' => array('label' => 'Zdravotní služby',),
'zimni-cesty' => array('label' => 'Zimní cesty',),
'zlepseni-a-opravy' => array('label' => 'Zlepšení a opravy',),
'znecisteni' => array('label' => 'Znečištění',),
'zneuzivani-latek' => array('label' => 'Zneužívání látek',),
'zvirata-a-skudci' => array('label' => 'Zvířata a škůdci',),
'zivotni-prostredi-a-odpad' => array('label' => 'Životní prostředí a odpad',),
),
'typ-organizace' => array(
	'danovi-poradci' => array('label' => 'Daňoví poradci',),
'exekutori' => array('label' => 'Exekutoři',),
'hospodarska-komora' => array('label' => 'Hospodářská komora',),
'krajsky-urad' => array('label' => 'Krajský úřad',),
'ministerstvo' => array('label' => 'Ministerstvo',),
'notari' => array('label' => 'Notáři',),
'obec-i.-typu' => array('label' => 'Obec I. Typu',),
'obec-ii.-typu' => array('label' => 'Obec II. Typu',),
'obec-iii.-typu' => array('label' => 'Obec III. Typu',),
'organ-statni-spravy' => array('label' => 'Orgán státní správy',),
'organ-verejne-moci' => array('label' => 'Orgán veřejné moci',),
'organizacni-slozka-statu' => array('label' => 'Organizační složka státu',),
'pravnicka-osoba' => array('label' => 'Právnická osoba',),
'pravnicka-osoba-zrizena-ze-zakona' => array('label' => 'Právnická osoba zřízená ze zákona',),
'statutarni-mesta-(magistraty)-a-jejich-obvody' => array('label' => 'Statutární města (Magistráty) a jejich obvody',),
),
'potreby' => array(
'aktivni-a-podporujici-komunita' => array('label' => 'Aktivní a podporující komunita',),
'bezpecne-zivotni-prostredi' => array('label' => 'Bezpečné životní prostředí',),
'byla-konzultovana' => array('label' => 'Byla konzultována',),
'byt-slyset' => array('label' => 'Být slyšet',),
'demokracie' => array('label' => 'Demokracie',),
'dovednosti' => array('label' => 'Dovednosti',),
'financni-pomoc' => array('label' => 'Finanční pomoc',),
'fyzikalni-a-kulturni-rozvoj' => array('label' => 'Fyzikální a kulturní rozvoj',),
'karierni-rust' => array('label' => 'Kariérní růst',),
'kulturni-integrace' => array('label' => 'Kulturní integrace',),
'lecba-dusevnich-stavu' => array('label' => 'Léčba duševních stavů',),
'lecba-chronickych-onemocneni' => array('label' => 'Léčba chronických onemocnění',),
'lecba-zraneni' => array('label' => 'Léčba zranění',),
'lepsi-ubytovani' => array('label' => 'Lepší ubytování',),
'mentoring' => array('label' => 'Mentoring',),
'nekde-zit' => array('label' => 'Někde žít',),
'ochrana-deti' => array('label' => 'Ochrana dětí',),
'osobni-bezpecnost-a-zabezpeceni' => array('label' => 'Osobní bezpečnost a zabezpečení',),
'osobni-prostor' => array('label' => 'Osobní prostor',),
'osetrovatelska-a-rezidencni-pece' => array('label' => 'Ošetřovatelská a rezidenční péče',),
'pece-doma' => array('label' => 'Péče doma',),
'pece-o-bydleni' => array('label' => 'Péče o bydlení',),
'pece-o-deti' => array('label' => 'Péče o děti',),
'pocit-sounalezitosti' => array('label' => 'Pocit sounáležitosti',),
'podpora-v-zivote' => array('label' => 'Podpora v životě',),
'podporovana-sit-pro-pachatele' => array('label' => 'Podporovaná síť pro pachatele',),
'pochopeni-ruznych-kultur-a-presvedceni' => array('label' => 'Pochopení různých kultur a přesvědčení',),
'pokyny-pro-zivot' => array('label' => 'Pokyny pro život',),
'pomoc-pri-odstranovani-prekazek-(fyzicka-/-dusevni-/-stigma)' => array('label' => 'Pomoc při odstraňování překážek (fyzická / duševní / stigma)',),
'pomoc-s-jazykovymi-a-/-nebo-kulturnimi-prekazkami' => array('label' => 'Pomoc s jazykovými a / nebo kulturními překážkami',),
'pomoc-s-komunikaci-/-pristupem' => array('label' => 'Pomoc s komunikací / přístupem',),
'potreby' => array('label' => 'Potřeby',),
'pravni-podpora' => array('label' => 'Právní podpora',),
'prilezitosti-k-uceni' => array('label' => 'Příležitosti k učení',),
'pristresi' => array('label' => 'Přístřeší',),
'pristup-do-sportovnich-a-rekreacnich-zarizeni' => array('label' => 'Přístup do sportovních a rekreačních zařízení',),
'pristup-k-advokacii' => array('label' => 'Přístup k advokacii',),
'pristup-k-kulturnim-zarizenim' => array('label' => 'Přístup k kulturním zařízením',),
'pristup-k-obcanskym-zarizenim' => array('label' => 'Přístup k občanským zařízením',),
'pristup-k-otevrenym-prostorum' => array('label' => 'Přístup k otevřeným prostorům',),
'pristupova-prava' => array('label' => 'Přístupová práva',),
'rovnost' => array('label' => 'Rovnost',),
'rychla-reakce-na-incidenty' => array('label' => 'Rychlá reakce na incidenty',),
'schopnost-hlasovat' => array('label' => 'Schopnost hlasovat',),
'socialni-integrace' => array('label' => 'Sociální integrace',),
'spravedlnost' => array('label' => 'Spravedlnost',),
'svoboda-z-trestne-cinnosti-/-strach-ze-zlocinu' => array('label' => 'Svoboda z trestné činnosti / strach ze zločinu',),
'teplo' => array('label' => 'Teplo',),
'ucast-na-aktivitach' => array('label' => 'Účast na aktivitách',),
'uceni-a-vzdelavani' => array('label' => 'Učení a vzdělávání',),
'vymahani-prava' => array('label' => 'Vymáhání práva',),
'vyziva' => array('label' => 'Výživa',),
'zakladni-zivotni-standard' => array('label' => 'Základní životní standard',),
'zamestnanost' => array('label' => 'Zaměstnanost',),
'zdravotni-pece-/-lekarska-pece' => array('label' => 'Zdravotní péče / Lékařská péče',),
'zdroje-uceni' => array('label' => 'Zdroje učení',),
'zkraceni-nebo-odstraneni-protipravniho-chovani' => array('label' => 'Zkrácení nebo odstranění protiprávního chování',),
),
'az-seznam' => array(
	'aktivity-centra---venkovni-aktivity-a-vzdelavaci-centra' => array('label' => 'Aktivity centra - venkovní aktivity a vzdělávací centra',),
	'aktivity-mladych-lidi' => array('label' => 'Aktivity mladých lidí',),
	'aktivity-pro-deti-a-mladez' => array('label' => 'Aktivity pro děti a mládež',),
	'aktivity-pro-starsi-lidi' => array('label' => 'Aktivity pro starší lidi',),
	'alarmy-a-vybaveni-dalkoveho-ovladani' => array('label' => 'Alarmy a vybavení dálkového ovládání',),
	'alarmy-pro-seniory-a-osoby-se-zdravotnim-postizenim' => array('label' => 'Alarmy pro seniory a osoby se zdravotním postižením',),
	'alarmy-spolecenstvi' => array('label' => 'Alarmy Společenství',),
	'alley-gating' => array('label' => 'Alley gating',),
	'analyza-soukromeho-zasobovani-vodou' => array('label' => 'Analýza soukromého zásobování vodou',),
	'antisocialni-chovani' => array('label' => 'Antisociální chování',),
	'archeologie' => array('label' => 'Archeologie',),
	'archiv' => array('label' => 'Archiv',),
	'asociace-bydleni' => array('label' => 'Asociace bydlení',),
	'atrakce-a-mista-k-navsteve' => array('label' => 'Atrakce a místa k návštěvě',),
	'autobusove-pruchody-(starsi-a-osoby-se-zdravotnim-postizenim)' => array('label' => 'Autobusové průchody (starší a osoby se zdravotním postižením)',),
	'az-seznam' => array('label' => 'Abecední seznam',),
	'azbest' => array('label' => 'Azbest',),
	'bazeny' => array('label' => 'Bazény',),
	'bezbarierove-??parkovani-a-povoleni' => array('label' => 'Bezbariérové ??parkování a povolení',),
	'bezdomovectvi---poradenstvi-a-podpora' => array('label' => 'Bezdomovectví - poradenství a podpora',),
	'bezpecnejsi-cesty-do-skoly' => array('label' => 'Bezpečnější cesty do školy',),
	'bezpecnost---ohnostroj' => array('label' => 'Bezpečnost - ohňostroj',),
	'bezpecnost-domova' => array('label' => 'Bezpečnost domova',),
	'bezpecnost-na-silnicich' => array('label' => 'Bezpečnost na silnicích',),
	'bezpecnost-potravin' => array('label' => 'Bezpečnost potravin',),
	'bezpecnosti-spolecenstvi' => array('label' => 'Bezpečnosti Společenství',),
	'biodiverzita' => array('label' => 'Biodiverzita',),
	'bridleways' => array('label' => 'Bridleways',),
	'brzy-roky-a-pece-o-deti' => array('label' => 'Brzy roky a péče o děti',),
	'bydleni---chranene' => array('label' => 'Bydlení - chráněné',),
	'bydleni---rada' => array('label' => 'Bydlení - rada',),
	'bytova-pece---dospeli' => array('label' => 'Bytová péče - dospělí',),
	'ceny-narodnich-domacnosti-(nndr)' => array('label' => 'Ceny národních domácností (NNDR)',),
	'cestovni-ruch' => array('label' => 'Cestovní ruch',),
	'cestovni-sluzby' => array('label' => 'Cestovní služby',),
	'cesty---omezeni-rychlosti' => array('label' => 'Cesty - omezení rychlosti',),
	'cikanske-a-cestovatelske-lokality' => array('label' => 'Cikánské a cestovatelské lokality',),
	'cirkve-a-mista-uctivani' => array('label' => 'Církve a místa uctívání',),
	'citizens-advice-bureau' => array('label' => 'Citizens Advice Bureau',),
	'civilni-nouzove-situace' => array('label' => 'Civilní nouzové situace',),
	'civilni-partnerstvi' => array('label' => 'Civilní partnerství',),
	'co-je-na-pruvodce' => array('label' => 'Co je na průvodce',),
	'cyklisticke-skoleni' => array('label' => 'Cyklistické školení',),
	'cyklistika' => array('label' => 'Cyklistika',),
	'dalsi-vzdelavani' => array('label' => 'Další vzdělávání',),
	'demolice' => array('label' => 'Demolice',),
	'demonstrace-a-pruvody' => array('label' => 'Demonstrace a průvody',),
	'denni-centra' => array('label' => 'Denní centra',),
	'denni-pece' => array('label' => 'Denní péče',),
	'detska-centra' => array('label' => 'Dětská centra',),
	'divadla-a-kina' => array('label' => 'Divadla a kina',),
	'dluhova-pomoc-a-poradenstvi' => array('label' => 'Dluhová pomoc a poradenství',),
	'dny-dovolene---skoly' => array('label' => 'Dny dovolené - školy',),
	'dobrovolne-organizace-a-podpurne-skupiny' => array('label' => 'Dobrovolné organizace a podpůrné skupiny',),
	'dobrovolnictvi' => array('label' => 'Dobrovolnictví',),
	'domaci-opravy' => array('label' => 'Domácí opravy',),
	'domaci-pece' => array('label' => 'Domácí péče',),
	'domaci-skola' => array('label' => 'Domácí škola',),
	'domaci-upravy-a-pomucky' => array('label' => 'Domácí úpravy a pomůcky',),
	'domaci-vzdelavani' => array('label' => 'Domácí vzdělávání',),
	'domovy-pece---deti-a-mladi-lide' => array('label' => 'Domovy péče - děti a mladí lidé',),
	'domovy-pece-a-chranene-bydleni' => array('label' => 'Domovy péče a chráněné bydlení',),
	'domy-s-vicenasobnym-zamestnanim' => array('label' => 'Domy s vícenásobným zaměstnáním',),
	'doprava-a-ulice' => array('label' => 'Doprava a ulice',),
	'doprava-spolecenstvi' => array('label' => 'Doprava Společenství',),
	'dopravni-schemata' => array('label' => 'Dopravní schémata',),
	'dopravni-znacky' => array('label' => 'Dopravní značky',),
	'dospeli-pecovatele' => array('label' => 'Dospělí pečovatelé',),
	'dostupne-bydleni' => array('label' => 'Dostupné bydlení',),
	'drogy---poradenstvi-a-podpora' => array('label' => 'Drogy - poradenství a podpora',),
	'drtici-silnice' => array('label' => 'Drtící silnice',),
	'druzstevni-zalozny' => array('label' => 'Družstevní záložny',),
	'dump' => array('label' => 'Dump',),
	'dumped-vozidla' => array('label' => 'Dumped vozidla',),
	'dve-mesta' => array('label' => 'Dvě města',),
	'energeticka-ucinnost' => array('label' => 'Energetická účinnost',),
	'environmentalni-informacni-predpisy' => array('label' => 'Environmentální informační předpisy',),
	'environmentalni-zdravi' => array('label' => 'Environmentální zdraví',),
	'evropske-a-jine-financovani' => array('label' => 'Evropské a jiné financování',),
	'exhumace' => array('label' => 'Exhumace',),
	'farni-a-mestske-rady' => array('label' => 'Farní a městské rady',),
	'finance-a-rozpocty' => array('label' => 'Finance a rozpočty',),
	'flyposting' => array('label' => 'Flyposting',),
	'flytipping' => array('label' => 'Flytipping',),
	'galerie' => array('label' => 'Galerie',),
	'golfove-kurzy' => array('label' => 'Golfové kurzy',),
	'grant-na-pomoc-pri-opravach-domu' => array('label' => 'Grant na pomoc při opravách domů',),
	'granty---bydleni-a-bydleni' => array('label' => 'Granty - bydlení a bydlení',),
	'granty---dobrovolne-organizace' => array('label' => 'Granty - dobrovolné organizace',),
	'granty---komunitni-zarizeni' => array('label' => 'Granty - komunitní zařízení',),
	'granty---podnikani' => array('label' => 'Granty - podnikání',),
	'granty---studentske-ceny' => array('label' => 'Granty - studentské ceny',),
	'granty-na-obnovu-domu' => array('label' => 'Granty na obnovu domů',),
	'granty-pro-sportovni-kluby' => array('label' => 'Granty pro sportovní kluby',),
	'granty-spolecenstvi' => array('label' => 'Granty Společenství',),
	'guverneri-(skoly)' => array('label' => 'Guvernéři (školy)',),
	'hasicska-a-zachranna-sluzba' => array('label' => 'Hasičská a záchranná služba',),
	'hazardni-hry---licencni-prostory' => array('label' => 'Hazardní hry - licenční prostory',),
	'historicke-stranky' => array('label' => 'Historické stránky',),
	'hiv-/-aids---poradenstvi-a-podpora' => array('label' => 'HIV / AIDS - poradenství a podpora',),
	'hlasovani' => array('label' => 'Hlasování',),
	'hledani-nemovitosti' => array('label' => 'Hledání nemovitostí',),
	'hlidani-deti' => array('label' => 'Hlídání dětí',),
	'hluk-neprijemny' => array('label' => 'Hluk nepříjemný',),
	'hmotnosti-a-miry' => array('label' => 'Hmotnosti a míry',),
	'hodnoceni-charakteru-krajiny' => array('label' => 'Hodnocení charakteru krajiny',),
	'hodnoceni-potreb' => array('label' => 'Hodnocení potřeb',),
	'hodnoceni-zvlastnich-vzdelavacich-potreb' => array('label' => 'Hodnocení zvláštních vzdělávacích potřeb',),
	'hospicova-pece---deti' => array('label' => 'Hospicová péče - děti',),
	'hospicova-pece---dospeli' => array('label' => 'Hospicová péče - dospělí',),
	'hraci-centra' => array('label' => 'Hrací centra',),
	'hrnce' => array('label' => 'Hrnce',),
	'hroby' => array('label' => 'Hroby',),
	'hrbitovy' => array('label' => 'Hřbitovy',),
	'hubeni-skudcu' => array('label' => 'Hubení škůdců',),
	'hudba' => array('label' => 'Hudba',),
	'chladici-veze' => array('label' => 'Chladicí věže',),
	'chov-psu' => array('label' => 'Chov psů',),
	'chranena-skrin' => array('label' => 'Chráněná skříň',),
	'chuze-do-skoly' => array('label' => 'Chůze do školy',),
	'infekcni-choroby' => array('label' => 'Infekční choroby',),
	'informace-o-scitani' => array('label' => 'Informace o sčítání',),
	'informacni-centra-pro-navstevniky' => array('label' => 'Informační centra pro návštěvníky',),
	'iniciativy-v-oblasti-zamestnanosti-a-odborne-pripravy' => array('label' => 'Iniciativy v oblasti zaměstnanosti a odborné přípravy',),
	'inkontinencni-pradelna' => array('label' => 'Inkontinenční prádelna',),
	'jazykova-a-kulturni-podpora' => array('label' => 'Jazyková a kulturní podpora',),
	'jazyky---prekladatelske-a-tlumocnicke-sluzby' => array('label' => 'Jazyky - překladatelské a tlumočnické služby',),
	'jednotne-granty---skoly' => array('label' => 'Jednotné granty - školy',),
	'jednotny-plan-rozvoje' => array('label' => 'Jednotný plán rozvoje',),
	'jizda-na-koni-a-staje' => array('label' => 'Jízda na koni a stáje',),
	'kamerovy-system' => array('label' => 'kamerový systém',),
	'kanaly-a-vodni-cesty' => array('label' => 'Kanály a vodní cesty',),
	'kancelar-zaznamu' => array('label' => 'Kancelář záznamů',),
	'karavan-a-kempy' => array('label' => 'Karavan a kempy',),
	'karierni-poradenstvi' => array('label' => 'Kariérní poradenství',),
	'kempy-a-tabory-pro-karavany' => array('label' => 'Kempy a tábory pro karavany',),
	'kerbside-recyklacni-sbirky' => array('label' => 'Kerbside recyklační sbírky',),
	'kina' => array('label' => 'Kina',),
	'klimaticka-zmena' => array('label' => 'Klimatická změna',),
	'klinicky-odpad' => array('label' => 'Klinický odpad',),
	'kluby-mimo-skoly' => array('label' => 'Kluby mimo školy',),
	'knihovni-pokuty' => array('label' => 'Knihovní pokuty',),
	'knihovnicke-knihy---obnovte-a-rezervujte-on-line' => array('label' => 'Knihovnické knihy - obnovte a rezervujte on-line',),
	'knihovny' => array('label' => 'Knihovny',),
	'knihovny---knihovny-hracek' => array('label' => 'Knihovny - knihovny hraček',),
	'knihovny---mobilni' => array('label' => 'Knihovny - mobilní',),
	'knihovny---pristup-k-internetu' => array('label' => 'Knihovny - přístup k internetu',),
	'knihovny---skola' => array('label' => 'Knihovny - škola',),
	'kolekce-kolekci' => array('label' => 'Kolekce kolekcí',),
	'komercni-odpady' => array('label' => 'Komerční odpady',),
	'komercni-pozemky-a-nemovitosti' => array('label' => 'Komerční pozemky a nemovitosti',),
	'komunalni-bytovy-fond' => array('label' => 'Komunální bytový fond',),
	'komunitni-opatrovnici' => array('label' => 'Komunitní opatrovníci',),
	'komunitnich-center' => array('label' => 'Komunitních center',),
	'konference,-setkani-a-udalosti' => array('label' => 'Konference, setkání a události',),
	'kontaktujte-radu' => array('label' => 'Kontaktujte radu',),
	'kontaminovana-puda' => array('label' => 'Kontaminovaná půda',),
	'kontrola-statni-prislusnosti' => array('label' => 'Kontrola státní příslušnosti',),
	'kontrola-znecisteni' => array('label' => 'Kontrola znečištění',),
	'kontrola-znecisteni---azbest' => array('label' => 'Kontrola znečištění - azbest',),
	'kontrola-znecisteni---hluk' => array('label' => 'Kontrola znečištění - hluk',),
	'kontrola-znecisteni---vzduch' => array('label' => 'Kontrola znečištění - vzduch',),
	'konzultace' => array('label' => 'Konzultace',),
	'koronery' => array('label' => 'Koronery',),
	'kose-na-psy' => array('label' => 'Koše na psy',),
	'kotviste' => array('label' => 'Kotviště',),
	'koupelny-(koupani)' => array('label' => 'Koupelny (koupání)',),
	'kremace-a-pohrby' => array('label' => 'Kremace a pohřby',),
	'krematorium' => array('label' => 'Krematorium',),
	'kultura-a-umeni' => array('label' => 'Kultura a umění',),
	'kvalita-vody' => array('label' => 'Kvalita vody',),
	'kvalita-vzduchu' => array('label' => 'Kvalita vzduchu',),
	'lahve-bank' => array('label' => 'Láhve bank',),
	'licence' => array('label' => 'Licence',),
	'licence-na-alkohol-a-zabavni-prostory' => array('label' => 'Licence na alkohol a zábavní prostory',),
	'licence-na-kovovy-srot' => array('label' => 'Licence na kovový šrot',),
	'licence-na-leseni' => array('label' => 'Licence na lešení',),
	'licence-na-sexualni-provoz' => array('label' => 'Licence na sexuální provoz',),
	'licence-na-ulici-kavarny' => array('label' => 'Licence na ulici kavárny',),
	'mapy' => array('label' => 'Mapy',),
	'mentoring' => array('label' => 'Mentoring',),
	'mimo-socialni-pece' => array('label' => 'Mimo sociální péče',),
	'mimoskolni-aktivity' => array('label' => 'Mimoškolní aktivity',),
	'mista-na-zakladnich-skolach' => array('label' => 'Místa na základních školách',),
	'mista-skolky' => array('label' => 'Místa školky',),
	'mistni-historie-a-dedictvi' => array('label' => 'Místní historie a dědictví',),
	'mistni-plan' => array('label' => 'Místní plán',),
	'mistni-vyhledavani-pudy' => array('label' => 'Místní vyhledávání půdy',),
	'mladi-pecovatele' => array('label' => 'Mladí pečovatelé',),
	'mlceni-deti' => array('label' => 'Mlčení dětí',),
	'mobilni-knihovny' => array('label' => 'Mobilní knihovny',),
	'modra-povoleni-k-parkovani' => array('label' => 'Modrá povolení k parkování',),
	'mortuary' => array('label' => 'Mortuary',),
	'mosty' => array('label' => 'Mosty',),
	'muzea-a-galerie' => array('label' => 'Muzea a galerie',),
	'nabor' => array('label' => 'Nábor',),
	'navigace' => array('label' => 'Navigace',),
	'nazvy-ulic-a-cisla-domu' => array('label' => 'Názvy ulic a čísla domů',),
	'nebezpecne-a-volne-zijici-zvirata' => array('label' => 'Nebezpečné a volně žijící zvířata',),
	'nebezpecne-budovy-a-stavby' => array('label' => 'Nebezpečné budovy a stavby',),
	'nebezpecne-psy' => array('label' => 'Nebezpečné psy',),
	'nezamestnanost' => array('label' => 'Nezaměstnanost',),
	'nhs-direct' => array('label' => 'NHS Direct',),
	'nouzove-planovani' => array('label' => 'Nouzové plánování',),
	'nouzove-situace' => array('label' => 'Nouzové situace',),
	'obcanska-vybavenost' => array('label' => 'Občanská vybavenost',),
	'obcanske-uznani' => array('label' => 'Občanské uznání',),
	'obecni-dan' => array('label' => 'obecní daň',),
	'obchodni-normy' => array('label' => 'Obchodní normy',),
	'obchodni-normy---poradenstvi-podnikum' => array('label' => 'Obchodní normy - poradenství podnikům',),
	'obchodni-normy---poradenstvi-pro-spotrebitele' => array('label' => 'Obchodní normy - poradenství pro spotřebitele',),
	'obchodni-odpad' => array('label' => 'Obchodní odpad',),
	'obchodni-poradenstvi' => array('label' => 'Obchodní poradenství',),
	'obchodni-poradenstvi---obchodni-normy' => array('label' => 'Obchodní poradenství - obchodní normy',),
	'obchodni-sazby' => array('label' => 'Obchodní sazby',),
	'obchodovani-na-ulici' => array('label' => 'Obchodování na ulici',),
	'objemny-odpad' => array('label' => 'Objemný odpad',),
	'obrubniky' => array('label' => 'Obrubníky',),
	'obrady-obcanstvi' => array('label' => 'Obřady občanství',),
	'obtezovani-poradenstvi-a-podpora' => array('label' => 'Obtěžování poradenství a podpora',),
	'obyvatele-povoleni-k-parkovani' => array('label' => 'Obyvatelé povolení k parkování',),
	'ockovani' => array('label' => 'Očkování',),
	'odmitnout' => array('label' => 'Odmítnout',),
	'odpad---specialni-sbirky-pro-velke-predmety' => array('label' => 'Odpad - speciální sbírky pro velké předměty',),
	'odpad---velka-sbirka-polozek' => array('label' => 'Odpad - velká sbírka položek',),
	'odpad---zahrada' => array('label' => 'Odpad - zahrada',),
	'odpad-a-recyklace' => array('label' => 'Odpad a recyklace',),
	'odpad-nebezpecny' => array('label' => 'Odpad nebezpečný',),
	'odpadky' => array('label' => 'Odpadky',),
	'odpadovy-mistni-plan' => array('label' => 'Odpadový místní plán',),
	'odstraneni-graffiti' => array('label' => 'Odstranění graffiti',),
	'odstraneni-mrtvych-zvirat' => array('label' => 'Odstranění mrtvých zvířat',),
	'odstraneni-snehu' => array('label' => 'Odstranění sněhu',),
	'odstranovani-obchodnich,-obchodnich-a-komercnich-odpadu' => array('label' => 'Odstraňování obchodních, obchodních a komerčních odpadů',),
	'odstranovani-odpadku' => array('label' => 'Odstraňování odpadků',),
	'odvoz-odpadu' => array('label' => 'Odvoz odpadu',),
	'ofsted-hlasi' => array('label' => 'Ofsted hlásí',),
	'ohnostroje---prodej' => array('label' => 'Ohňostroje - prodej',),
	'ohnostrojova-bezpecnost' => array('label' => 'Ohňostrojová bezpečnost',),
	'ochrana-dat' => array('label' => 'Ochrana dat',),
	'ochrana-deti' => array('label' => 'Ochrana dětí',),
	'ochrana-dospelych' => array('label' => 'Ochrana dospělých',),
	'ochrana-pobreznich-casti' => array('label' => 'Ochrana pobřežních částí',),
	'ochrana-prirody' => array('label' => 'Ochrana přírody',),
	'omezeni-rychlosti' => array('label' => 'Omezení rychlosti',),
	'opravte-svuj-domov' => array('label' => 'Opravte svůj domov',),
	'opravy---nouzove-mimocasove-opravy-na-bydleni-v-rade' => array('label' => 'Opravy - nouzové mimočasové opravy na bydlení v radě',),
	'opravy-a-renovace-bytu' => array('label' => 'Opravy a renovace bytů',),
	'opravy-chodniku' => array('label' => 'Opravy chodníků',),
	'opustena-vozidla' => array('label' => 'Opuštěná vozidla',),
	'opustene-nakupni-voziky' => array('label' => 'Opuštěné nákupní vozíky',),
	'osobni-licence-na-alkohol' => array('label' => 'Osobní licence na alkohol',),
	'osobni-licence-na-alkohol-a-zabavu' => array('label' => 'Osobní licence na alkohol a zábavu',),
	'osoby-se-zdravotnim-postizenim---autobusove-pruchody' => array('label' => 'Osoby se zdravotním postižením - autobusové průchody',),
	'osoby-se-zdravotnim-postizenim---programy-zamestnanosti-a-odborne-pripravy' => array('label' => 'Osoby se zdravotním postižením - programy zaměstnanosti a odborné přípravy',),
	'osvedceni---objednani-porodu,-manzelstvi-a-smrti' => array('label' => 'Osvědčení - Objednání porodu, manželství a smrti',),
	'osvetleni-ulic' => array('label' => 'Osvětlení ulic',),
	'osetrovatelske-a-obytne-domy-pro-dospele' => array('label' => 'Ošetřovatelské a obytné domy pro dospělé',),
	'otevrene-prostory-a-parky' => array('label' => 'Otevřené prostory a parky',),
	'oznameni-o-pokutach' => array('label' => 'Oznámení o pokutách',),
	'oznameni-o-rozhodnuti-o-planovani' => array('label' => 'Oznámení o rozhodnutí o plánování',),
	'pamatniky' => array('label' => 'Památníky',),
	'parkovaci-a-jizdni-schemata' => array('label' => 'Parkovací a jízdní schémata',),
	'parkovaci-mista-pro-zdravotne-postizene-osoby' => array('label' => 'Parkovací místa pro zdravotně postižené osoby',),
	'parkovaci-plochy' => array('label' => 'Parkovací plochy',),
	'parkovaci-pokuty' => array('label' => 'Parkovací pokuty',),
	'parkovaci-povoleni' => array('label' => 'Parkovací povolení',),
	'parkovaci-povoleni-pro-osoby-se-zdravotnim-postizenim---modry-odznak' => array('label' => 'Parkovací povolení pro osoby se zdravotním postižením - modrý odznak',),
	'parkoviste' => array('label' => 'Parkoviště',),
	'parky-a-otevrene-prostory' => array('label' => 'Parky a otevřené prostory',),
	'partnerstvi-mest' => array('label' => 'Partnerství měst',),
	'pastva' => array('label' => 'Pastva',),
	'pasy---volny-cas' => array('label' => 'Pasy - volný čas',),
	'pausaly-pro-volny-cas' => array('label' => 'Paušály pro volný čas',),
	'pece' => array('label' => 'Péče',),
	'pece-doma' => array('label' => 'Péče doma',),
	'pece-o-bydleni---deti-a-mladez' => array('label' => 'Péče o bydlení - děti a mládež',),
	'pece-o-deti' => array('label' => 'Péče o děti',),
	'pece-o-deti---deti' => array('label' => 'Péče o děti - děti',),
	'pece-o-psy' => array('label' => 'Péče o psy',),
	'penize-a-dluhove-poradenstvi' => array('label' => 'Peníze a dluhové poradenství',),
	'pesi-cesty---prava-cest---rady-a-informace' => array('label' => 'Pěší cesty - práva cest - rady a informace',),
	'planovaci-aplikace-(obytne)' => array('label' => 'Plánovací aplikace (obytné)',),
	'planovani' => array('label' => 'Plánování',),
	'planovani-a-politika-dopravy' => array('label' => 'Plánování a politika dopravy',),
	'planovani-aplikaci-(firem)' => array('label' => 'Plánování aplikací (firem)',),
	'planovani-cest' => array('label' => 'Plánování cest',),
	'planovani-nerostu' => array('label' => 'Plánování nerostů',),
	'platba-za-peci---prime-platby' => array('label' => 'Platba za péči - přímé platby',),
	'playgroups' => array('label' => 'Playgroups',),
	'plna-moc' => array('label' => 'Plná moc',),
	'podival-se-na-deti' => array('label' => 'Podíval se na děti',),
	'podnikove-granty' => array('label' => 'Podnikové granty',),
	'podpora-cestovani-domu' => array('label' => 'Podpora cestování domů',),
	'podpora-obeti' => array('label' => 'Podpora obětí',),
	'podpora-svedku' => array('label' => 'Podpora svědků',),
	'podpora-zarizeni-pro-zdravotne-postizene-osoby' => array('label' => 'Podpora zařízení pro zdravotně postižené osoby',),
	'podporovane-a-chranene-bydleni' => array('label' => 'Podporované a chráněné bydlení',),
	'pohotovostni-sluzby' => array('label' => 'Pohotovostní služby',),
	'pohrbivani-a-kremace' => array('label' => 'Pohřbívání a kremace',),
	'pohrby-a-pohrby' => array('label' => 'Pohřby a pohřby',),
	'pojmenovani-obradu' => array('label' => 'Pojmenování obřadů',),
	'policie' => array('label' => 'policie',),
	'pomoc-doma' => array('label' => 'Pomoc doma',),
	'poradenstvi' => array('label' => 'Poradenství',),
	'poradenstvi-a-podpora-v-oblasti-alkoholu' => array('label' => 'Poradenství a podpora v oblasti alkoholu',),
	'poradenstvi-a-pomoc-pri-poraneni' => array('label' => 'Poradenství a pomoc při poranění',),
	'poradenstvi-pro-spotrebitele' => array('label' => 'Poradenství pro spotřebitele',),
	'poradenstvi-v-oblasti-kompostovani' => array('label' => 'Poradenství v oblasti kompostování',),
	'poruchy---prizpusobeni-vaseho-domova' => array('label' => 'Poruchy - přizpůsobení vašeho domova',),
	'posilovani' => array('label' => 'Posilování',),
	'poslanci---poslanci-a-poslanci-evropskeho-parlamentu' => array('label' => 'Poslanci - poslanci a poslanci Evropského parlamentu',),
	'poslanci-a-poslanci-evropskeho-parlamentu' => array('label' => 'Poslanci a poslanci Evropského parlamentu',),
	'postovni-hlasy' => array('label' => 'Poštovní hlasy',),
	'povoleni-k-filmu-a-fotografovani' => array('label' => 'Povolení k filmu a fotografování',),
	'povoleni-k-parkovani' => array('label' => 'Povolení k parkování',),
	'pozemky-a-majetek' => array('label' => 'Pozemky a majetek',),
	'pozarni-bezpecnost' => array('label' => 'Požární bezpečnost',),
	'prace---poradenstvi-v-oblasti-kariery' => array('label' => 'Práce - poradenství v oblasti kariéry',),
	'prace-pro-radu' => array('label' => 'Práce pro radu',),
	'pracovni-lekarstvi' => array('label' => 'Pracovní lékařství',),
	'pracovni-staze' => array('label' => 'Pracovní stáže',),
	'prava-cest' => array('label' => 'Práva cest',),
	'prava-na-socialni-zabezpeceni' => array('label' => 'Práva na sociální zabezpečení',),
	'pravni-rada' => array('label' => 'Právní rada',),
	'pravo-koupit---bydleni-rady' => array('label' => 'Právo koupit - bydlení rady',),
	'prazdne-vlastnosti' => array('label' => 'Prázdné vlastnosti',),
	'prevence-a-omezovani-kriminality' => array('label' => 'Prevence a omezování kriminality',),
	'probacni-sluzby' => array('label' => 'Probační služby',),
	'pronajem-hudebnich-nastroju' => array('label' => 'Pronájem hudebních nástrojů',),
	'pronajimatele' => array('label' => 'Pronajímatelé',),
	'provozni-licence' => array('label' => 'Provozní licence',),
	'proxy-hlasovani' => array('label' => 'Proxy hlasování',),
	'predskolni-vzdelavani' => array('label' => 'Předškolní vzdělávání',),
	'prechody-pro-chodce' => array('label' => 'Přechody pro chodce',),
	'prekazky-na-chodniku' => array('label' => 'Překážky na chodníku',),
	'prekladatelske-a-tlumocnicke-sluzby' => array('label' => 'Překladatelské a tlumočnické služby',),
	'preskocit-licence' => array('label' => 'Přeskočit licence',),
	'prideleni' => array('label' => 'Přidělení',),
	'prideleni-bydleni' => array('label' => 'Přidělení bydlení',),
	'prijate-cesty' => array('label' => 'Přijaté cesty',),
	'prijeti' => array('label' => 'Přijetí',),
	'prijeti-do-skoly' => array('label' => 'Přijetí do školy',),
	'prijeti-do-skoly---primarni' => array('label' => 'Přijetí do školy - primární',),
	'prijeti-do-skoly---sekundarni' => array('label' => 'Přijetí do školy - sekundární',),
	'prijimaci-rizeni-(skoly)' => array('label' => 'Přijímací řízení (školy)',),
	'prikazy-na-ochranu-stromu' => array('label' => 'Příkazy na ochranu stromů',),
	'prime-platby' => array('label' => 'Přímé platby',),
	'prirodni-rezervace' => array('label' => 'Přírodní rezervace',),
	'prispevky-na-bydleni' => array('label' => 'Příspěvky na bydlení',),
	'pristavy-a-pristaviste' => array('label' => 'Přístavy a přístaviště',),
	'pristavy-a-pristavy' => array('label' => 'Přístavy a přístavy',),
	'pristehovalectvi' => array('label' => 'Přistěhovalectví',),
	'pristup-k-internetu-v-knihovnach' => array('label' => 'Přístup k internetu v knihovnách',),
	'pristupnost' => array('label' => 'Přístupnost',),
	'prizpusobeni-vaseho-domova' => array('label' => 'Přizpůsobení vašeho domova',),
	'pujcovna-a-rezervace-haly' => array('label' => 'Půjčovna a rezervace haly',),
	'rada---osvobozeni-od-dani' => array('label' => 'Rada - osvobození od daní',),
	'rada---slevy' => array('label' => 'Rada - slevy',),
	'rada-dane---vyhody' => array('label' => 'Rada daně - výhody',),
	'radnice' => array('label' => 'Radnice',),
	'radnici' => array('label' => 'Radnici',),
	'rady-pro-pripad-nouzove-opravy' => array('label' => 'Rady pro případ nouzové opravy',),
	'ramec-mistniho-rozvoje' => array('label' => 'Rámec místního rozvoje',),
	'ranger-sluzba' => array('label' => 'Ranger služba',),
	'recyklace' => array('label' => 'Recyklace',),
	'recyklace-domovniho-odpadu' => array('label' => 'Recyklace domovního odpadu',),
	'recyklace-sbirek' => array('label' => 'Recyklace sbírek',),
	'recyklacni-mista' => array('label' => 'Recyklační místa',),
	'regenerace' => array('label' => 'Regenerace',),
	'registr-planovani' => array('label' => 'Registr plánování',),
	'registrace-narozeni' => array('label' => 'Registrace narození',),
	'registrace-umrti' => array('label' => 'Registrace úmrtí',),
	'registracni-kancelare' => array('label' => 'Registrační kanceláře',),
	'registrars' => array('label' => 'Registrars',),
	'repatriace-tel' => array('label' => 'Repatriace těl',),
	'rodicovska-podpora' => array('label' => 'Rodičovská podpora',),
	'rodicovske-prikazy' => array('label' => 'Rodičovské příkazy',),
	'rodinna-historie' => array('label' => 'Rodinná historie',),
	'rodiny' => array('label' => 'Rodiny',),
	'rovnost-a-rozmanitost' => array('label' => 'Rovnost a rozmanitost',),
	'rozvoj---mistni-ekonomika-a-podnikani' => array('label' => 'Rozvoj - místní ekonomika a podnikání',),
	'rybolov' => array('label' => 'Rybolov',),
	'rychlost-houpani-a-uklidneni-provozu' => array('label' => 'Rychlost houpání a uklidnění provozu',),
	'rezani-travy' => array('label' => 'Řezání trávy',),
	'rizeni-budovy' => array('label' => 'Řízení budovy',),
	'rizeni-majetku' => array('label' => 'Řízení majetku',),
	'rizeni-provozu' => array('label' => 'Řízení provozu',),
	'rizeni-vyvoje' => array('label' => 'Řízení vývoje',),
	'sber-domacnosti' => array('label' => 'Sběr domácností',),
	'sber-kose' => array('label' => 'Sběr koše',),
	'sber-nebezpecnych-odpadu' => array('label' => 'Sběr nebezpečných odpadů',),
	'sber-odpadku' => array('label' => 'Sběr odpadků',),
	'sber-odpadu---domaci-kose' => array('label' => 'Sběr odpadu - domácí koše',),
	'semafory' => array('label' => 'Semafory',),
	'sen---doprava-do-skoly' => array('label' => 'SEN - doprava do školy',),
	'sen---hodnoceni-specialnich-vzdelavacich-potreb' => array('label' => 'SEN - Hodnocení speciálních vzdělávacích potřeb',),
	'schema-motability' => array('label' => 'Schéma motability',),
	'schvaleni-dodavatele' => array('label' => 'Schválení dodavatelé',),
	'silnice---dopravni-systemy' => array('label' => 'Silnice - dopravní systémy',),
	'silnice---snezeni' => array('label' => 'Silnice - sněžení',),
	'silnicni-prace' => array('label' => 'Silniční práce',),
	'silnicni-sterkovani' => array('label' => 'Silniční štěrkování',),
	'skladky-odpadu-z-domacnosti' => array('label' => 'Skládky odpadů z domácností',),
	'skupiny-rodicu-a-batolat' => array('label' => 'Skupiny rodičů a batolat',),
	'slaneni-silnic-v-zime' => array('label' => 'Slanění silnic v zimě',),
	'sluzby-dusevniho-zdravi' => array('label' => 'Služby duševního zdraví',),
	'sluzby-pro-dospele' => array('label' => 'Služby pro dospělé',),
	'smrt---kremace' => array('label' => 'Smrt - kremace',),
	'smrt---pohrby-a-pohrby' => array('label' => 'Smrt - pohřby a pohřby',),
	'smrt---poradenstvi-v-oblasti-umrti-a-podpora' => array('label' => 'Smrt - poradenství v oblasti úmrtí a podpora',),
	'snadno-spustte-detska-centra' => array('label' => 'Snadno spusťte dětská centra',),
	'snatky' => array('label' => 'Sňatky',),
	'socialni-pece-o-dospele' => array('label' => 'Sociální péče o dospělé',),
	'soukrome-bydleni' => array('label' => 'Soukromé bydlení',),
	'sousedni-hodinky' => array('label' => 'Sousední hodinky',),
	'spici-drsny---poradenstvi-a-podpora' => array('label' => 'Spící drsný - poradenství a podpora',),
	'spokojenost-zakazniku' => array('label' => 'Spokojenost zákazníků',),
	'sportovni' => array('label' => 'Sportovní',),
	'sportovni-centra' => array('label' => 'Sportovní centra',),
	'sportovni-koucovani' => array('label' => 'Sportovní koučování',),
	'spravedlive-obchodovani' => array('label' => 'Spravedlivé obchodování',),
	'starostlivost---dospeli' => array('label' => 'Starostlivost - dospělí',),
	'starsi-lide' => array('label' => 'Starší lidé',),
	'starsi-lide---aktivity-a-volny-cas' => array('label' => 'Starší lidé - aktivity a volný čas',),
	'starsi-lide---autobusove-pruchody' => array('label' => 'Starší lidé - autobusové průchody',),
	'stavebni-predpisy' => array('label' => 'Stavební předpisy',),
	'stiznosti,-pripominky-a-navrhy' => array('label' => 'Stížnosti, připomínky a návrhy',),
	'strategie-spolecenstvi' => array('label' => 'Strategie Společenství',),
	'stravovani-na-kolech' => array('label' => 'Stravování na kolech',),
	'strediska-mest---prevence-kriminality' => array('label' => 'Střediska měst - prevence kriminality',),
	'stredni-skoly' => array('label' => 'Střední školy',),
	'strikacky-a-jehly' => array('label' => 'Stříkačky a jehly',),
	'studentske-pujcky' => array('label' => 'Studentské půjčky',),
	'svatebni-mista' => array('label' => 'Svatební místa',),
	'svoboda-informaci' => array('label' => 'Svoboda informací',),
	'sikanovani' => array('label' => 'Šikanování',),
	'skoleni-ridicu' => array('label' => 'Školení řidičů',),
	'skolni-doprava' => array('label' => 'Školní doprava',),
	'skolni-guverneri' => array('label' => 'Školní guvernéři',),
	'skolni-hlidky' => array('label' => 'Školní hlídky',),
	'skolni-jidla' => array('label' => 'Školní jídla',),
	'skolni-knihovny' => array('label' => 'Školní knihovny',),
	'skolni-prazdniny' => array('label' => 'Školní prázdniny',),
	'skoly' => array('label' => 'Školy',),
	'skoly---jazykova-a-kulturni-podpora' => array('label' => 'Školy - jazyková a kulturní podpora',),
	'skoly---prazdninove-schemata' => array('label' => 'Školy - prázdninové schémata',),
	'skoly---sikana' => array('label' => 'Školy - šikana',),
	'taxi-licence' => array('label' => 'Taxi licence',),
	'taxi-ridicsky-prukaz' => array('label' => 'Taxi řidičský průkaz',),
	'tehotenstvi-mladistvych' => array('label' => 'Těhotenství mladistvých',),
	'terminovane-terminy---skoly' => array('label' => 'Termínované termíny - školy',),
	'testovani-mot' => array('label' => 'Testování MOT',),
	'tipy---centra-pro-recyklaci-domovniho-odpadu' => array('label' => 'Tipy - centra pro recyklaci domovního odpadu',),
	'tiskove-zpravy' => array('label' => 'tiskové zprávy',),
	'toalety' => array('label' => 'Toalety',),
	'toulavi-psi' => array('label' => 'Toulaví psi',),
	'trapeni' => array('label' => 'Trápení',),
	'trhy' => array('label' => 'Trhy',),
	'turisticke-informacni-strediska' => array('label' => 'Turistické informační střediska',),
	'turisticke-ubytovani' => array('label' => 'Turistické ubytování',),
	'tym-mladistvych-(yot),-wessex' => array('label' => 'Tým mladistvých (YOT), Wessex',),
	'ubytovani' => array('label' => 'Ubytování',),
	'uceni-dospelych' => array('label' => 'Učení dospělých',),
	'ucitele-uceni' => array('label' => 'Učitelé učení',),
	'udalosti' => array('label' => 'Události',),
	'udrzba-a-opravy-silnic' => array('label' => 'Údržba a opravy silnic',),
	'udrzba-plosiny' => array('label' => 'Údržba plošiny',),
	'udrzba-vozovky' => array('label' => 'Údržba vozovky',),
	'udrzitelny-rozvoj' => array('label' => 'Udržitelný rozvoj',),
	'udrzujte-v-zime-v-teple' => array('label' => 'Udržujte v zimě v teple',),
	'uklid-ulice' => array('label' => 'Úklid ulice',),
	'ulicni-nabytek' => array('label' => 'Uliční nábytek',),
	'umeni-a-zabava' => array('label' => 'Umění a zábava',),
	'univerzity-a-vysoke-skoly' => array('label' => 'Univerzity a vysoké školy',),
	'uprchlici-a-zadatele-o-azyl' => array('label' => 'Uprchlíci a žadatelé o azyl',),
	'uvedene-budovy' => array('label' => 'Uvedené budovy',),
	'uzavreni-skol' => array('label' => 'Uzavření škol',),
	'valecne-pomniky' => array('label' => 'Válečné pomníky',),
	'vandalstvi' => array('label' => 'Vandalství',),
	'vazici-mosty' => array('label' => 'Vážící mosty',),
	'vedecke-sluzby' => array('label' => 'Vědecké služby',),
	'velke-nebo-objemne-polozky' => array('label' => 'Velké nebo objemné položky',),
	'venkov' => array('label' => 'Venkov',),
	'venkovni-strediska-aktivit' => array('label' => 'Venkovní střediska aktivit',),
	'verejna-doprava' => array('label' => 'Veřejná doprava',),
	'verejna-prava-na-cestu' => array('label' => 'Veřejná práva na cestu',),
	'verejne-chodniky' => array('label' => 'Veřejné chodníky',),
	'verejne-toalety' => array('label' => 'Veřejné toalety',),
	'vesele-zvire' => array('label' => 'Veselé zvíře',),
	'vice-domu-s-obsazenim' => array('label' => 'Více domů s obsazením',),
	'vlaky' => array('label' => 'Vlaky',),
	'volby' => array('label' => 'Volby',),
	'volby---hlasovani' => array('label' => 'Volby - hlasování',),
	'volebni-rejstrik' => array('label' => 'Volební rejstřík',),
	'volna-mista-rady' => array('label' => 'Volná místa Rady',),
	'volna-mista-v-rade' => array('label' => 'Volná místa v radě',),
	'volna-pracovni-mista' => array('label' => 'Volná pracovní místa',),
	'volne-mista-pro-skolky' => array('label' => 'Volné místa pro školky',),
	'volnocasove-aktivity' => array('label' => 'Volnočasové aktivity',),
	'vozidla---opustena' => array('label' => 'Vozidla - opuštěná',),
	'vstup-do-zeme' => array('label' => 'Vstup do země',),
	'vule-a-svedectvi---rady' => array('label' => 'Vůle a svědectví - rady',),
	'vybaveni-pro-osoby-se-zdravotnim-postizenim' => array('label' => 'Vybavení pro osoby se zdravotním postižením',),
	'vybory' => array('label' => 'Výbory',),
	'vydavani-kopii-osvedceni' => array('label' => 'Vydávání kopií osvědčení',),
	'vyhody' => array('label' => 'Výhody',),
	'vyhody---bydleni' => array('label' => 'Výhody - bydlení',),
	'vyhody---dan-ze-strany-rady' => array('label' => 'Výhody - daň ze strany Rady',),
	'vyhody-podvodu' => array('label' => 'Výhody podvodu',),
	'vyhozene-obrubniky-a-prekrizeni' => array('label' => 'Vyhozené obrubníky a překřížení',),
	'vylouceni-ze-skoly' => array('label' => 'Vyloučení ze školy',),
	'vymeny-jehel' => array('label' => 'Výměny jehel',),
	'vysokoskolske-vzdelani' => array('label' => 'Vysokoškolské vzdělání',),
	'vytapeni---v-zime-udrzovano-v-teple' => array('label' => 'Vytápění - v zimě udržováno v teple',),
	'vyvoj-sportu' => array('label' => 'Vývoj sportu',),
	'vyvoj-umeni' => array('label' => 'Vývoj umění',),
	'vzdelani' => array('label' => 'Vzdělání',),
	'vzdelani---dospeli' => array('label' => 'Vzdělání - dospělí',),
	'vzdelavani-dospelych' => array('label' => 'Vzdělávání dospělých',),
	'vzdelavejte-sve-dite-doma' => array('label' => 'Vzdělávejte své dítě doma',),
	'zablokovane-chodniky' => array('label' => 'Zablokované chodníky',),
	'zadavani-verejnych-zakazek-na-verejne-zakazky' => array('label' => 'Zadávání veřejných zakázek na veřejné zakázky',),
	'zadavani-zakazek---jednani-s-radou' => array('label' => 'Zadávání zakázek - jednání s Radou',),
	'zahradni-odpad' => array('label' => 'Zahradní odpad',),
	'zahradni-udrzba' => array('label' => 'Zahradní údržba',),
	'zachovani' => array('label' => 'Zachování',),
	'zakaz-koureni' => array('label' => 'Zákaz kouření',),
	'zamestnavani-a-skoleni-pro-zdravotne-postizene-osoby' => array('label' => 'Zaměstnávání a školení pro zdravotně postižené osoby',),
	'zanedbani---deti' => array('label' => 'Zanedbání - děti',),
	'zanedbani---dospeli' => array('label' => 'Zanedbání - dospělí',),
	'zapisy-a-programy-zasedani-rady' => array('label' => 'Zápisy a programy zasedání rady',),
	'zaplaveni' => array('label' => 'Zaplavení',),
	'zaregistrujte-se-k-hlasovani' => array('label' => 'Zaregistrujte se k hlasování',),
	'zaregistrujte-si-narozeni' => array('label' => 'Zaregistrujte si narození',),
	'zaregistrujte-smrt' => array('label' => 'Zaregistrujte smrt',),
	'zavislost' => array('label' => 'Závislost',),
	'zaznamy-zaku' => array('label' => 'Záznamy žáků',),
	'zdarma-skolni-jidla' => array('label' => 'Zdarma školní jídla',),
	'zdravi-a-bezpecnost' => array('label' => 'Zdraví a bezpečnost',),
	'zdravi-a-dobre-zivotni-podminky-zvirat' => array('label' => 'Zdraví a dobré životní podmínky zvířat',),
	'zdravotni-poradenstvi' => array('label' => 'Zdravotní poradenství',),
	'zeme-s-otevrenym-pristupem' => array('label' => 'Země s otevřeným přístupem',),
	'zemedelci-trhy' => array('label' => 'Zemědělci trhy',),
	'zemedelstvi' => array('label' => 'Zemědělství',),
	'zimni---rady-o-udrzeni-v-teple' => array('label' => 'Zimní - rady o udržení v teple',),
	'zimni-sterkovani' => array('label' => 'Zimní štěrkování',),
	'zlocin' => array('label' => 'Zločin',),
	'zlocin-z-nenavisti' => array('label' => 'Zločin z nenávisti',),
	'znecisteni-ovzdusi' => array('label' => 'Znečištění ovzduší',),
	'znecisteni-psu' => array('label' => 'Znečištění psů',),
	'znecisteni-zeme' => array('label' => 'Znečíštění země',),
	'znecisteni-zvirat' => array('label' => 'Znečištění zvířat',),
	'zneuzivani-a-zanedbavani---dospeli' => array('label' => 'Zneužívání a zanedbávání - dospělí',),
	'zneuzivani-a-zanedbavani-deti' => array('label' => 'Zneužívání a zanedbávání dětí',),
	'zneuzivani-a-zanedbavani-dospelych' => array('label' => 'Zneužívání a zanedbávání dospělých',),
	'zoo-licence' => array('label' => 'Zoo licence',),
	'zoologicke-zahrady-a-farmarske-parky' => array('label' => 'Zoologické zahrady a farmářské parky',),
	'zpravy,-agendy-a-zapisy-vyboru' => array('label' => 'Zprávy, agendy a zápisy výborů',),
	'zranitelni-dospeli' => array('label' => 'Zranitelní dospělí',),
	'zvlastni-vzdelavaci-potreby---doprava-do-skoly' => array('label' => 'Zvláštní vzdělávací potřeby - doprava do školy',),
	'zvyhodnene-cestovani' => array('label' => 'Zvýhodněné cestování',),
	'zadatele-o-azyl' => array('label' => 'Žadatelé o azyl',),
	'zlute-cary' => array('label' => 'Žluté čáry',),
	),
'financni-prostredky' => array(
'uspora/snizeni-vydaju' => array('label' => 'Úspora/snížení výdajů',),
'vydelek-/-genenrovani-zisku' => array('label' => 'Výdělek / genenrování zisku',),
'zvyseni-narokovanych-davek' => array('label' => 'Zvýšení nárokovaných dávek',),
'zlepseni-pristupu-k-levnejsim-statkum-a-sluzbam' => array('label' => 'Zlepšení přístupu k levnějším statkům a službám',),
'zdarma-poskytovany-/-dotovany-pristup-k-internatu' => array('label' => 'Zdarma poskytovaný / dotovaný přístup k internatu',),
'zdarma-poskytovane-/-dotovane-pocitace/vybaveni' => array('label' => 'Zdarma poskytované / dotované počítače/vybavení',),
'zmirneni-chudoby' => array('label' => 'Zmírnění chudoby',),
'podpora-financniho-vzdelavani-a-dovednosti' => array('label' => 'Podpora finančního vzdělávání a dovedností',),
'vylepseny-pristup-k-fin.-informacim-a-dluh.-poradenstvi' => array('label' => 'Vylepšený přístup k fin. informacím a dluh. poradenství',),
'stimulace-neformalni-ekonomiky' => array('label' => 'Stimulace neformální ekonomiky',),
'lepsi-pristup-k-financnim-sluzbam' => array('label' => 'Lepší přístup k finančním službám',),
	),
'vzdelani-a-dovednosti' => array(
	'zlepseni-skolnich-vysledku' => array('label' => 'Zlepšení školních výsledků',),
'zlepseni-zakladnich-dovednosti-(gramotnost)' => array('label' => 'Zlepšení základních dovedností (gramotnost)',),
'zlepseni-jazykovych-dovednosti' => array('label' => 'Zlepšení jazykových dovedností',),
'zlepseni-pocitacove-gramotnosti' => array('label' => 'Zlepšení počítačové gramotnosti',),
'zlepseni-prenositelnych-dovednosti' => array('label' => 'Zlepšení přenositelných dovedností',),
'ziskani-kvalifikace' => array('label' => 'Získání kvalifikace',),
'postupovani' => array('label' => 'Postupování',),
'ucast-ve-vzdelavacich-cinnostech' => array('label' => 'Účast ve vzdělávacích činnostech',),
'posilena-podpora-pro-specialni-potreby' => array('label' => 'Posílená podpora pro speciální potřeby',),
'vyssi-podpora-ucitelum' => array('label' => 'Vyšší podpora učitelům',),
'vyssi-podpora-skolitelum' => array('label' => 'Vyšší podpora školitelům',),
'vetsi-spokojenost-s-praci-u-pedagogu' => array('label' => 'Větší spokojenost s prací u pedagogů',),
),
'zdravi-a-pece' => array(
	'zlepseni-zdravotniho-stavu' => array('label' => 'Zlepšení zdravotního stavu',),
'lepsi-psychicke-zdravi' => array('label' => 'Lepší psychické zdraví',),
'lepsi-prevence-proti-nemocem' => array('label' => 'Lepší prevence proti nemocem',),
'lepsi-pristup-k-peci' => array('label' => 'Lepší přístup k péči',),
'rychlejsi-pristup-k-lecbe-a-lekum' => array('label' => 'Rychlejší přístup k léčbě a lékům',),
'lepsi-socialni-pece' => array('label' => 'Lepší sociální péče',),
'zvysena-podpora-nezavisleho-bydleni' => array('label' => 'Zvýšená podpora nezávislého bydlení',),
'vyssi-spokojenost-pacientu' => array('label' => 'Vyšší spokojenost pacientů',),
'vyssi-efektivita-lecby' => array('label' => 'Vyšší efektivita léčby',),
'posilena-podpora-postizenym' => array('label' => 'Posílená podpora postiženým',),
'lepsi-pristup-k-zdravotnim-informacim' => array('label' => 'Lepší přístup k zdravotním informacím',),
'vetsi-volba-a-komfort' => array('label' => 'Větší volba a komfort',),
'vyssi-podpora-osob-se-zavislosti' => array('label' => 'Vyšší podpora osob se závislostí',),
'lepsi-podpora-pro-pracovniky-ve-zdravotnictvi' => array('label' => 'Lepší podpora pro pracovníky ve zdravotnictví',),
'lepsi-podpora-pro-pracovniky-v-socialni-sfere' => array('label' => 'Lepší podpora pro pracovníky v sociální sféře',),
'vyssi-spokojenost-s-praci-pecovatelu-ve-zdravotn.' => array('label' => 'Vyšší spokojenost s prací pečovatelů ve zdravotn.',),
),
'bydleni' => array(
	'zvyseny-vyber-ze-socialniho-bydleni' => array('label' => 'Zvýšený výběr ze sociálního bydlení',),
'vylepsena-sluzba-nabidek' => array('label' => 'Vylepšená služba nabídek',),
'zkvalitneni-sluzeb-oprav-a-udrzby' => array('label' => 'Zkvalitnění služeb oprav a údržby',),
'zlepsena-interakce-mezi-najemci-a-najemniky' => array('label' => 'Zlepšená interakce mezi nájemci a nájemníky',),
'spokojenost-najemniku' => array('label' => 'Spokojenost nájemníků',),
'pristup-najemniku-k-it-a-podpore' => array('label' => 'Přístup nájemníků k IT a podpoře',),
'kvalitnejsi-informace-a-poradenstvi-v-oblasti-bydleni' => array('label' => 'Kvalitnější informace a poradenství v oblasti bydlení',),
'vyssi-podpora-bezdomovcu' => array('label' => 'Vyšší podpora bezdomovců',),
),
'sluzby' => array(
	'vetsi-moznost-volby' => array('label' => 'Větší možnost volby',),
'zvysena-dostupnost' => array('label' => 'Zvýšená dostupnost',),
'zvysena-uzitnost' => array('label' => 'Zvýšená užitnost',),
'zvysena-perzonalizace' => array('label' => 'Zvýšená perzonalizace',),
'rychlejsi-reakce' => array('label' => 'Rychlejší reakce',),
'kvalitnejsi-informace' => array('label' => 'Kvalitnější informace',),
'lepsi-komunikace' => array('label' => 'Lepší komunikace',),
'zvysena-spokojenost' => array('label' => 'Zvýšená spokojenost',),
'vyssi-spokojenost-zamestnancu' => array('label' => 'Vyšší spokojenost zaměstnanců',),
'vyssi-spolehlivost-a-konzistentnost' => array('label' => 'Vyšší spolehlivost a konzistentnost',),
'vyssi-bezpecnost' => array('label' => 'Vyšší bezpečnost',),
'vyssi-integrrace-s-jinymi-sluzbami' => array('label' => 'Vyšší integrrace s jinými službami',),
'uspora-casu' => array('label' => 'Úspora času',),
'lepsi-sdileni-informaci' => array('label' => 'Lepší sdílení informací',),
'vyssi-efektivita-zamestnancu' => array('label' => 'Vyšší efektivita zaměstnanců',),
'vyssi-efektivita-zdroju' => array('label' => 'Vyšší efektivita zdrojů',),
'vyssi-efektivita-uspory-nakladu' => array('label' => 'Vyšší efektivita úspory nákladů',),
'efektivnejsi-vyuziti-dovednosti' => array('label' => 'Efektivnější využití dovedností',),
'rozsireni-dosahu-rovnosti' => array('label' => 'Rozšíření dosahu rovnosti',),
'lepsi-povedomi' => array('label' => 'Lepší povědomí',),
'vyssi-schopnost-vyuziti-sluzeb' => array('label' => 'Vyšší schopnost využití služeb',),
'lepsi-vyuziti-sluzeb' => array('label' => 'Lepší využití služeb',),
'vetsi-recyklace/uchovani' => array('label' => 'Větší recyklace/uchování',),
'vyssi-angazovanost-a-projednavani' => array('label' => 'Vyšší angažovanost a projednávání',),
'lepsi-transparentnost' => array('label' => 'Lepší transparentnost',),
'snizene-riziko' => array('label' => 'Snížené riziko',),
'vyssi-pruznost' => array('label' => 'Vyšší pružnost',),
'silnejsi-spoluprace' => array('label' => 'Silnější spolupráce',),
'lepsi-povest-a-duvera' => array('label' => 'Lepší pověst a důvěra',),
),
'kriminalita-a-bezpecnost' => array(
	'snizeni-kriminality' => array('label' => 'Snížení kriminality',),
'redukce-protispolecenskeho-chovani' => array('label' => 'Redukce protispolečenského chování',),
'snizeni-obav-z-kriminality' => array('label' => 'Snížení obav z kriminality',),
'snizena-opakovana-trestna-cinnost' => array('label' => 'Snížená opakovaná trestná činnost',),
'zvysena-podpora-obeti' => array('label' => 'Zvýšená podpora obětí',),
'lepsi-bezpecnost' => array('label' => 'Lepší bezpečnost',),
'zvysena-podpora-pro-stycne-pracovniky' => array('label' => 'Zvýšená podpora pro styčné pracovníky',),
),
'zivotni-prostredi' => array(
	'zvysene-povedomi-o-uhlikove-stope' => array('label' => 'Zvýšené povědomí o uhlíkové stopě',),
'zvysena-poradni-a-informacni-cinnost' => array('label' => 'Zvýšená poradní a informační činnost',),
'redukce-uhlikove-stopy' => array('label' => 'Redukce uhlíkové stopy',),
'zvysena-flexibilita-prace' => array('label' => 'Zvýšená flexibilita práce',),
'posileni-informaci-o-doprave' => array('label' => 'Posílení informací o dopravě',),
'vylepseny-presun-k-verejne-doprave' => array('label' => 'Vylepšený přesun k veřejné dopravě',),
'zvysena-odolnost-proti-environmentalnim-rizikum' => array('label' => 'Zvýšená odolnost proti environmentálním rizikům',),
'zlepseni-ohlasovacich-sluzeb-o-env.-zlocinech' => array('label' => 'Zlepšení ohlašovacích služeb o env. Zločinech',),
'lepsi-schopnost-reagovat-na-env.-zlociny' => array('label' => 'Lepší schopnost reagovat na env. Zločiny',),
'lepsi-pristup-ke-zdravym-potravinam' => array('label' => 'Lepší přístup ke zdravým potravinám',),
),
'komunita' => array(
	'zvysena-soudrznost-komunity' => array('label' => 'Zvýšená soudržnost komunity',),
'zvysena-ucast-komunity' => array('label' => 'Zvýšená účast komunity',),
'zvysena-dobrovolnicka-cinnost' => array('label' => 'Zvýšená dobrovolnická činnost',),
'vyssi-spokojenost-komunity' => array('label' => 'Vyšší spokojenost komunity',),
'silnejsi-komunita-a-sektor-dobrovolniku' => array('label' => 'Silnější komunita a sektor dobrovolníků',),
'zvysena-kapacita-it-v-sektoru-komunit-a-dobrovolniku' => array('label' => 'Zvýšená kapacita IT v sektoru komunit a dobrovolníků',),
'vyssi-integrace-komunity' => array('label' => 'Vyšší integrace komunity',),
),
'zivotni-podminky' => array(
	'zvysena-podpora-pro-zaliby-a-zajmy' => array('label' => 'Zvýšená podpora pro záliby a zájmy',),
'zvysena-interakce-s-rodinami' => array('label' => 'Zvýšená interakce s rodinami',),
'zvysena-interakce-s-prateli' => array('label' => 'Zvýšená interakce s přáteli',),
'vyssi-interakce-s-komunitou' => array('label' => 'Vyšší interakce s komunitou',),
'zvysena-spokojenost' => array('label' => 'Zvýšená spokojenost',),
'vyssi-sebevedomi' => array('label' => 'Vyšší sebevědomí',),
'posilena-socialni-a-podpurna-sit' => array('label' => 'Posílená sociální a podpůrná síť',),
'lepsi-kvalita-zivota' => array('label' => 'Lepší kvalita života',),
'zlepseni-zivotnich-dovednosti' => array('label' => 'Zlepšení životních dovedností',),
'vetsi-nezavislost' => array('label' => 'Větší nezávislost',),
'lepsi-vize' => array('label' => 'Lepší vize',),
'lepsi-projednavani' => array('label' => 'Lepší projednávání',),
'lepsi-komunikace' => array('label' => 'Lepší komunikace',),
'vyssi-zrucnost' => array('label' => 'Vyšší zručnost',),
'vyssi-pochopeni-a-poznavani' => array('label' => 'Vyšší pochopení a poznávání',),
'zvysena-ucast-ve-verejnem-rozhodovani' => array('label' => 'Zvýšená účast ve veřejném rozhodování',),
),
'rovnost-a-postaveni' => array(
	'snizena-izolovanost' => array('label' => 'Snížená izolovanost',),
'zvyseny-pocit-sounalezitosti' => array('label' => 'Zvýšený pocit sounáležitosti',),
'vylepseny-pristup-k-sluzbam' => array('label' => 'Vylepšený přístup k službám',),
'vice-zivotnich-prilezitosti' => array('label' => 'Více životních příležitostí',),
'posilena-schopnost-vyjadrovani' => array('label' => 'Posílená schopnost vyjadřování',),
'lepsi-pristup-k-it-vybaveni' => array('label' => 'Lepší přístup k IT vybavení',),
'pristup-k-technolgiim-pres-zastupujici-uzivatele' => array('label' => 'Přístup k technolgiím přes zastupující uživatele',),
'kvalitnejsi-predavani-informaci' => array('label' => 'Kvalitnější předávání informací',),
'lepsi-prioritizace-sluzeb' => array('label' => 'Lepší prioritizace služeb',),
'lepsi-cileni-sluzeb' => array('label' => 'Lepší cílení služeb',),
'vyssi-aplikace-kanalu-pristupu' => array('label' => 'Vyšší aplikace kanálů přístupu',),
'lepsi-prijimani-sluzeb' => array('label' => 'Lepší přijímání služeb',),
'rovnost-verejnych-sluzeb-ve-venkovskych-oblastech' => array('label' => 'Rovnost veřejných služeb ve venkovských oblastech',),
'posileni-informovanosti-a-vedeni' => array('label' => 'Posílení informovanosti a vedení',),
),
'zamestnanost-a-ekonomika' => array(
'zvysena-schopnost-hledat-praci-a-zadat-o-ni' => array('label' => 'Zvýšená schopnost hledat práci a žádat o ni',),
'pracovni-praxe' => array('label' => 'Pracovní praxe',),
'ziskani-prace-/-zvyseni-zamestnanosti' => array('label' => 'Získání práce / zvýšení zaměstnanosti',),
'zahajeni-podnikani-/-zvyseni-mnozstvi-startupu' => array('label' => 'Zahájení podnikání / zvýšení množství startupů',),
'lepsi-pocitacova-gramotnost-zamestnancu' => array('label' => 'Lepší počítačová gramotnost zaměstnanců',),
'lepsi-it-infrastruktura' => array('label' => 'Lepší IT infrastruktura',),
'posileni-lokalni-ekonomicke-diverzity' => array('label' => 'Posílení lokální ekonomické diverzity',),
'vetsi-konkurence-a-konkurenceschopnost' => array('label' => 'Větší konkurence a konkurenceschopnost',),
'pristup-k-internetu-v-komunite' => array('label' => 'Přístup k internetu v komunitě',),),

'pristup-k-digitalnim-technologiim' => array(
	'pristup-k-internetu-v-domacnostech' => array('label' => 'Přístup k internetu v domácnostech',),
'pristup-k-moderni-sirokopasmove-komunikaci' => array('label' => 'Přístup k moderní širokopásmové komunikaci',),
'pristup-k-sirokopasmove-komunikaci-dalsi-generace' => array('label' => 'Přístup k širokopásmové komunikaci další generace',),
'pristup-k-pocitaci' => array('label' => 'Přístup k počítači',),
'postoje-k-internetu' => array('label' => 'Postoje k internetu',),
'snizene-obavy-z-technologie' => array('label' => 'Snížené obavy z technologie',),
'skoleni-a-podpora' => array('label' => 'Školení a podpora',),
'zakladni-it-dovednosti' => array('label' => 'Základní IT dovednosti',),
'vyssi-duvera-v-internet' => array('label' => 'Vyšší důvěra v internet',),
'zavisli-a-uzivatele-navykovych-latek' => array('label' => 'Závislí a uživatelé návykových látek',),
),

'vyloucene-skupiny' => array(
	'pecovatele' => array('label' => 'Pečovatelé',),
	'ohrozene-deti' => array('label' => 'Ohrožené děti',),
	'postizeni' => array('label' => 'Postižení',),
	'studenti-predcasne-opoustejici-skolu' => array('label' => 'Studenti předčasně opouštějící školu',),
	'etnicke-a-kulturni-mensiny' => array('label' => 'Etnické a kulturní menšiny',),
	'casto-se-stehujici-obyvatele' => array('label' => 'Často se stěhující obyvatelé',),
	'bezdomovci' => array('label' => 'Bezdomovci',),
	'negramotni' => array('label' => 'Negramotní',),
	'izolovani' => array('label' => 'Izolovaní',),
	'lesby,-gayove,-bisexualove' => array('label' => 'Lesby, gayové, bisexuálové',),
	'domacnosti-s-nizkymi-prijmny' => array('label' => 'Domácnosti s nízkými příjmny',),
	'necesky-mluvici' => array('label' => 'Nečesky mluvící',),
	'delikventi' => array('label' => 'Delikventi',),
	'starsi-lide' => array('label' => 'Starší lidé',),
	'lide-pobirajici-davky' => array('label' => 'Lidé pobírající dávky',),
	'nezdravi' => array('label' => 'Nezdraví',),
	'problemove-rodiny' => array('label' => 'Problémové rodiny',),
	'uprchlici-a-zadatele-o-azyl' => array('label' => 'Uprchlíci a žadatelé o azyl',),
	'odloucene-venkovske-oblasti' => array('label' => 'Odloučené venkovské oblasti',),
	'rodiny-s-jednim-rodicem' => array('label' => 'Rodiny s jedním rodičem',),
	'zaskolaci' => array('label' => 'Záškoláci',),
	'nezamestnani' => array('label' => 'Nezaměstnaní',),
	'obeti-zlocinu' => array('label' => 'Oběti zločinu',),
	'mladi-znevyhodneni' => array('label' => 'Mladi znevýhodnění',),
	'rodina' => array('label' => 'Rodina',),
	),

'rodina-pratele-pecovatele' => array(
	'pritel' => array('label' => 'Přítel',),
'soused' => array('label' => 'Soused',),
'neplaceny-pecovatel' => array('label' => 'Neplacený pečovatel',),
'neplaceny-dobrovolnik' => array('label' => 'Neplacený dobrovolník',),
'skupiny-v-komunite' => array('label' => 'Skupiny v komunitě',),
),

'chude-osoby-spolecenstvi' => array(
	'rezidentni-organizace' => array('label' => 'Rezidentní organizace',),
'sidliste' => array('label' => 'Sídliště',),
'mladeznicke-skupiny' => array('label' => 'Mládežnické skupiny',),
'dobrocinne-organizace-(neposkytujici-sluzbu)' => array('label' => 'Dobročinné organizace (neposkytující službu)',),
'skupina-dobrovolniku' => array('label' => 'Skupina dobrovolníků',),
'chranene-bydleni' => array('label' => 'Chráněné bydlení',),
'pracovniici-s-mladistvymi' => array('label' => 'Pracovniíci s mladistvými',),
),

'stycni-pracovnici' => array(
	'dospely-pecovatel' => array('label' => 'Dospělý pečovatel',),
'socialni-pracovnik' => array('label' => 'Socíální pracovník',),
'dochazkove-zdravotnicke-sluzby' => array('label' => 'Docházkové zdravotnické služby',),
'pracovnik-zachranne-sluzby' => array('label' => 'Pracovník záchranné služby',),
'urednik-pres-bydleni' => array('label' => 'Úředník přes bydlení',),
'pracovnik-komunitniho-rozvoje' => array('label' => 'Pracovník komunitního rozvoje',),
'ucitele-a-skolitele' => array('label' => 'Učitelé a školitelé',),
'straznik' => array('label' => 'Strážník',),
'policie' => array('label' => 'Policie',),
'vazebni-dustojnik' => array('label' => 'Vazební důstojník',),
'skupina-pro-mladistve-delikventy' => array('label' => 'Skupina pro mladistvé delikventy',),
'poradenska-sluzba-pro-mladistve' => array('label' => 'Poradenská služba pro mladistvé',),
'vezensky-strazny' => array('label' => 'Vězeňský strážný',),
'zamestnanost' => array('label' => 'Zaměstnanost',),
'urednici-pres-davky' => array('label' => 'Úředníci přes dávky',),
'stycny-urednik' => array('label' => 'Styčný úředník',),
'pozary-a-bezpecnost' => array('label' => 'Požáry a bezpečnost',),
'urednik-pro-zivotni-prostredi' => array('label' => 'Úředník pro životní prostředí',),
'urednik-planovani' => array('label' => 'Úředník plánování',),
'zamestnanci-kontaktniho-centra' => array('label' => 'Zaměstnanci kontaktního centra',),
'vychovny-poradce' => array('label' => 'Výchovný poradce',),
'mentor' => array('label' => 'Mentor',),
'terapeut' => array('label' => 'Terapeut',),
'knihovnik' => array('label' => 'Knihovník',),
'zdravotni-sestra' => array('label' => 'Zdravotní sestra',),
'obvodni-lekar' => array('label' => 'Obvodní lékař',),
'lekarnik' => array('label' => 'Lékárník',),
'sluzby-pece-o-dospele' => array('label' => 'Služby péče o dospělé',),
),

'lokalni-organy' => array(
	'tym-vyuky-dospelych' => array('label' => 'Tým výuky dospělých',),
'sluzby-pro-deti' => array('label' => 'Služby pro děti',),
'komunikace-a-vztahy-s-verejnosti' => array('label' => 'Komunikace a vztahy s veřejností',),
'kontaktni-centrum' => array('label' => 'Kontaktní centrum',),
'oddeleni-ekonomickeho-rozvoje' => array('label' => 'Oddělení ekonomického rozvoje',),
'vzdelavaci-sluzby' => array('label' => 'Vzdělávací služby',),
'environmentalni-sluzby' => array('label' => 'Environmentální služby',),
'oddeleni-financi' => array('label' => 'Oddělení financí',),
'zdravotni-sluzby' => array('label' => 'Zdravotní služby',),
'urady-pro-bydleni' => array('label' => 'Úřady pro bydlení',),
'oddeleni-lidskych-zdroju' => array('label' => 'Oddělení lidských zdrojů',),
'oddeleni-it' => array('label' => 'Oddělení IT',),
'knihovny' => array('label' => 'Knihovny',),
'rizeni-vykonnosti' => array('label' => 'Řízení výkonnosti',),
'oddeleni-planovani' => array('label' => 'Oddělení plánování',),
'skupina-senior-managementu' => array('label' => 'Skupina senior managementu',),
'socialni-sluzby' => array('label' => 'Sociální služby',),
'oddeleni-prepravy' => array('label' => 'Oddělení přepravy',),
'poskytovatel-verejneho-sektoru' => array('label' => 'Poskytovatel veřejného sektoru',),
),

'organizace-poskytujici-sluzby' => array(
	'poskytovatelska-organizace-soukromeho-sektoru' => array('label' => 'Poskytovatelská organizace soukromého sektoru',),
'poskytovatelska-organizace-terciarniho-sektoru' => array('label' => 'Poskytovatelská organizace terciárního sektoru',),
'poskytovatelska-organizace-akademickeho-sektoru' => array('label' => 'Poskytovatelská organizace akademického sektoru',),
'poskytovatelska-organizace---socialni-podnik' => array('label' => 'Poskytovatelská organizace - socíální podnik',),
'business-organisation' => array('label' => 'Business Organisation',),
),

'lokalni-partneri' => array(
	'komunitni-organizace' => array('label' => 'Komunitní organizace',),
'hasicsky-zachranny-sbor' => array('label' => 'Hasičský záchranný sbor',),
'ustav-vysokoskolskeho-vzdelavani' => array('label' => 'Ústav vysokoškolského vzdělávání',),
'urad-prace' => array('label' => 'Úřad práce',),
'rada-pro-vyuku-a-dovednosti' => array('label' => 'Rada pro výuku a dovednosti',),
'poskytovatel-primarni-pece' => array('label' => 'Poskytovatel primární péče',),
'policie' => array('label' => 'Policie',),
'vazebni-sluzby' => array('label' => 'Vazební služby',),
'bytove-druzstvo' => array('label' => 'Bytové družstvo',),
'nabozenske-instituce' => array('label' => 'Náboženské instituce',),
'terciarni-sektor' => array('label' => 'Terciární sektor',),
'lokalni-ekonomika' => array('label' => 'Lokální ekonomika',),
),

'ekonomika-a-spolecnost' => array(
	'regionalni-ekonomika' => array('label' => 'Regionální ekonomika',),
'narodni-ekonomika' => array('label' => 'Národní ekonomika',),
'spolecnost' => array('label' => 'Společnost',),
),

	);

	// Lazy - I didn't feel like manually converting to key-value structure
	$departments_sorted = array();
	foreach ( $schools as $s_key => $s_label ) {
		// Skip if we only want one school
		if ( $school && $s_key != $school ) {
			continue;
		}

		$departments_sorted[ $s_key ] = array();
	}

	foreach ( $all_departments as $s_key => $depts ) {
		// Skip if we only want one school
		if ( $school && $s_key != $school ) {
			continue;
		}

		foreach ( $depts as $dept_name => $dept ) {
			if ( 'short' == $label_type ) {
				$d_label = isset( $dept['short_label'] ) ? $dept['short_label'] : $dept['label'];
			} else {
				$d_label = $dept['label'];
			}

			$departments_sorted[ $s_key ][ $dept_name ] = $d_label;
		}
	}

	if ( $school ) {
		$departments_sorted = $departments_sorted[ $school ];
	}

	return $departments_sorted;
}

function wds_new_group_type() {
	if ( isset( $_GET['new'] ) && 'true' === $_GET['new'] && isset( $_GET['type'] ) ) {
		global $bp;
		unset( $bp->groups->current_create_step );
		unset( $bp->groups->completed_create_steps );

		setcookie( 'bp_new_group_id', false, time() - 1000, COOKIEPATH );
		setcookie( 'bp_completed_create_steps', false, time() - 1000, COOKIEPATH );
		setcookie( 'wds_bp_group_type', $_GET['type'], time() + 20000, COOKIEPATH );
		bp_core_redirect( $bp->root_domain . '/' . $bp->groups->slug . '/create/step/group-details/?type=' . $_GET['type'] );
	}
}
add_action( 'init', 'wds_new_group_type' );

add_action( 'wp_ajax_wds_load_group_type', 'wds_load_group_type' );
add_action( 'wp_ajax_nopriv_wds_load_group_type', 'wds_load_group_type' );

function wds_load_group_type( $group_type ) {
	global $wpdb, $bp, $user_ID;

	$return = '';

	if ( $group_type ) {
		$echo = true;
		$return = '<input type="hidden" name="group_type" value="' . ucfirst( $group_type ) . '">';
	} else {
		$group_type = $_POST['group_type'];
	}

	$wds_group_school = groups_get_groupmeta( bp_get_current_group_id(), 'wds_group_school' );
	$wds_group_school = explode( ',', $wds_group_school );

	$account_type = xprofile_get_field_data( 'Account Type', bp_loggedin_user_id() );

	$return = '<div class="panel panel-default">';

	$return .= '<div class="panel-heading">Témata';
	if ( openlab_is_school_required_for_group_type( $group_type ) && ( 'staff' != strtolower( $account_type ) || is_super_admin( get_current_user_id() ) ) ) {
		$return .= ' <span class="required">(vyžadováno)</span>';
	}
	$return .= '</div><div class="panel-body">';
	$return .= '<table>';

	$return .= '<tr class="school-tooltip"><td colspan="2">';

	// associated school/dept tooltip
	switch ( $group_type ) {
		case 'course' :
			$return .= '<p class="ol-tooltip">Pokud je váš kurz spojen s některými z uvedených témat a oblastí, vyberte prosím zaškrtávací políčka níže.</p>';
			break;
		case 'portfolio' :
			$return .= '<p class="ol-tooltip">Pokud je vaše portfolio ' . openlab_get_portfolio_label() . ' spojeno s některými z uvedených témat a oblastí, vyberte prosím zaškrtávací políčka níže.</p>';
			break;
		case 'project' :
			$return .= '<p class="ol-tooltip">Je váš projekt spojen s jedním nebo více tématy?</p>';
			break;
		case 'club' :
			$return .= '<p class="ol-tooltip">Je vaše skupina spojena s jedním nebo více tématy?</p>';
			break;
	}

	$return .= '</td></tr>';

	$return .= '<tr><td class="school-inputs" colspan="2">';

	// If this is a Portfolio, we'll pre-check the school and department
	// of the logged-in user
	$checked_array = array( 'schools' => array(), 'departments' => array() );
	if ( 'portfolio' == $group_type && bp_is_group_create() ) {
		$account_type = strtolower( bp_get_profile_field_data( array(
			'field' => 'Account Type',
			'user_id' => bp_loggedin_user_id(),
		) ) );
		$dept_field = 'student' == $account_type ? 'Major Program of Study' : 'Department';

		$user_department = bp_get_profile_field_data( array(
			'field' => $dept_field,
			'user_id' => bp_loggedin_user_id(),
		) );

		if ( $user_department ) {
			$all_departments = openlab_get_department_list();
			foreach ( $all_departments as $school => $depts ) {
				if ( in_array( $user_department, $depts ) ) {
					$checked_array['schools'][] = $school;
					$checked_array['departments'][] = array_search( $user_department, $depts );
					break;
				}
			}
		}
	} else {
		foreach ( (array) $wds_group_school as $school ) {
			$checked_array['schools'][] = $school;
		}
	}

	$onclick = 'onclick="wds_load_group_departments();"';

	$schools = openlab_get_school_list();
	foreach ( $schools as $school_key => $school_label ) {
		$return .= sprintf( '<label><input type="checkbox" id="school_%s" name="wds_group_school[]" value="%s" ' . $onclick . ' ' . checked( in_array( $school_key, $checked_array['schools'] ), true, false ) . '> %s</label>', esc_attr( $school_key ), esc_attr( $school_key ), esc_html( $school_label ) );
	}

	$return .= '</td>';
	$return .= '</tr>';

	// For the love of Pete, it's not that hard to cast variables
	$wds_faculty = $wds_course_code = $wds_section_code = $wds_semester = $wds_year = $wds_course_html = '';

	if ( bp_get_current_group_id() ) {
		$wds_faculty = groups_get_groupmeta( bp_get_current_group_id(), 'wds_faculty' );
		$wds_course_code = groups_get_groupmeta( bp_get_current_group_id(), 'wds_course_code' );
		$wds_section_code = groups_get_groupmeta( bp_get_current_group_id(), 'wds_section_code' );
		$wds_semester = groups_get_groupmeta( bp_get_current_group_id(), 'wds_semester' );
		$wds_year = groups_get_groupmeta( bp_get_current_group_id(), 'wds_year' );
		$wds_course_html = groups_get_groupmeta( bp_get_current_group_id(), 'wds_course_html' );
	}

	$last_name = xprofile_get_field_data( 'Last Name', $bp->loggedin_user->id );

	$faculty_name = bp_core_get_user_displayname( bp_loggedin_user_id() );
	$return .= '<input type="hidden" name="wds_faculty" value="' . esc_attr( $faculty_name ) . '">';

	$return .= '<tr class="department-title">';

	$return .= '<td colspan="2" class="block-title italics">Oblasti';
	if ( openlab_is_school_required_for_group_type( $group_type ) && 'staff' != strtolower( $account_type ) ) {
		$return .= ' <span class="required">(vyžadováno)</span>';
	}
	$return .= '</td></tr>';
	$return .= '<tr class="departments"><td id="departments_html" colspan="2" aria-live="polite"></td>';
	$return .= '</tr>';

	$return .= '</table></div></div>';

	if ( 'course' == $group_type ) {

		$return .= '<div class="panel panel-default">';
		$return .= '<div class="panel-heading">Informace ke kurzu</div>';
		$return .= '<div class="panel-body"><table>';

		$return .= '<tr><td colspan="2"><p class="ol-tooltip">Následující pole nejsou povinné, ale s těchto informacemi bude pro ostatní snazší tento kurz najít.</p></td></tr>';

		$return .= '<tr class="additional-field course-code-field">';
		$return .= '<td class="additional-field-label"><label class="passive" for="wds_course_code">Kód kurzu:</label></td>';
		$return .= '<td><input class="form-control" type="text" id="wds_course_code" name="wds_course_code" value="' . $wds_course_code . '"></td>';
		$return .= '</tr>';

		$return .= '<tr class="additional-field section-code-field">';
		$return .= '<td class="additional-field-label"><label class="passive" for="wds_section_code">Typ:</label></td>';
		$return .= '<td><input class="form-control" type="text" id="wds_section_code" name="wds_section_code" value="' . $wds_section_code . '"></td>';
		$return .= '</tr>';

		$return .= '<tr class="additional-field semester-field">';
		$return .= '<td class="additional-field-label"><label class="passive" for="wds_semester">Kvartál:</label></td>';
		$return .= '<td><select class="form-control" id="wds_semester" name="wds_semester">';
		$return .= '<option value="">--zvolte--';

		$checked = $Spring = $Summer = $Fall = $Winter = '';

		if ( $wds_semester == 'Spring' ) {
			$Spring = 'selected';
		} elseif ( $wds_semester == 'Summer' ) {
			$Summer = 'selected';
		} elseif ( $wds_semester == 'Fall' ) {
			$Fall = 'selected';
		} elseif ( $wds_semester == 'Winter' ) {
			$Winter = 'selected';
		}

		$return .= '<option value="Spring" ' . $Spring . '>Spring';
		$return .= '<option value="Summer" ' . $Summer . '>Summer';
		$return .= '<option value="Fall" ' . $Fall . '>Fall';
		$return .= '<option value="Winter" ' . $Winter . '>Winter';
		$return .= '</select></td>';
		$return .= '</tr>';

		$return .= '<tr class="additional-field year-field">';
		$return .= '<td class="additional-field-label"><label class="passive" for="wds_year">Rok:</label></td>';
		$return .= '<td><input class="form-control" type="text" id="wds_year" name="wds_year" value="' . $wds_year . '"></td>';
		$return .= '</tr>';

		$return .= '<tr class="additional-field additional-description-field">';
		$return .= '<td colspan="2" class="additional-field-label"><label class="passive" for="additional-desc-html">Doplňující popis/HTML:</label></td></tr>';
		$return .= '<tr><td colspan="2"><textarea class="form-control" name="wds_course_html" id="additional-desc-html">' . $wds_course_html . '</textarea></td></tr>';
		$return .= '</tr>';

		$return .= '</table></div></div><!--.panel-->';
	}

	$return .= '<script>wds_load_group_departments();</script>';

	if ( $echo ) {
		return $return;
	} else {
		$return = str_replace( "'", "\'", $return );
		die( "document.getElementById( 'wds-group-type' ).innerHTML='$return'" );
	}
}

/**
 * Are School and Department required for this group type?
 */
function openlab_is_school_required_for_group_type( $group_type = '' ) {
	$req_types = array( 'course', 'portfolio' );

	return in_array( $group_type, $req_types );
}

/**
 * School and Department are required for courses and portfolios
 *
 * Hook in before BP's core function, so we get first dibs on returning errors
 */
function openlab_require_school_and_department_for_groups() {
	global $bp;

	// Only check at group creation and group admin
	if ( ! bp_is_group_admin_page() && ! bp_is_group_create() ) {
		return;
	}

	// Don't check at deletion time ( groan )
	if ( bp_is_group_admin_screen( 'delete-group' ) ) {
		return;
	}

	// No payload, no check
	if ( empty( $_POST ) ) {
		return;
	}

	if ( bp_is_group_create() ) {
		$group_type = isset( $_GET['type'] ) ? $_GET['type'] : '';
		$redirect = bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/create/step/group-details/';
	} else {
		$group_type = openlab_get_current_group_type();
		$redirect = bp_get_group_permalink( groups_get_current_group() ) . 'admin/edit-details/';
	}

	$account_type = xprofile_get_field_data( 'Account Type', bp_loggedin_user_id() );

	if ( openlab_is_school_required_for_group_type( $group_type ) && (bp_is_action_variable( 'group-details', 1 ) || bp_is_action_variable( 'edit-details' )) ) {

		if ( empty( $_POST['wds_group_school'] ) || empty( $_POST['wds_departments'] ) || ! isset( $_POST['wds_group_school'] ) || ! isset( $_POST['wds_departments'] ) ) {
			//bp_core_add_message( 'You must provide a school and department.', 'error' );
			bp_core_add_message( 'Téma a oblast nemohou být prázdné.', 'error' );
			bp_core_redirect( $redirect );
		}
	}
}

add_action( 'bp_actions', 'openlab_require_school_and_department_for_groups', 5 );


//Save Group Meta
add_action( 'groups_group_after_save', 'wds_bp_group_meta_save' );

function wds_bp_group_meta_save( $group ) {
	global $wpdb, $user_ID, $bp;

	$is_editing = false;

	if ( isset( $_POST['_wp_http_referer'] ) && strpos( $_POST['_wp_http_referer'], 'edit-details' ) !== false ) {
		$is_editing = true;
	}

	if ( isset( $_POST['group_type'] ) ) {
		groups_update_groupmeta( $group->id, 'wds_group_type', $_POST['group_type'] );

		if ( 'course' == $_POST['group_type'] ) {
			$is_course = true;
		}
	}

	if ( isset( $_POST['wds_faculty'] ) ) {
		groups_update_groupmeta( $group->id, 'wds_faculty', $_POST['wds_faculty'] );
	}
	if ( isset( $_POST['wds_group_school'] ) ) {
		$wds_group_school = implode( ',', $_POST['wds_group_school'] );

		//fully deleting and then adding in school metadata so schools can be unchecked
		groups_delete_groupmeta( $group->id, 'wds_group_school' );
		groups_add_groupmeta( $group->id, 'wds_group_school', $wds_group_school, true );
	} elseif ( ! isset( $_POST['wds_group_school'] ) ) {
		//allows user to uncheck all schools (projects and clubs only)
		//on edit only
		if ( $is_editing ) {
			groups_update_groupmeta( $group->id, 'wds_group_school', '' );
		}
	}

	if ( isset( $_POST['wds_departments'] ) ) {
		$wds_departments = implode( ',', $_POST['wds_departments'] );

		//fully deleting and then adding in department metadata so departments can be unchecked
		groups_delete_groupmeta( $group->id, 'wds_departments' );
		groups_add_groupmeta( $group->id, 'wds_departments', $wds_departments, true );
	} elseif ( ! isset( $_POST['wds_departments'] ) ) {
		//allows user to uncheck all departments (projects and clubs only)
		//on edit only
		if ( $is_editing ) {
			groups_update_groupmeta( $group->id, 'wds_departments', '' );
		}
	}

	if ( isset( $_POST['wds_course_code'] ) ) {
		groups_update_groupmeta( $group->id, 'wds_course_code', $_POST['wds_course_code'] );
	}
	if ( isset( $_POST['wds_section_code'] ) ) {
		groups_update_groupmeta( $group->id, 'wds_section_code', $_POST['wds_section_code'] );
	}
	if ( isset( $_POST['wds_semester'] ) ) {
		groups_update_groupmeta( $group->id, 'wds_semester', $_POST['wds_semester'] );
	}
	if ( isset( $_POST['wds_year'] ) ) {
		groups_update_groupmeta( $group->id, 'wds_year', $_POST['wds_year'] );
	}
	if ( isset( $_POST['wds_course_html'] ) ) {
		groups_update_groupmeta( $group->id, 'wds_course_html', $_POST['wds_course_html'] );
	}
	if ( isset( $_POST['group_project_type'] ) ) {
		groups_update_groupmeta( $group->id, 'wds_group_project_type', $_POST['group_project_type'] );
	}

	// Clear the active semester cache
	delete_transient( 'openlab_active_semesters' );

	// Site association. Non-portfolios have the option of not having associated sites (thus the
	// wds_website_check value).
	if ( isset( $_POST['wds_website_check'] ) ||
			openlab_is_portfolio( $group->id )
	) {

		if ( isset( $_POST['new_or_old'] ) && 'new' == $_POST['new_or_old'] ) {

			// Create a new site
			ra_copy_blog_page( $group->id );
		} elseif ( isset( $_POST['new_or_old'] ) && 'old' == $_POST['new_or_old'] && isset( $_POST['groupblog-blogid'] ) ) {

			// Associate an existing site
			groups_update_groupmeta( $group->id, 'wds_bp_group_site_id', (int) $_POST['groupblog-blogid'] );
		} elseif ( isset( $_POST['new_or_old'] ) && 'external' == $_POST['new_or_old'] && isset( $_POST['external-site-url'] ) ) {

			// External site
			// Some validation
			$url = openlab_validate_url( $_POST['external-site-url'] );
			groups_update_groupmeta( $group->id, 'external_site_url', $url );

			if ( ! empty( $_POST['external-site-type'] ) ) {
				groups_update_groupmeta( $group->id, 'external_site_type', $_POST['external-site-type'] );
			}

			if ( ! empty( $_POST['external-posts-url'] ) ) {
				groups_update_groupmeta( $group->id, 'external_site_posts_feed', $_POST['external-posts-url'] );
			}

			if ( ! empty( $_POST['external-comments-url'] ) ) {
				groups_update_groupmeta( $group->id, 'external_site_comments_feed', $_POST['external-comments-url'] );
			}
		}

		if ( openlab_is_portfolio( $group->id ) ) {
			openlab_associate_portfolio_group_with_user( $group->id, bp_loggedin_user_id() );
		}
	}

	// Site privacy
	if ( isset( $_POST['blog_public'] ) ) {
		$blog_public = (float) $_POST['blog_public'];
		$site_id = openlab_get_site_id_by_group_id( $group->id );

		if ( $site_id ) {
			update_blog_option( $site_id, 'blog_public', $blog_public );
		}
	}

	// Portfolio list display
	if ( isset( $_POST['group-portfolio-list-heading'] ) ) {
		$enabled = ! empty( $_POST['group-show-portfolio-list'] ) ? 'yes' : 'no';
		groups_update_groupmeta( $group->id, 'portfolio_list_enabled', $enabled );

		groups_update_groupmeta( $group->id, 'portfolio_list_heading', strip_tags( stripslashes( $_POST['group-portfolio-list-heading'] ) ) );
	}

	// Library tools display.
	$library_tools_enabled = ! empty( $_POST['group-show-library-tools'] ) ? 'yes' : 'no';
	groups_update_groupmeta( $group->id, 'library_tools_enabled', $library_tools_enabled );

	// Feed URLs ( step two of group creation )
	if ( isset( $_POST['external-site-posts-feed'] ) || isset( $_POST['external-site-comments-feed'] ) ) {
		groups_update_groupmeta( $group->id, 'external_site_posts_feed', $_POST['external-site-posts-feed'] );
		groups_update_groupmeta( $group->id, 'external_site_comments_feed', $_POST['external-site-comments-feed'] );
	}
}

function wds_get_by_meta( $limit = null, $page = null, $user_id = false, $search_terms = false, $populate_extras = true, $meta_key = null, $meta_value = null ) {
	global $wpdb, $bp;

	if ( $limit && $page ) {
		$pag_sql = $wpdb->prepare( ' LIMIT %d, %d', intval( ( $page - 1 ) * $limit ), intval( $limit ) );
	} else { $pag_sql = '';
	}

	if ( ! is_user_logged_in() || ( ! is_super_admin() && ( $user_id != $bp->loggedin_user->id ) ) ) {
		$hidden_sql = " AND g.status != 'hidden'";
	} else { $hidden_sql = '';
	}

	if ( $search_terms ) {
		$search_terms = like_escape( $wpdb->escape( $search_terms ) );
		$search_sql = " AND ( g.name LIKE '%%{$search_terms}%%' OR g.description LIKE '%%{$search_terms}%%' )";
	} else {
		$search_sql = '';
	}

	if ( $user_id ) {
		$user_id = $wpdb->escape( $user_id );
		$paged_groups = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE gm3.meta_key='$meta_key' AND gm3.meta_value='$meta_value' AND g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 ORDER BY g.name ASC {$pag_sql}" );
		$total_groups = $wpdb->get_var( "SELECT COUNT( DISTINCT m.group_id ) FROM {$bp->groups->table_name_members} m LEFT JOIN {$bp->groups->table_name_groupmeta} gm ON m.group_id = gm.group_id INNER JOIN {$bp->groups->table_name} g ON m.group_id = g.id WHERE gm.meta_key = 'last_activity' {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0" );
	} else {
		$paged_groups = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bp->groups->table_name} g WHERE gm3.meta_key='$meta_key' AND gm3.meta_value='$meta_value' AND g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' {$hidden_sql} {$search_sql} ORDER BY g.name ASC {$pag_sql}" );
		$total_groups = $wpdb->get_var( "SELECT COUNT( DISTINCT g.id ) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bp->groups->table_name} g WHERE gm3.meta_key='$meta_key' AND gm3.meta_value='$meta_value' AND g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' {$hidden_sql} {$search_sql}" );
	}
	//echo $total_groups;
	if ( ! empty( $populate_extras ) ) {
		foreach ( (array) $paged_groups as $group ) {
			$group_ids[] = $group->id;
		}
		$group_ids = $wpdb->escape( join( ',', (array) $group_ids ) );
		$paged_groups = BP_Groups_Group::get_group_extras( $paged_groups, $group_ids, 'newest' );
	}

	return array( 'groups' => $paged_groups, 'total' => $total_groups );
}

//Copy the group blog template
function ra_copy_blog_page( $group_id ) {
	global $bp, $wpdb, $current_site, $user_email, $base, $user_ID;
	$blog = isset( $_POST['blog'] ) ? $_POST['blog'] : array();

	if ( ! empty( $blog['domain'] ) && $group_id ) {
		$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';
		if ( ! defined( 'SUNRISE' ) || $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->dmtable}'" ) != $wpdb->dmtable ) {
			$join = $where = '';
		} else {
			$join = "LEFT JOIN {$wpdb->dmtable} d ON d.blog_id = b.blog_id ";
			$where = 'AND d.domain IS NULL ';
		}

		$src_id = intval( $_POST['source_blog'] );

		//$domain = sanitize_user( str_replace( '/', '', $blog[ 'domain' ] ) );
		//$domain = str_replace( ".","", $domain );
		$domain = friendly_url( $blog['domain'] );
		$email = sanitize_email( $user_email );
		$title = $_POST['group-name'];

		if ( ! $src_id ) {
			$msg = __( 'Select a source blog.' );
		} elseif ( empty( $domain ) || empty( $email ) ) {
			$msg = __( 'Missing blog address or email address.' );
		} elseif ( ! is_email( $email ) ) {
			$msg = __( 'Invalid email address' );
		} else {
			if ( constant( 'VHOST' ) == 'yes' ) {
				$newdomain = $domain . '.' . $current_site->domain;
				$path = $base;
			} else {
				$newdomain = $current_site->domain;
				$path = $base . $domain . '/';
			}

			$password = 'N/A';
			$user_id = email_exists( $email );
			if ( ! $user_id ) {
				$password = generate_random_password();
				$user_id = wpmu_create_user( $domain, $password, $email );
				if ( false == $user_id ) {
					$msg = __( 'There was an error creating the user' );
				} else {
					wp_new_user_notification( $user_id, $password );
				}
			}
			$wpdb->hide_errors();
			$new_id = wpmu_create_blog( $newdomain, $path, $title, $user_id, array( 'public' => 1 ), $current_site->id );
			$id = $new_id;
			$wpdb->show_errors();
			if ( ! is_wp_error( $id ) ) { //if it dont already exists then move over everything
				$current_user = get_userdata( bp_loggedin_user_id() );

				groups_update_groupmeta( $group_id, 'wds_bp_group_site_id', $id );
				/* if ( get_user_option( $user_id, 'primary_blog' ) == 1 )
                  update_user_option( $user_id, 'primary_blog', $id, true ); */
				$content_mail = sprintf( __( "New site created by %1$1s\n\nAddress: http://%2$2s\nName: %3$3s" ), $current_user->user_login, $newdomain . $path, stripslashes( $title ) );
				wp_mail( get_site_option( 'admin_email' ), sprintf( __( '[%s] New Blog Created' ), $current_site->site_name ), $content_mail, 'From: "Site Admin" <' . get_site_option( 'admin_email' ) . '>' );
				wpmu_welcome_notification( $id, $user_id, $password, $title, array( 'public' => 1 ) );
				$msg = __( 'Site Created' );
				// now copy
				$blogtables = $wpdb->base_prefix . $src_id . '_';
				$newtables = $wpdb->base_prefix . $new_id . '_';
				$query = "SHOW TABLES LIKE '{$blogtables}%'";
				//				var_dump( $query );
				$tables = $wpdb->get_results( $query, ARRAY_A );
				if ( $tables ) {
					reset( $tables );
					$create = array();
					$data = array();
					$len = strlen( $blogtables );
					$create_col = 'Create Table';
					// add std wp tables to this array
					$wptables = array(
					$blogtables . 'links',
					$blogtables . 'postmeta',
					$blogtables . 'posts',
						$blogtables . 'terms',
					$blogtables . 'term_taxonomy',
					$blogtables . 'term_relationships',
					);
					for ( $i = 0; $i < count( $tables ); $i++ ) {
						$table = current( $tables[ $i ] );
						if ( substr( $table, 0, $len ) == $blogtables ) {
							if ( ! ( $table == $blogtables . 'options' || $table == $blogtables . 'comments' ) ) {
								$create[ $table ] = $wpdb->get_row( "SHOW CREATE TABLE {$table}" );
								$data[ $table ] = $wpdb->get_results( "SELECT * FROM {$table}", ARRAY_A );
							}
						}
					}
					//					var_dump( $create );
					if ( $data ) {
						switch_to_blog( $src_id );
						$src_upload_dir = wp_upload_dir();
						$src_url = get_option( 'siteurl' );
						$option_query = "SELECT option_name, option_value FROM {$wpdb->options}";
						restore_current_blog();
						$new_url = get_blog_option( $new_id, 'siteurl' );
						foreach ( $data as $k => $v ) {
							$table = str_replace( $blogtables, $newtables, $k );
							if ( in_array( $k, $wptables ) ) { // drop new blog table
								$query = "DROP TABLE IF EXISTS {$table}";
								$wpdb->query( $query );
							}
							$key = (array) $create[ $k ];
							$query = str_replace( $blogtables, $newtables, $key[ $create_col ] );
							$wpdb->query( $query );
							$is_post = ( $k == $blogtables . 'posts' );
							if ( $v ) {
								foreach ( $v as $row ) {
									if ( $is_post ) {
										$row['guid'] = str_replace( $src_url, $new_url, $row['guid'] );
										$row['post_content'] = str_replace( $src_url, $new_url, $row['post_content'] );
										$row['post_author'] = $user_id;
									}
									$wpdb->insert( $table, $row );
								}
							}
						}

						// copy media
						OpenLab_Clone_Course_Site::copyr( $src_upload_dir['basedir'], str_replace( $src_id, $new_id, $src_upload_dir['basedir'] ) );

						// update options
						$skip_options = array(
						'admin_email',
						'blogname',
						'blogdescription',
						'cron',
						'db_version',
						'doing_cron',
							'fileupload_url',
						'home',
						'new_admin_email',
						'nonce_salt',
						'random_seed',
						'rewrite_rules',
						'secret',
						'siteurl',
						'upload_path',
							'upload_url_path',
						"{$wpdb->base_prefix}{$src_id}_user_roles",
						);
						$options = $wpdb->get_results( $option_query );
						//new blog stuff
						if ( $options ) {
							switch_to_blog( $new_id );
							update_option( 'wds_bp_group_id', $group_id );

							$old_relative_url = set_url_scheme( $src_url, 'relative' );
							$new_relative_url = set_url_scheme( $new_url, 'relative' );
							foreach ( $options as $o ) {
								//								var_dump( $o );
								if ( ! in_array( $o->option_name, $skip_options ) && substr( $o->option_name, 0, 6 ) != '_trans' ) {
									// Imperfect but we generally won't have nested arrays.
									if ( is_serialized( $o->option_value ) ) {
										$new_option_value = unserialize( $o->option_value );
										foreach ( $new_option_value as $key => &$value ) {
											if ( is_string( $value ) ) {
												$value = str_replace( $old_relative_url, $new_relative_url, $value );
											}
										}
									} else {
										$new_option_value = str_replace( $old_relative_url, $new_relative_url, $o->option_value );
									}
									update_option( $o->option_name, $new_option_value );
								}
							}
							if ( version_compare( $GLOBALS['wp_version'], '2.8', '>' ) ) {
								set_transient( 'rewrite_rules', '' );
							} else {
								update_option( 'rewrite_rules', '' );
							}

							restore_current_blog();
							$msg = __( 'Blog Copied' );
						}
					}
				}
			} else {
				$msg = $id->get_error_message();
			}
		}
	}
}

//this is a function for sanitizing the website name
//source http://cubiq.org/the-perfect-php-clean-url-generator
function friendly_url( $str, $replace = array(), $delimiter = '-' ) {
	if ( ! empty( $replace ) ) {
		$str = str_replace( (array) $replace, ' ', $str );
	}

	if ( function_exists( 'iconv' ) ) {
		$clean = iconv( 'UTF-8', 'ASCII//TRANSLIT', $str );
	} else {
		$clean = $str;
	}

	$clean = preg_replace( '/[^a-zA-Z0-9\/_|+ -]/', '', $clean );
	$clean = strtolower( trim( $clean, '-' ) );
	$clean = preg_replace( '/[\/_|+ -]+/', $delimiter, $clean );

	return $clean;
}

/**
 * Don't let anyone access the Create A Site page
 *
 * @see http://openlab.citytech.cuny.edu/redmine/issues/160
 */
function openlab_redirect_from_site_creation() {
	if ( bp_is_create_blog() ) {
		bp_core_redirect( bp_get_root_domain() );
	}
}

add_action( 'bp_actions', 'openlab_redirect_from_site_creation' );

/**
 * Load custom language file for BP Group Documents
 */
load_textdomain( 'bp-group-documents', WP_CONTENT_DIR . '/languages/buddypress-group-documents-en_CAC.mo' );

/**
 * Allow super admins to change user type on Dashboard
 */
class OpenLab_Change_User_Type {

	public static function init() {
		static $instance;

		if ( ! is_super_admin() ) {
			return;
		}

		if ( empty( $instance ) ) {
			$instance = new OpenLab_Change_User_Type;
		}
	}

	function __construct() {
		add_action( 'show_user_profile', array( $this, 'markup' ) );
		add_action( 'edit_user_profile', array( $this, 'markup' ) );

		add_action( 'personal_options_update', array( $this, 'save' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save' ) );
	}

	function markup( $user ) {
		$account_type = xprofile_get_field_data( 'Account Type', $user->ID );

		$field_id = xprofile_get_field_id_from_name( 'Account Type' );
		$field = new BP_XProfile_Field( $field_id );
		$options = $field->get_children();
		?>

		<h3>Typ účtu na portále OpenLab</h3>

		<table class="form-table">
			<tr>
				<th>
					<label for="openlab_account_type">Typ účtu</label>
				</th>

				<td>
					<?php foreach ( $options as $option ) : ?>
						<input type="radio" name="openlab_account_type" value="<?php echo $option->name ?>" <?php checked( $account_type, $option->name ) ?>> <?php echo $option->name ?><br />
						<?php endforeach ?>
				</td>
			</tr>
		</table>

		<?php
	}

	function save( $user_id ) {
		if ( isset( $_POST['openlab_account_type'] ) ) {
			xprofile_set_field_data( 'Account Type', $user_id, $_POST['openlab_account_type'] );
		}
	}

}

add_action( 'admin_init', array( 'OpenLab_Change_User_Type', 'init' ) );

/**
 * Only allow the site's faculty admin to see full names on the Dashboard
 *
 * See http://openlab.citytech.cuny.edu/redmine/issues/165
 */
function openlab_hide_fn_ln( $check, $object, $meta_key, $single ) {
	global $wpdb, $bp;

	if ( is_admin() && in_array( $meta_key, array( 'first_name', 'last_name' ) ) ) {

		// Faculty only
		$account_type = xprofile_get_field_data( 'Account Type', get_current_user_id() );
		if ( 'faculty' != strtolower( $account_type ) ) {
			return '';
		}

		// Make sure it's the right faculty member
		$group_id = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM {$bp->groups->table_name_groupmeta} WHERE meta_key = 'wds_bp_group_site_id' AND meta_value = '%d' LIMIT 1", get_current_blog_id() ) );

		if ( ! empty( $group_id ) && ! groups_is_user_admin( get_current_user_id(), (int) $group_id ) ) {
			return '';
		}

		// Make sure it's a course
		$group_type = groups_get_groupmeta( $group_id, 'wds_group_type' );

		if ( 'course' != strtolower( $group_type ) ) {
			return '';
		}
	}

	return $check;
}

//add_filter( 'get_user_metadata', 'openlab_hide_fn_ln', 9999, 4 );

/**
 * No access redirects should happen from wp-login.php
 */
add_filter( 'bp_no_access_mode', create_function( '', 'return 2;' ) );

/**
 * Don't auto-link items in profiles
 * Hooked to bp_screens so that it gets fired late enough
 */
add_action( 'bp_screens', create_function( '', "remove_filter( 'bp_get_the_profile_field_value', 'xprofile_filter_link_profile_data', 9, 2 );" ) );

//Change "Group" to something else
class buddypress_Translation_Mangler {
	/*
     * Filter the translation string before it is displayed.
     *
     * This function will choke if we try to load it when not viewing a group page or in a group loop
     * So we bail in cases where neither of those things is present, by checking $groups_template
     */

	static function filter_gettext( $translation, $text, $domain ) {
		global $bp, $groups_template;

		if ( 'buddypress' != $domain ) {
			return $translation;
		}

		$group_id = 0;
		if ( ! bp_is_group_create() ) {
			if ( ! empty( $groups_template->group->id ) ) {
				$group_id = $groups_template->group->id;
			} elseif ( ! empty( $bp->groups->current_group->id ) ) {
				$group_id = $bp->groups->current_group->id;
			}
		}

		if ( $group_id ) {
			$grouptype = groups_get_groupmeta( $group_id, 'wds_group_type' );
		} elseif ( isset( $_GET['type'] ) ) {
			$grouptype = $_GET['type'];
		} else {
			return $translation;
		}

		$uc_grouptype = ucfirst( $grouptype );
		$translations = get_translations_for_domain( 'buddypress' );

		switch ( $text ) {
			case 'Forum':
				return $translations->translate( 'Forum' );
				break;
			case 'Group Forum':
				return $translations->translate( "$uc_grouptype Discussion" );
				break;
			case 'Group Forum Directory':
				return $translations->translate( '' );
				break;
			case 'Group Forums Directory':
				return $translations->translate( 'Group Discussions Directory' );
				break;
			case 'Join Group':
				return $translations->translate( 'Join Now!' );
				break;
			case 'You successfully joined the group.':
				return $translations->translate( 'You successfully joined!' );
				break;
			case 'Recent Discussion':
				return $translations->translate( 'Recent Forum Discussion' );
				break;
			case 'said ':
				return $translations->translate( '' );
				break;
			case 'Create a Group':
				return $translations->translate( 'Create a ' . $uc_grouptype );
				break;
			case 'Manage' :
				return $translations->translate( 'Nastavení' );
				break;
		}
		return $translation;
	}

}

function openlab_launch_translator() {
	add_filter( 'gettext', array( 'buddypress_Translation_Mangler', 'filter_gettext' ), 10, 4 );
	add_filter( 'gettext', array( 'bbPress_Translation_Mangler', 'filter_gettext' ), 10, 4 );
	add_filter( 'gettext_with_context', 'openlab_gettext_with_context', 10, 4 );
}

add_action( 'bp_setup_globals', 'openlab_launch_translator' );

function openlab_gettext_with_context( $translations, $text, $context, $domain ) {
	if ( 'buddypress' !== $domain ) {
		return $translations;
	}
	switch ( $text ) {
		case 'Manage' :
			if ( 'My Group screen nav' === $context ) {
				return 'Nastavení';
			}
			break;
	}
	return $translations;
}

class bbPress_Translation_Mangler {

	static function filter_gettext( $translation, $text, $domain ) {
		if ( 'bbpress' != $domain ) {
			return $translation;
		}
		$translations = get_translations_for_domain( 'buddypress' );
		switch ( $text ) {
			case 'Forum':
				return $translations->translate( 'Diskusní fórum' );
				break;
		}
		return $translation;
	}

}

class buddypress_ajax_Translation_Mangler {
	/*
     * Filter the translation string before it is displayed.
     */

	static function filter_gettext( $translation, $text, $domain ) {
		$translations = get_translations_for_domain( 'buddypress' );
		switch ( $text ) {
			case 'Friendship Requested':
			case 'Add Friend':
				return $translations->translate( 'Friend' );
				break;
		}
		return $translation;
	}

}

function openlab_launch_ajax_translator() {
	add_filter( 'gettext', array( 'buddypress_ajax_Translation_Mangler', 'filter_gettext' ), 10, 4 );
}

add_action( 'bp_setup_globals', 'openlab_launch_ajax_translator' );

/**
 * When a user attempts to visit a blog, check to see if the user is a member of the
 * blog's associated group. If so, ensure that the member has access.
 *
 * This function should be deprecated when a more elegant solution is found.
 * See http://openlab.citytech.cuny.edu/redmine/issues/317 for more discussion.
 */
function openlab_sync_blog_members_to_group() {
	global $wpdb, $bp;

	// No need to continue if the user is not logged in, if this is not an admin page, or if
	// the current blog is not private
	$blog_public = get_option( 'blog_public' );
	if ( ! is_user_logged_in() || ! is_admin() || (int) $blog_public < 0 ) {
		return;
	}

	$user_id = get_current_user_id();
	$userdata = get_userdata( $user_id );

	// Is the user already a member of the blog?
	if ( empty( $userdata->caps ) ) {

		// Is this blog associated with a group?
		$group_id = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM {$bp->groups->table_name_groupmeta} WHERE meta_key = 'wds_bp_group_site_id' AND meta_value = %d", get_current_blog_id() ) );

		if ( $group_id ) {

			// Is this user a member of the group?
			if ( groups_is_user_member( $user_id, $group_id ) ) {

				// Figure out the status
				if ( groups_is_user_admin( $user_id, $group_id ) ) {
					$status = 'administrator';
				} elseif ( groups_is_user_mod( $user_id, $group_id ) ) {
					$status = 'editor';
				} else {
					$status = 'author';
				}

				// Add the user to the blog
				add_user_to_blog( get_current_blog_id(), $user_id, $status );

				// Redirect to avoid errors
				echo '<script type="text/javascript">window.location="' . $_SERVER['REQUEST_URI'] . '";</script>';
			}
		}
	}
}

//add_action( 'init', 'openlab_sync_blog_members_to_group', 999 ); // make sure BP is loaded

/**
 * Interfere in the comment posting process to allow for duplicates on the same post
 *
 * Borrowed from http://www.strangerstudios.com/blog/2010/10/duplicate-comment-detected-it-looks-as-though-you%E2%80%99ve-already-said-that/
 * See http://openlab.citytech.cuny.edu/redmine/issues/351
 */
function openlab_enable_duplicate_comments_preprocess_comment( $comment_data ) {
	if ( is_user_logged_in() ) {
		//add some random content to comment to keep dupe checker from finding it
		$random = md5( time() );
		$comment_data['comment_content'] .= 'disabledupes{' . $random . '}disabledupes';
	}

	return $comment_data;
}

add_filter( 'preprocess_comment', 'openlab_enable_duplicate_comments_preprocess_comment' );

/**
 * Strips disabledupes string from comments. See previous function.
 */
function openlab_enable_duplicate_comments_comment_post( $comment_id ) {
	global $wpdb;

	if ( is_user_logged_in() ) {

		//remove the random content
		$comment_content = $wpdb->get_var( "SELECT comment_content FROM $wpdb->comments WHERE comment_ID = '$comment_id' LIMIT 1" );
		$comment_content = preg_replace( '/disabledupes\{.*\}disabledupes/', '', $comment_content );
		$wpdb->query( "UPDATE $wpdb->comments SET comment_content = '" . $wpdb->escape( $comment_content ) . "' WHERE comment_ID = '$comment_id' LIMIT 1" );

		clean_comment_cache( array( $comment_id ) );
	}
}

add_action( 'comment_post', 'openlab_enable_duplicate_comments_comment_post', 1 );

/**
 * Adds the URL of the user profile to the New User Registration admin emails
 *
 * See http://openlab.citytech.cuny.edu/redmine/issues/334
 */
function openlab_newuser_notify_siteadmin( $message ) {

	// Due to WP lameness, we have to hack around to get the username
	preg_match( '|New User: ( .* )|', $message, $matches );

	if ( ! empty( $matches ) ) {
		$user = get_user_by( 'login', $matches[1] );
		$profile_url = bp_core_get_user_domain( $user->ID );

		if ( $profile_url ) {
			$message_a = explode( 'Remote IP', $message );
			$message = $message_a[0] . 'Profile URL: ' . $profile_url . "\n" . 'Remote IP' . $message_a[1];
		}
	}

	return $message;
}

add_filter( 'newuser_notify_siteadmin', 'openlab_newuser_notify_siteadmin' );

/**
 * Get the word for a group type
 *
 * Groups fall into three categories: Project, Club, and Course. Use this function to get the word
 * corresponding to the group type, with the appropriate case and count.
 *
 * @param $case 'lower' ( course ), 'title' ( Course ), 'upper' ( COURSE )
 * @param $count 'single' ( course ), 'plural' ( courses )
 * @param $group_id Will default to the current group id
 */
function openlab_group_type( $case = 'lower', $count = 'single', $group_id = 0 ) {
	if ( ! $case || ! in_array( $case, array( 'lower', 'title', 'upper' ) ) ) {
		$case = 'lower';
	}

	if ( ! $count || ! in_array( $count, array( 'single', 'plural' ) ) ) {
		$case = 'single';
	}

	// Set a group id. The elseif statements allow for cascading logic; if the first is not
	// found, fall to the second, etc.
	$group_id = (int) $group_id;
	if ( ! $group_id && $group_id = bp_get_current_group_id() ) {

	} // current group
	elseif ( ! $group_id && $group_id = bp_get_new_group_id() ) {

	} // new group
	elseif ( ! $group_id && $group_id = bp_get_group_id() ) {

	}         // group in loop

	$group_type = groups_get_groupmeta( $group_id, 'wds_group_type' );

	if ( empty( $group_type ) ) {
		return '';
	}

	switch ( $case ) {
		case 'lower' :
			$group_type = strtolower( $group_type );
			break;

		case 'title' :
			$group_type = ucwords( $group_type );
			break;

		case 'upper' :
			$group_type = strtoupper( $group_type );
			break;
	}

	switch ( $count ) {
		case 'single' :
			break;

		case 'plural' :
			$group_type .= 's';
			break;
	}

	return $group_type;
}

/**
 * Utility function for getting a default user id when none has been passed to the function
 *
 * The logic is this: If there is a displayed user, return it. If not, check to see whether we're
 * in a members loop; if so, return the current member. If it's still 0, check to see whether
 * we're on a my-* page; if so, return the loggedin user id. Otherwise, return 0.
 *
 * Note that we have to manually check the $members_template variable, because
 * bp_get_member_user_id() doesn't do it properly.
 *
 * @return int
 */
function openlab_fallback_user() {
	global $members_template;

	$user_id = bp_displayed_user_id();

	if ( ! $user_id && ! empty( $members_template ) && isset( $members_template->member ) ) {
		$user_id = bp_get_member_user_id();
	}

	if ( ! $user_id && ( is_page( 'my-courses' ) || is_page( 'my-clubs' ) || is_page( 'my-projects' ) || is_page( 'my-sites' ) ) ) {
		$user_id = bp_loggedin_user_id();
	}

	return (int) $user_id;
}

/**
 * Utility function for getting a default group id when none has been passed to the function
 *
 * The logic is this: If this is a group page, return the current group id. If this is the group
 * creation process, return the new_group_id. If this is a group loop, return the id of the group
 * show during this iteration
 *
 * @return int
 */
function openlab_fallback_group() {
	global $groups_template;

	if ( ! bp_is_active( 'groups' ) ) {
		return 0;
	}

	$group_id = bp_get_current_group_id();

	if ( ! $group_id && bp_is_group_create() ) {
		$group_id = bp_get_new_group_id();
	}

	if ( ! $group_id && ! empty( $groups_template ) && isset( $groups_template->group ) ) {
		$group_id = $groups_template->group->id;
	}

	return (int) $group_id;
}

/**
 * Is this my profile?
 *
 * We need a specialized function that returns true when bp_is_my_profile() does, or in addition,
 * when on a my-* page
 *
 * @return bool
 */
function openlab_is_my_profile() {
	global $bp;

	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( bp_is_my_profile() ) {
		return true;
	}

	if ( is_page( 'my-courses' ) || is_page( 'my-clubs' ) || is_page( 'my-projects' ) || is_page( 'my-sites' ) ) {
		return true;
	}

	//for the group creating pages
	if ( $bp->current_action == 'create' ) {
		return true;
	}

	return false;
}

/**
 * On saving settings, save our additional fields
 */
function openlab_addl_settings_fields() {
	global $bp;

	$fname = isset( $_POST['fname'] ) ? $_POST['fname'] : '';
	$lname = isset( $_POST['lname'] ) ? $_POST['lname'] : '';
	$account_type = isset( $_POST['account_type'] ) ? $_POST['account_type'] : '';

	// Don't let this continue if a password error was recorded
	if ( isset( $bp->template_message_type ) && 'error' == $bp->template_message_type && 'No changes were made to your account.' != $bp->template_message ) {
		return;
	}

	if ( empty( $fname ) || empty( $lname ) ) {
		bp_core_add_message( 'First Name and Last Name are required fields', 'error' );
	} else {
		xprofile_set_field_data( 'First Name', bp_displayed_user_id(), $fname );
		xprofile_set_field_data( 'Last Name', bp_displayed_user_id(), $lname );

		bp_core_add_message( __( 'Your settings have been saved.', 'buddypress' ), 'success' );
	}

	if ( ! empty( $account_type ) ) {
		//saving account type for students or alumni
		$types = array( 'Student', 'Alumni' );
		$account_type = in_array( $_POST['account_type'], $types ) ? $_POST['account_type'] : 'Student';
		$user_id = bp_displayed_user_id();
		$current_type = openlab_get_displayed_user_account_type();

		// Only students and alums can do this
		if ( in_array( $current_type, $types ) ) {
			xprofile_set_field_data( 'Account Type', bp_displayed_user_id(), $account_type );
		}
	}

	bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_settings_slug() . '/general' ) );
}

add_action( 'bp_core_general_settings_after_save', 'openlab_addl_settings_fields' );

/**
 * A small hack to ensure that the 'Create A New Site' option is disabled on my-sites.php
 */
function openlab_disable_new_site_link( $registration ) {
	if ( '/wp-admin/my-sites.php' == $_SERVER['SCRIPT_NAME'] ) {
		$registration = 'none';
	}

	return $registration;
}

add_filter( 'site_option_registration', 'openlab_disable_new_site_link' );

function openlab_set_default_group_subscription_on_creation( $group_id ) {
	groups_update_groupmeta( $group_id, 'ass_default_subscription', 'supersub' );
}

add_action( 'groups_created_group', 'openlab_set_default_group_subscription_on_creation' );

/**
 * Brackets in password reset emails cause problems in some clients. Remove them
 */
function openlab_strip_brackets_from_pw_reset_email( $message ) {
	$message = preg_replace( '/<(http\S*?)>/', '$1', $message );
	return $message;
}

add_filter( 'retrieve_password_message', 'openlab_strip_brackets_from_pw_reset_email' );

/**
 * Don't allow non-super-admins to Add New Users on user-new.php
 *
 * This is a hack. user-new.php shows the Add New User section for any user
 * who has the 'create_users' cap. For some reason, Administrators have the
 * 'create_users' cap even on Multisite. Instead of doing a total removal
 * of this cap for Administrators ( which may break something ), I'm just
 * removing it on the user-new.php page.
 *
 */
function openlab_block_add_new_user( $allcaps, $cap, $args ) {
	if ( ! in_array( 'create_users', $cap ) ) {
		return $allcaps;
	}

	if ( ! is_admin() || false === strpos( $_SERVER['SCRIPT_NAME'], 'user-new.php' ) ) {
		return $allcaps;
	}

	if ( is_super_admin() ) {
		return $allcaps;
	}

	unset( $allcaps['create_users'] );

	return $allcaps;
}

add_filter( 'user_has_cap', 'openlab_block_add_new_user', 10, 3 );

/**
 * Remove user from group blog when leaving group
 *
 * NOTE: This function should live in includes/group-blogs.php, but can't
 * because of AJAX load order
 */
function openlab_remove_user_from_groupblog( $group_id, $user_id ) {
	$blog_id = groups_get_groupmeta( $group_id, 'wds_bp_group_site_id' );

	if ( $blog_id ) {
		remove_user_from_blog( $user_id, $blog_id );
	}
}

add_action( 'groups_leave_group', 'openlab_remove_user_from_groupblog', 10, 2 );

/**
 * Don't let Awesome Flickr plugin load colorbox if WP AJAX Edit Comments is active
 *
 * See http://openlab.citytech.cuny.edu/redmine/issues/363
 */
function openlab_fix_colorbox_conflict_1() {
	if ( ! function_exists( 'enqueue_afg_scripts' ) ) {
		return;
	}

	$is_wp_ajax_edit_comments_active = in_array( 'wp-ajax-edit-comments/wp-ajax-edit-comments.php', (array) get_option( 'active_plugins', array() ) );

	remove_action( 'wp_print_scripts', 'enqueue_afg_scripts' );

	if ( ! get_option( 'afg_disable_slideshow' ) ) {
		if ( get_option( 'afg_slideshow_option' ) == 'highslide' ) {
			wp_enqueue_script( 'afg_highslide_js', BASE_URL . '/highslide/highslide-full.min.js' );
		}

		if ( get_option( 'afg_slideshow_option' ) == 'colorbox' && ! $is_wp_ajax_edit_comments_active ) {
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'afg_colorbox_script', BASE_URL . '/colorbox/jquery.colorbox-min.js', array( 'jquery' ) );
			wp_enqueue_script( 'afg_colorbox_js', BASE_URL . '/colorbox/mycolorbox.js', array( 'jquery' ) );
		}
	}
}

add_action( 'wp_print_scripts', 'openlab_fix_colorbox_conflict_1', 1 );

/**
 * Prevent More Privacy Options from displaying its -2 message, and replace with our own
 *
 * See #775
 */
function openlab_swap_private_blog_message() {
	global $current_blog, $ds_more_privacy_options;

	if ( '-2' == $current_blog->public ) {
		remove_action( 'template_redirect', array( &$ds_more_privacy_options, 'ds_members_authenticator' ) );
		add_action( 'template_redirect', 'openlab_private_blog_message', 1 );
	}
}

add_action( 'wp', 'openlab_swap_private_blog_message' );

/**
 * Callback for our own "members only" blog message
 *
 * See #775
 */
function openlab_private_blog_message() {
	global $ds_more_privacy_options;

	$blog_id = get_current_blog_id();
	$group_id = openlab_get_group_id_by_blog_id( $blog_id );
	$group_url = bp_get_group_permalink( groups_get_group( array( 'group_id' => $group_id ) ) );
	$user_id = get_current_user_id();

	if ( is_user_member_of_blog( $user_id, $blog_id ) || is_super_admin() ) {
		return;
	} elseif ( is_user_logged_in() ) {
		openlab_ds_login_header();
		?>
		<form name="loginform" id="loginform" />
		<p>To become a member of this site, please request membership on <a href="<?php echo esc_attr( $group_url ) ?>">the profile page</a>.</p>
		</form>
		</div>
		</body>
		</html>
		<?php
		exit();
	} else {
		if ( is_feed() ) {
			$ds_more_privacy_options->ds_feed_login();
		} else {
			nocache_headers();
			header( 'HTTP/1.1 302 Moved Temporarily' );
			header( 'Location: ' . wp_login_url() );
			header( 'Status: 302 Moved Temporarily' );
			exit();
		}
	}
}

/**
 * A version of the More Privacy Options login header without the redirect
 *
 * @see openlab_private_blog_message()
 */
function openlab_ds_login_header() {
	global $error, $is_iphone, $interim_login, $current_site;
	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
		<head>
			<title><?php _e( 'Private Blog Message' ); ?></title>
			<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php bloginfo( 'charset' ); ?>" />
			<?php
			wp_admin_css( 'login', true );
			wp_admin_css( 'colors-fresh', true );

			if ( $is_iphone ) {
				?>
				<meta name="viewport" content="width=320; initial-scale=0.9; maximum-scale=1.0; user-scalable=0;" />
				<style type="text/css" media="screen">
					form { margin-left: 0px; }
					#login { margin-top: 20px; }
				</style>
			<?php } elseif ( isset( $interim_login ) && $interim_login ) {
				?>
				<style type="text/css" media="all">
					.login #login { margin: 20px auto; }
				</style>
				<?php
}

			do_action( 'login_head' );
			?>
		</head>
		<body class="login">
			<div id="login">
				<h1><a href="<?php echo apply_filters( 'login_headerurl', 'http://' . $current_site->domain . $current_site->path ); ?>" title="<?php echo apply_filters( 'login_headertitle', $current_site->site_name ); ?>"><span class="hide"><?php bloginfo( 'name' ); ?></span></a></h1>
				<?php
}

/**
 * Group member portfolio list widget
 *
 * This function is here (rather than includes/portfolios.php) because it needs
 * to run at 'widgets_init'.
 */
class OpenLab_Course_Portfolios_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'openlab_course_portfolios_widget', 'Portfolio List', array(
			'description' => 'Display a list of the Portfolios belonging to the members of this course.',
				)
		);
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];

		echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];

		$name_key = 'display_name' === $instance['sort_by'] ? 'user_display_name' : 'portfolio_title';
		$group_id = openlab_get_group_id_by_blog_id( get_current_blog_id() );
		$portfolios = openlab_get_group_member_portfolios( $group_id, $instance['sort_by'] );

		if ( '1' === $instance['display_as_dropdown'] ) {
			echo '<form action="" method="get">';
			echo '<select class="portfolio-goto" name="portfolio-goto">';
			echo '<option value="" selected="selected">Choose a Portfolio</option>';
			foreach ( $portfolios as $portfolio ) {
				echo '<option value="' . esc_attr( $portfolio['portfolio_url'] ) . '">' . esc_attr( $portfolio[ $name_key ] ) . '</option>';
			}
			echo '</select>';
			echo '<input class="openlab-portfolio-list-widget-submit" style="margin-top: .5em" type="submit" value="Go" />';
			wp_nonce_field( 'portfolio_goto', '_pnonce' );
			echo '</form>';
		} else {
			echo '<ul class="openlab-portfolio-links">';
			foreach ( $portfolios as $portfolio ) {
				echo '<li><a href="' . esc_url( $portfolio['portfolio_url'] ) . '">' . esc_html( $portfolio[ $name_key ] ) . '</a></li>';
			}
			echo '</ul>';
		}

		// Some lousy inline CSS
		?>
		<style type="text/css">
			.openlab-portfolio-list-widget-submit {
				margin-top: .5em;
			}
			body.js .openlab-portfolio-list-widget-submit {
				display: none;
			}
		</style>

		<?php
		echo $args['after_widget'];

		$this->enqueue_scripts();
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['display_as_dropdown'] = ! empty( $new_instance['display_as_dropdown'] ) ? '1' : '';
		$instance['sort_by'] = in_array( $new_instance['sort_by'], array( 'random', 'display_name', 'title' ) ) ? $new_instance['sort_by'] : 'display_name';
		$instance['num_links'] = isset( $new_instance['num_links'] ) ? (int) $new_instance['num_links'] : '';
		return $instance;
	}

	public function form( $instance ) {
		$settings = wp_parse_args($instance, array(
			'title' => 'Portfolia členů',
			'display_as_dropdown' => '0',
			'sort_by' => 'title',
			'num_links' => false,
		));
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ) ?>">Title:</label><br />
			<input name="<?php echo $this->get_field_name( 'title' ) ?>" id="<?php echo $this->get_field_name( 'title' ) ?>" value="<?php echo esc_attr( $settings['title'] ) ?>" />
					</p>

					<p>
			<input name="<?php echo $this->get_field_name( 'display_as_dropdown' ) ?>" id="<?php echo $this->get_field_name( 'display_as_dropdown' ) ?>" value="1" <?php checked( $settings['display_as_dropdown'], '1' ) ?> type="checkbox" />
			<label for="<?php echo $this->get_field_id( 'display_as_dropdown' ) ?>">Zobrazit jako rozbalovací seznam</label>
					</p>

					<p>
			<label for="<?php echo $this->get_field_id( 'sort_by' ) ?>">Třídit podle:</label><br />
			<select name="<?php echo $this->get_field_name( 'sort_by' ) ?>" id="<?php echo $this->get_field_name( 'sort_by' ) ?>">
				<option value="title" <?php selected( $settings['sort_by'], 'title' ) ?>>Název portfolia</option>
				<option value="display_name" <?php selected( $settings['sort_by'], 'display_name' ) ?>>Jméno člena</option>
				<option value="random" <?php selected( $settings['sort_by'], 'random' ) ?>>Náhodně</option>
			</select>
					</p>

		<?php
	}

	protected function enqueue_scripts() {
		wp_enqueue_script( 'jquery' );

		// poor man's dependency - jquery will be loaded by now
		add_action( 'wp_footer', array( $this, 'script' ), 1000 );
	}

	public function script() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$('.portfolio-goto').on('change', function () {
					var maybe_url = this.value;
					if (maybe_url) {
						document.location.href = maybe_url;
					}
				});
			}, (jQuery));
		</script>
		<?php
	}
}

/**
 * Register the Course Portfolios widget
 */
function openlab_register_portfolios_widget() {
	register_widget( 'OpenLab_Course_Portfolios_Widget' );
}
add_action( 'widgets_init', 'openlab_register_portfolios_widget' );

/**
 * Utility function for getting the xprofile exclude groups for a given account type
 */
function openlab_get_exclude_groups_for_account_type( $type ) {
	global $wpdb, $bp;
	$groups = $wpdb->get_results( "SELECT id, name FROM {$bp->profile->table_name_groups}" );

	// Reindex
	$gs = array();
	foreach ( $groups as $group ) {
		$gs[ $group->name ] = $group->id;
	}

	$exclude_groups = array();
	foreach ( $gs as $gname => $gid ) {
		// special case for alumni
		if ( 'Alumni' === $type && 'Student' === $gname ) {
			continue;
		}

		// otherwise, non-matches are excluded
		if ( $gname !== $type ) {
			$exclude_groups[] = $gid;
		}
	}

	return implode( ',', $exclude_groups );
}

/**
 * Flush rewrite rules when a blog is created.
 *
 * There's a bug in WP that causes rewrite rules to be flushed before
 * taxonomies have been registered. As a result, tag and category archive links
 * do not work. Here we work around the issue by hooking into blog creation,
 * registering the taxonomies, and forcing another rewrite flush.
 *
 * See https://core.trac.wordpress.org/ticket/20171, http://openlab.citytech.cuny.edu/redmine/issues/1054
 */
function openlab_flush_rewrite_rules( $blog_id ) {
	switch_to_blog( $blog_id );
	create_initial_taxonomies();
	flush_rewrite_rules();
	update_option( 'openlab_rewrite_rules_flushed', 1 );
	restore_current_blog();
}
add_action( 'wpmu_new_blog', 'openlab_flush_rewrite_rules', 9999 );

/**
 * Lazyloading rewrite rules repairer.
 *
 * Repairs the damage done by WP's buggy rewrite rules generator for new blogs.
 *
 * See openlab_flush_rewrite_rules().
 */
function openlab_lazy_flush_rewrite_rules() {
	// We load late, so taxonomies should be created by now
	if ( ! get_option( 'openlab_rewrite_rules_flushed' ) ) {
		flush_rewrite_rules();
		update_option( 'openlab_rewrite_rules_flushed', 1 );
	}
}
add_action( 'init', 'openlab_lazy_flush_rewrite_rules', 9999 );

/**
 * Whitelist the 'webcal' protocol.
 *
 * Prevents the protocol from being stripped for non-privileged users.
 */
function openlab_add_webcal_to_allowed_protocols( $protocols ) {
	$protocols[] = 'webcal';
	return $protocols;
}
add_filter( 'kses_allowed_protocols', 'openlab_add_webcal_to_allowed_protocols' );

/**
 * Don't limit upload space on blog 1.
 */
function openlab_allow_unlimited_space_on_blog_1( $check ) {
	if ( 1 === get_current_blog_id() ) {
		return 0;
	}

	return $check;
}
add_filter( 'pre_get_space_used', 'openlab_allow_unlimited_space_on_blog_1' );

/**
 * Disable BP 2.5 rich-text emails.
 */
add_filter( 'bp_email_use_wp_mail', '__return_true' );

/**
 * Set "From" name in outgoing email to the site name.
 *
 * BP did this until 2.5, when the filters were moved to the new email system. Since we're using the legacy emails
 * for now, we must reinstate.
 *
 * @return string The blog name for the root blog.
 */
function openlab_email_from_name_filter() {
	/**
	 * Filters the "From" name in outgoing email to the site name.
	 *
	 * @since BuddyPress (1.2.0)
	 *
	 * @param string $value Value to set the "From" name to.
	 */
	return apply_filters( 'bp_core_email_from_name_filter', bp_get_option( 'blogname', 'WordPress' ) );
}
add_filter( 'wp_mail_from_name', 'openlab_email_from_name_filter' );

function openlab_email_appearance_settings( $settings ) {
	$settings['email_bg'] = '#fff';
	$settings['header_bg'] = '#fff';
	$settings['footer_bg'] = '#fff';
	$settings['highlight_color'] = '#ec6348';
	return $settings;
}
add_filter( 'bp_after_email_appearance_settings_parse_args', 'openlab_email_appearance_settings' );

/**
 * Group slug blacklist.
 */
function openlab_forbidden_group_names( $names ) {
	$names[] = 'thebuzz';
	$names[] = 'the-buzz';
	$names[] = 'the-hub';
	return $names;
}
add_filter( 'groups_forbidden_names', 'openlab_forbidden_group_names' );

function openlab_disallow_tinymce_comment_stylesheet( $settings ) {
	if ( ! isset( $settings['tinymce'] ) || ! isset( $settings['tinymce']['content_css'] ) ) {
		return $settings;
	}

	if ( false !== strpos( $settings['tinymce']['content_css'], 'tinymce-comment-field-editor' ) ) {
		unset( $settings['tinymce']['content_css'] );
	}

	return $settings;
}
add_filter( 'wp_editor_settings', 'openlab_disallow_tinymce_comment_stylesheet' );

/**
 * Blogs must be public in order for BP to record their activity.
 */
add_filter( 'bp_is_blog_public', '__return_true' );

/**
 * Blacklist some Jetpack modules.
 */
function openlab_blacklist_jetpack_modules( $modules ) {
	$blacklist = array( 'masterbar' );

	foreach ( $blacklist as $module ) {
		unset( $modules[ $module ] );
	}

	return $modules;
}
add_filter( 'jetpack_get_available_modules', 'openlab_blacklist_jetpack_modules' );
