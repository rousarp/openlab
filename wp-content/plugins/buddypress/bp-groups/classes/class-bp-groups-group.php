<?php
/**
 * BuddyPress Groups Classes.
 *
 * @package BuddyPress
 * @subpackage GroupsClasses
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Group object.
 *
 * @since 1.6.0
 */
class BP_Groups_Group {

	/**
	 * ID of the group.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	public $id;

	/**
	 * User ID of the group's creator.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	public $creator_id;

	/**
	 * Name of the group.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public $name;

	/**
	 * Group slug.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public $slug;

	/**
	 * Group description.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public $description;

	/**
	 * Group status.
	 *
	 * Core statuses are 'public', 'private', and 'hidden'.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public $status;

	/**
	 * Parent ID.
	 *
	 * ID of parent group, if applicable.
	 *
	 * @since 2.7.0
	 * @var int
	 */
	public $parent_id;

	/**
	 * Should (legacy) bbPress forums be enabled for this group?
	 *
	 * @since 1.6.0
	 * @var int
	 */
	public $enable_forum;

	/**
	 * Date the group was created.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public $date_created;

	/**
	 * Data about the group's admins.
	 *
	 * @since 1.6.0
	 * @var array
	 */
	protected $admins;

	/**
	 * Data about the group's moderators.
	 *
	 * @since 1.6.0
	 * @var array
	 */
	protected $mods;

	/**
	 * Total count of group members.
	 *
	 * @since 1.6.0
	 * @var int
	 */
	protected $total_member_count;

	/**
	 * Is the current user a member of this group?
	 *
	 * @since 1.2.0
	 * @var bool
	 */
	protected $is_member;

	/**
	 * Does the current user have an outstanding invitation to this group?
	 *
	 * @since 1.9.0
	 * @var bool
	 */
	protected $is_invited;

	/**
	 * Does the current user have a pending membership request to this group?
	 *
	 * @since 1.9.0
	 * @var bool
	 */
	protected $is_pending;

	/**
	 * Timestamp of the last activity that happened in this group.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	protected $last_activity;

	/**
	 * If this is a private or hidden group, does the current user have access?
	 *
	 * @since 1.6.0
	 * @var bool
	 */
	protected $user_has_access;

	/**
	 * Raw arguments passed to the constructor.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	public $args;

	/**
	 * Constructor method.
	 *
	 * @since 1.6.0
	 *
	 * @param int|null $id   Optional. If the ID of an existing group is provided,
	 *                       the object will be pre-populated with info about that group.
	 * @param array    $args {
	 *     Array of optional arguments.
	 *     @type bool $populate_extras Deprecated.
	 * }
	 */
	public function __construct( $id = null, $args = array() ) {
		if ( !empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Set up data about the current group.
	 *
	 * @since 1.6.0
	 */
	public function populate() {
		global $wpdb;

		// Get BuddyPress.
		$bp    = buddypress();

		// Check cache for group data.
		$group = wp_cache_get( $this->id, 'bp_groups' );

		// Cache missed, so query the DB.
		if ( false === $group ) {
			$group = $wpdb->get_row( $wpdb->prepare( "SELECT g.* FROM {$bp->groups->table_name} g WHERE g.id = %d", $this->id ) );

			wp_cache_set( $this->id, $group, 'bp_groups' );
		}

		// No group found so set the ID and bail.
		if ( empty( $group ) || is_wp_error( $group ) ) {
			$this->id = 0;
			return;
		}

		// Group found so setup the object variables.
		$this->id           = (int) $group->id;
		$this->creator_id   = (int) $group->creator_id;
		$this->name         = stripslashes( $group->name );
		$this->slug         = $group->slug;
		$this->description  = stripslashes( $group->description );
		$this->status       = $group->status;
		$this->parent_id    = (int) $group->parent_id;
		$this->enable_forum = (int) $group->enable_forum;
		$this->date_created = $group->date_created;
	}

	/**
	 * Save the current group to the database.
	 *
	 * @since 1.6.0
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save() {
		global $wpdb;

		$bp = buddypress();

		$this->creator_id   = apply_filters( 'groups_group_creator_id_before_save',   $this->creator_id,   $this->id );
		$this->name         = apply_filters( 'groups_group_name_before_save',         $this->name,         $this->id );
		$this->slug         = apply_filters( 'groups_group_slug_before_save',         $this->slug,         $this->id );
		$this->description  = apply_filters( 'groups_group_description_before_save',  $this->description,  $this->id );
		$this->status       = apply_filters( 'groups_group_status_before_save',       $this->status,       $this->id );
		$this->parent_id    = apply_filters( 'groups_group_parent_id_before_save',    $this->parent_id,    $this->id );
		$this->enable_forum = apply_filters( 'groups_group_enable_forum_before_save', $this->enable_forum, $this->id );
		$this->date_created = apply_filters( 'groups_group_date_created_before_save', $this->date_created, $this->id );

		/**
		 * Fires before the current group item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Groups_Group $this Current instance of the group item being saved. Passed by reference.
		 */
		do_action_ref_array( 'groups_group_before_save', array( &$this ) );

		// Groups need at least a name.
		if ( empty( $this->name ) ) {
			return false;
		}

		// Set slug with group title if not passed.
		if ( empty( $this->slug ) ) {
			$this->slug = sanitize_title( $this->name );
		}

		// Sanity check.
		if ( empty( $this->slug ) ) {
			return false;
		}

		// Check for slug conflicts if creating new group.
		if ( empty( $this->id ) ) {
			$this->slug = groups_check_slug( $this->slug );
		}

		if ( !empty( $this->id ) ) {
			$sql = $wpdb->prepare(
				"UPDATE {$bp->groups->table_name} SET
					creator_id = %d,
					name = %s,
					slug = %s,
					description = %s,
					status = %s,
					parent_id = %d,
					enable_forum = %d,
					date_created = %s
				WHERE
					id = %d
				",
					$this->creator_id,
					$this->name,
					$this->slug,
					$this->description,
					$this->status,
					$this->parent_id,
					$this->enable_forum,
					$this->date_created,
					$this->id
			);
		} else {
			$sql = $wpdb->prepare(
				"INSERT INTO {$bp->groups->table_name} (
					creator_id,
					name,
					slug,
					description,
					status,
					parent_id,
					enable_forum,
					date_created
				) VALUES (
					%d, %s, %s, %s, %s, %d, %d, %s
				)",
					$this->creator_id,
					$this->name,
					$this->slug,
					$this->description,
					$this->status,
					$this->parent_id,
					$this->enable_forum,
					$this->date_created
			);
		}

		if ( false === $wpdb->query($sql) )
			return false;

		if ( empty( $this->id ) )
			$this->id = $wpdb->insert_id;

		/**
		 * Fires after the current group item has been saved.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Groups_Group $this Current instance of the group item that was saved. Passed by reference.
		 */
		do_action_ref_array( 'groups_group_after_save', array( &$this ) );

		wp_cache_delete( $this->id, 'bp_groups' );

		return true;
	}

	/**
	 * Delete the current group.
	 *
	 * @since 1.6.0
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete() {
		global $wpdb;

		// Delete groupmeta for the group.
		groups_delete_groupmeta( $this->id );

		// Fetch the user IDs of all the members of the group.
		$user_ids    = BP_Groups_Member::get_group_member_ids( $this->id );
		$user_id_str = esc_sql( implode( ',', wp_parse_id_list( $user_ids ) ) );

		// Modify group count usermeta for members.
		$wpdb->query( "UPDATE {$wpdb->usermeta} SET meta_value = meta_value - 1 WHERE meta_key = 'total_group_count' AND user_id IN ( {$user_id_str} )" );

		// Now delete all group member entries.
		BP_Groups_Member::delete_all( $this->id );

		/**
		 * Fires before the deletion of a group.
		 *
		 * @since 1.2.0
		 *
		 * @param BP_Groups_Group $this     Current instance of the group item being deleted. Passed by reference.
		 * @param array           $user_ids Array of user IDs that were members of the group.
		 */
		do_action_ref_array( 'bp_groups_delete_group', array( &$this, $user_ids ) );

		wp_cache_delete( $this->id, 'bp_groups' );

		$bp = buddypress();

		// Finally remove the group entry from the DB.
		if ( !$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name} WHERE id = %d", $this->id ) ) )
			return false;

		return true;
	}

	/**
	 * Magic getter.
	 *
	 * @since 2.7.0
	 *
	 * @param string $key Property name.
	 * @return mixed
	 */
	public function __get( $key ) {
		switch ( $key ) {
			case 'last_activity' :
			case 'total_member_count' :
			case 'forum_id' :
				$retval = groups_get_groupmeta( $this->id, $key );

				if ( 'last_activity' !== $key ) {
					$retval = (int) $retval;
				}

				return $retval;

			case 'admins' :
				return $this->get_admins();

			case 'mods' :
				return $this->get_mods();

			case 'is_member' :
				return $this->get_is_member();

			case 'is_invited' :
				return groups_check_user_has_invite( bp_loggedin_user_id(), $this->id );

			case 'is_pending' :
				return groups_check_for_membership_request( bp_loggedin_user_id(), $this->id );

			case 'user_has_access' :
				return $this->get_user_has_access();

			default :
				return isset( $this->{$key} ) ? $this->{$key} : null;
		}
	}

	/**
	 * Magic issetter.
	 *
	 * Used to maintain backward compatibility for properties that are now
	 * accessible only via magic method.
	 *
	 * @since 2.7.0
	 *
	 * @param string $key Property name.
	 * @return bool
	 */
	public function __isset( $key ) {
		switch ( $key ) {
			case 'admins' :
			case 'is_invited' :
			case 'is_member' :
			case 'is_pending' :
			case 'last_activity' :
			case 'mods' :
			case 'total_member_count' :
			case 'user_has_access' :
			case 'forum_id' :
				return true;

			default :
				return isset( $this->{$key} );
		}
	}

	/**
	 * Magic setter.
	 *
	 * Used to maintain backward compatibility for properties that are now
	 * accessible only via magic method.
	 *
	 * @since 2.7.0
	 *
	 * @param string $key Property name.
	 */
	public function __set( $key, $value ) {
		switch ( $key ) {
			case 'user_has_access' :
				return $this->user_has_access = (bool) $value;

			default :
				$this->{$key} = $value;
		}
	}

	/**
	 * Get a list of the group's admins.
	 *
	 * Used to provide cache-friendly access to the 'admins' property of
	 * the group object.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	protected function get_admins() {
		if ( isset( $this->admins ) ) {
			return $this->admins;
		}

		$this->set_up_admins_and_mods();
		return $this->admins;
	}

	/**
	 * Get a list of the group's mods.
	 *
	 * Used to provide cache-friendly access to the 'mods' property of
	 * the group object.
	 *
	 * @since 2.7.0
	 *
	 * @return array
	 */
	protected function get_mods() {
		if ( isset( $this->mods ) ) {
			return $this->mods;
		}

		$this->set_up_admins_and_mods();
		return $this->mods;
	}

	/**
	 * Set up admins and mods for the current group object.
	 *
	 * Called only when the 'admins' or 'mods' property is accessed.
	 *
	 * @since 2.7.0
	 */
	protected function set_up_admins_and_mods() {
		$admin_ids = BP_Groups_Member::get_group_administrator_ids( $this->id );
		$admin_ids_plucked = wp_list_pluck( $admin_ids, 'user_id' );

		$mod_ids = BP_Groups_Member::get_group_moderator_ids( $this->id );
		$mod_ids_plucked = wp_list_pluck( $mod_ids, 'user_id' );

		$admin_mod_users = get_users( array(
			'include' => array_merge( $admin_ids_plucked, $mod_ids_plucked ),
			'blog_id' => null,
		) );

		$admin_objects = $mod_objects = array();
		foreach ( $admin_mod_users as $admin_mod_user ) {
			$obj = new stdClass();
			$obj->user_id = $admin_mod_user->ID;
			$obj->user_login = $admin_mod_user->user_login;
			$obj->user_email = $admin_mod_user->user_email;
			$obj->user_nicename = $admin_mod_user->user_nicename;

			if ( in_array( $admin_mod_user->ID, $admin_ids_plucked, true ) ) {
				$obj->is_admin = 1;
				$obj->is_mod = 0;
				$admin_objects[] = $obj;
			} else {
				$obj->is_admin = 0;
				$obj->is_mod = 1;
				$mod_objects[] = $obj;
			}
		}

		$this->admins = $admin_objects;
		$this->mods   = $mod_objects;
	}

	/**
	 * Checks whether the logged-in user is a member of the group.
	 *
	 * @since 2.7.0
	 *
	 * @return bool
	 */
	protected function get_is_member() {
		if ( isset( $this->is_member ) ) {
			return $this->is_member;
		}

		$this->is_member = groups_is_user_member( bp_loggedin_user_id(), $this->id );
		return $this->is_member;
	}

	/**
	 * Checks whether the logged-in user has access to the group.
	 *
	 * @since 2.7.0
	 *
	 * @return bool
	 */
	protected function get_user_has_access() {
		if ( isset( $this->user_has_access ) ) {
			return $this->user_has_access;
		}

		if ( ( 'private' === $this->status ) || ( 'hidden' === $this->status ) ) {

			// Assume user does not have access to hidden/private groups.
			$this->user_has_access = false;

			// Group members or community moderators have access.
			if ( ( is_user_logged_in() && $this->get_is_member() ) || bp_current_user_can( 'bp_moderate' ) ) {
				$this->user_has_access = true;
			}
		} else {
			$this->user_has_access = true;
		}

		return $this->user_has_access;
	}

	/** Static Methods ****************************************************/

	/**
	 * Get whether a group exists for a given slug.
	 *
	 * @since 1.6.0
	 *
	 * @param string      $slug       Slug to check.
	 * @param string|bool $table_name Optional. Name of the table to check
	 *                                against. Default: $bp->groups->table_name.
	 * @return int|null Group ID if found; null if not.
	 */
	public static function group_exists( $slug, $table_name = false ) {
		global $wpdb;

		if ( empty( $table_name ) )
			$table_name = buddypress()->groups->table_name;

		if ( empty( $slug ) )
			return false;

		$query = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE slug = %s", strtolower( $slug ) ) );

		return is_numeric( $query ) ? (int) $query : $query;
	}

	/**
	 * Get the ID of a group by the group's slug.
	 *
	 * Alias of {@link BP_Groups_Group::group_exists()}.
	 *
	 * @since 1.6.0
	 *
	 * @param string $slug See {@link BP_Groups_Group::group_exists()}.
	 * @return string|null See {@link BP_Groups_Group::group_exists()}.
	 */
	public static function get_id_from_slug( $slug ) {
		return BP_Groups_Group::group_exists( $slug );
	}

	/**
	 * Get IDs of users with outstanding invites to a given group from a specified user.
	 *
	 * @since 1.6.0
	 *
	 * @param int $user_id ID of the inviting user.
	 * @param int $group_id ID of the group.
	 * @return array IDs of users who have been invited to the group by the
	 *               user but have not yet accepted.
	 */
	public static function get_invites( $user_id, $group_id ) {
		global $wpdb;

		$bp = buddypress();

		return $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->groups->table_name_members} WHERE group_id = %d and is_confirmed = 0 AND inviter_id = %d", $group_id, $user_id ) );
	}

	/**
	 * Get a list of a user's groups, filtered by a search string.
	 *
	 * @since 1.6.0
	 *
	 * @param string   $filter  Search term. Matches against 'name' and
	 *                          'description' fields.
	 * @param int      $user_id ID of the user whose groups are being searched.
	 *                          Default: the displayed user.
	 * @param mixed    $order   Not used.
	 * @param int|null $limit   Optional. The max number of results to return.
	 *                          Default: null (no limit).
	 * @param int|null $page    Optional. The page offset of results to return.
	 *                          Default: null (no limit).
	 * @return false|array {
	 *     @type array $groups Array of matched and paginated group objects.
	 *     @type int   $total  Total count of groups matching the query.
	 * }
	 */
	public static function filter_user_groups( $filter, $user_id = 0, $order = false, $limit = null, $page = null ) {
		global $wpdb;

		if ( empty( $user_id ) )
			$user_id = bp_displayed_user_id();

		$search_terms_like = '%' . bp_esc_like( $filter ) . '%';

		$pag_sql = $order_sql = $hidden_sql = '';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		// Get all the group ids for the current user's groups.
		$gids = BP_Groups_Member::get_group_ids( $user_id );

		if ( empty( $gids['groups'] ) )
			return false;

		$bp = buddypress();

		$gids = esc_sql( implode( ',', wp_parse_id_list( $gids['groups'] ) ) );

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT id as group_id FROM {$bp->groups->table_name} WHERE ( name LIKE %s OR description LIKE %s ) AND id IN ({$gids}) {$pag_sql}", $search_terms_like, $search_terms_like ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name} WHERE ( name LIKE %s OR description LIKE %s ) AND id IN ({$gids})", $search_terms_like, $search_terms_like ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	/**
	 * Get a list of groups, filtered by a search string.
	 *
	 * @since 1.6.0
	 *
	 * @param string      $filter  Search term. Matches against 'name' and
	 *                             'description' fields.
	 * @param int|null    $limit   Optional. The max number of results to return.
	 *                             Default: null (no limit).
	 * @param int|null    $page    Optional. The page offset of results to return.
	 *                             Default: null (no limit).
	 * @param string|bool $sort_by Column to sort by. Default: false (default
	 *        sort).
	 * @param string|bool $order   ASC or DESC. Default: false (default sort).
	 * @return array {
	 *     @type array $groups Array of matched and paginated group objects.
	 *     @type int   $total  Total count of groups matching the query.
	 * }
	 */
	public static function search_groups( $filter, $limit = null, $page = null, $sort_by = false, $order = false ) {
		global $wpdb;

		$search_terms_like = '%' . bp_esc_like( $filter ) . '%';

		$pag_sql = $order_sql = $hidden_sql = '';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( !empty( $sort_by ) && !empty( $order ) ) {
			$sort_by   = esc_sql( $sort_by );
			$order     = esc_sql( $order );
			$order_sql = "ORDER BY {$sort_by} {$order}";
		}

		if ( !bp_current_user_can( 'bp_moderate' ) )
			$hidden_sql = "AND status != 'hidden'";

		$bp = buddypress();

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT id as group_id FROM {$bp->groups->table_name} WHERE ( name LIKE %s OR description LIKE %s ) {$hidden_sql} {$order_sql} {$pag_sql}", $search_terms_like, $search_terms_like ) );
		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name} WHERE ( name LIKE %s OR description LIKE %s ) {$hidden_sql}", $search_terms_like, $search_terms_like ) );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	/**
	 * Check for the existence of a slug.
	 *
	 * @since 1.6.0
	 *
	 * @param string $slug Slug to check.
	 * @return string|null The slug, if found. Otherwise null.
	 */
	public static function check_slug( $slug ) {
		global $wpdb;

		$bp = buddypress();

		return $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$bp->groups->table_name} WHERE slug = %s", $slug ) );
	}

	/**
	 * Get the slug for a given group ID.
	 *
	 * @since 1.6.0
	 *
	 * @param int $group_id ID of the group.
	 * @return string|null The slug, if found. Otherwise null.
	 */
	public static function get_slug( $group_id ) {
		global $wpdb;

		$bp = buddypress();

		return $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$bp->groups->table_name} WHERE id = %d", $group_id ) );
	}

	/**
	 * Check whether a given group has any members.
	 *
	 * @since 1.6.0
	 *
	 * @param int $group_id ID of the group.
	 * @return bool True if the group has members, otherwise false.
	 */
	public static function has_members( $group_id ) {
		global $wpdb;

		$bp = buddypress();

		$members = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d", $group_id ) );

		if ( empty( $members ) )
			return false;

		return true;
	}

	/**
	 * Check whether a group has outstanding membership requests.
	 *
	 * @since 1.6.0
	 *
	 * @param int $group_id ID of the group.
	 * @return int|null The number of outstanding requests, or null if
	 *                  none are found.
	 */
	public static function has_membership_requests( $group_id ) {
		global $wpdb;

		$bp = buddypress();

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 0", $group_id ) );
	}

	/**
	 * Get outstanding membership requests for a group.
	 *
	 * @since 1.6.0
	 *
	 * @param int      $group_id ID of the group.
	 * @param int|null $limit    Optional. Max number of results to return.
	 *                           Default: null (no limit).
	 * @param int|null $page     Optional. Page offset of results returned. Default:
	 *                           null (no limit).
	 * @return array {
	 *     @type array $requests The requested page of located requests.
	 *     @type int   $total    Total number of requests outstanding for the
	 *                           group.
	 * }
	 */
	public static function get_membership_requests( $group_id, $limit = null, $page = null ) {
		global $wpdb;

		if ( !empty( $limit ) && !empty( $page ) ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		$bp = buddypress();

		$paged_requests = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 0 AND inviter_id = 0{$pag_sql}", $group_id ) );
		$total_requests = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 0 AND inviter_id = 0", $group_id ) );

		return array( 'requests' => $paged_requests, 'total' => $total_requests );
	}

	/**
	 * Query for groups.
	 *
	 * @see WP_Meta_Query::queries for a description of the 'meta_query'
	 *      parameter format.
	 *
	 * @since 1.6.0
	 * @since 2.6.0 Added `$group_type`, `$group_type__in`, and `$group_type__not_in` parameters.
	 * @since 2.7.0 Added `$update_admin_cache` and `$parent_id` parameters.
	 *
	 * @param array $args {
	 *     Array of parameters. All items are optional.
	 *     @type string       $type               Optional. Shorthand for certain orderby/order combinations.
	 *                                            'newest', 'active', 'popular', 'alphabetical', 'random'.
	 *                                            When present, will override orderby and order params.
	 *                                            Default: null.
	 *     @type string       $orderby            Optional. Property to sort by. 'date_created', 'last_activity',
	 *                                            'total_member_count', 'name', 'random'. Default: 'date_created'.
	 *     @type string       $order              Optional. Sort order. 'ASC' or 'DESC'. Default: 'DESC'.
	 *     @type int          $per_page           Optional. Number of items to return per page of results.
	 *                                            Default: null (no limit).
	 *     @type int          $page               Optional. Page offset of results to return.
	 *                                            Default: null (no limit).
	 *     @type int          $user_id            Optional. If provided, results will be limited to groups
	 *                                            of which the specified user is a member. Default: null.
	 *     @type string       $search_terms       Optional. If provided, only groups whose names or descriptions
	 *                                            match the search terms will be returned. Default: false.
	 *     @type array|string $group_type         Array or comma-separated list of group types to limit results to.
	 *     @type array|string $group_type__in     Array or comma-separated list of group types to limit results to.
	 *     @type array|string $group_type__not_in Array or comma-separated list of group types that will be
	 *                                            excluded from results.
	 *     @type array        $meta_query         Optional. An array of meta_query conditions.
	 *                                            See {@link WP_Meta_Query::queries} for description.
	 *     @type array|string $value              Optional. Array or comma-separated list of group IDs. Results
	 *                                            will be limited to groups within the list. Default: false.
	 *     @type array|string $parent_id          Optional. Array or comma-separated list of group IDs. Results
	 *                                            will be limited to children of the specified groups. Default: null.
	 *     @type array|string $exclude            Optional. Array or comma-separated list of group IDs.
	 *                                            Results will exclude the listed groups. Default: false.
	 *     @type bool         $update_meta_cache  Whether to pre-fetch groupmeta for the returned groups.
	 *                                            Default: true.
	 *     @type bool         $update_admin_cache Whether to pre-fetch administrator IDs for the returned
	 *                                            groups. Default: false.
	 *     @type bool         $show_hidden        Whether to include hidden groups in results. Default: false.
	 * }
	 * @return array {
	 *     @type array $groups Array of group objects returned by the
	 *                         paginated query.
	 *     @type int   $total  Total count of all groups matching non-
	 *                         paginated query params.
	 * }
	 */
	public static function get( $args = array() ) {
		global $wpdb;

		// Backward compatibility with old method of passing arguments.
		if ( ! is_array( $args ) || func_num_args() > 1 ) {
			_deprecated_argument( __METHOD__, '1.7', sprintf( __( 'Arguments passed to %1$s should be in an associative array. See the inline documentation at %2$s for more details.', 'buddypress' ), __METHOD__, __FILE__ ) );

			$old_args_keys = array(
				0 => 'type',
				1 => 'per_page',
				2 => 'page',
				3 => 'user_id',
				4 => 'search_terms',
				5 => 'include',
				6 => 'populate_extras',
				7 => 'exclude',
				8 => 'show_hidden',
			);

			$func_args = func_get_args();
			$args      = bp_core_parse_args_array( $old_args_keys, $func_args );
		}

		$defaults = array(
			'type'               => null,
			'orderby'            => 'date_created',
			'order'              => 'DESC',
			'per_page'           => null,
			'page'               => null,
			'user_id'            => 0,
			'search_terms'       => false,
			'group_type'         => '',
			'group_type__in'     => '',
			'group_type__not_in' => '',
			'meta_query'         => false,
			'include'            => false,
			'parent_id'          => null,
			'update_meta_cache'  => true,
			'update_admin_cache' => false,
			'exclude'            => false,
			'show_hidden'        => false,
		);

		$r = wp_parse_args( $args, $defaults );

		$bp = buddypress();

		$sql = array(
			'select'     => "SELECT DISTINCT g.id",
			'from'       => "{$bp->groups->table_name} g",
			'where'      => '',
			'orderby'    => '',
			'pagination' => '',
		);

		if ( ! empty( $r['user_id'] ) ) {
			$sql['from'] .= " JOIN {$bp->groups->table_name_members} m ON ( g.id = m.group_id )";
		}

		$where_conditions = array();

		if ( empty( $r['show_hidden'] ) ) {
			$where_conditions['hidden'] = "g.status != 'hidden'";
		}

		if ( ! empty( $r['search_terms'] ) ) {
			$search_terms_like = '%' . bp_esc_like( $r['search_terms'] ) . '%';
			$where_conditions['search'] = $wpdb->prepare( "( g.name LIKE %s OR g.description LIKE %s )", $search_terms_like, $search_terms_like );
		}

		$meta_query_sql = self::get_meta_query_sql( $r['meta_query'] );

		if ( ! empty( $meta_query_sql['join'] ) ) {
			$sql['from'] .= $meta_query_sql['join'];
		}

		if ( ! empty( $meta_query_sql['where'] ) ) {
			$where_conditions['meta'] = $meta_query_sql['where'];
		}

		// Only use 'group_type__in', if 'group_type' is not set.
		if ( empty( $r['group_type'] ) && ! empty( $r['group_type__in']) ) {
			$r['group_type'] = $r['group_type__in'];
		}

		// Group types to exclude. This has priority over inclusions.
		if ( ! empty( $r['group_type__not_in'] ) ) {
			$group_type_clause = self::get_sql_clause_for_group_types( $r['group_type__not_in'], 'NOT IN' );

		// Group types to include.
		} elseif ( ! empty( $r['group_type'] ) ) {
			$group_type_clause = self::get_sql_clause_for_group_types( $r['group_type'], 'IN' );
		}

		if ( ! empty( $group_type_clause ) ) {
			$where_conditions['group_type'] = $group_type_clause;
		}

		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions['user'] = $wpdb->prepare( "m.user_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0", $r['user_id'] );
		}

		if ( ! empty( $r['include'] ) ) {
			$include        = implode( ',', wp_parse_id_list( $r['include'] ) );
			$where_conditions['include'] = "g.id IN ({$include})";
		}

		if ( ! is_null( $r['parent_id'] ) ) {
			// Note that `wp_parse_id_list()` converts `false` to 0.
			$parent_id        = implode( ',', wp_parse_id_list( $r['parent_id'] ) );
			$where_conditions['parent_id'] = "g.parent_id IN ({$parent_id})";
		}

		if ( ! empty( $r['exclude'] ) ) {
			$exclude        = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions['exclude'] = "g.id NOT IN ({$exclude})";
		}

		/* Order/orderby ********************************************/

		$order   = $r['order'];
		$orderby = $r['orderby'];

		// If a 'type' parameter was passed, parse it and overwrite
		// 'order' and 'orderby' params passed to the function.
		if (  ! empty( $r['type'] ) ) {

			/**
			 * Filters the 'type' parameter used to overwrite 'order' and 'orderby' values.
			 *
			 * @since 2.1.0
			 *
			 * @param array  $value Converted 'type' value for order and orderby.
			 * @param string $value Parsed 'type' value for the get method.
			 */
			$order_orderby = apply_filters( 'bp_groups_get_orderby', self::convert_type_to_order_orderby( $r['type'] ), $r['type'] );

			// If an invalid type is passed, $order_orderby will be
			// an array with empty values. In this case, we stick
			// with the default values of $order and $orderby.
			if ( ! empty( $order_orderby['order'] ) ) {
				$order = $order_orderby['order'];
			}

			if ( ! empty( $order_orderby['orderby'] ) ) {
				$orderby = $order_orderby['orderby'];
			}
		}

		// 'total_member_count' and 'last_activity' sorts require additional table joins.
		if ( 'total_member_count' === $orderby ) {
			$sql['from'] .= " JOIN {$bp->groups->table_name_groupmeta} gm_total_member_count ON ( g.id = gm_total_member_count.group_id )";
			$where_conditions['total_member_count'] = "gm_total_member_count.meta_key = 'total_member_count'";
		} elseif ( 'last_activity' === $orderby ) {

			$sql['from'] .= " JOIN {$bp->groups->table_name_groupmeta} gm_last_activity on ( g.id = gm_last_activity.group_id )";
			$where_conditions['last_activity'] = "gm_last_activity.meta_key = 'last_activity'";
		}

		// Sanitize 'order'.
		$order = bp_esc_sql_order( $order );

		/**
		 * Filters the converted 'orderby' term.
		 *
		 * @since 2.1.0
		 *
		 * @param string $value   Converted 'orderby' term.
		 * @param string $orderby Original orderby value.
		 * @param string $value   Parsed 'type' value for the get method.
		 */
		$orderby = apply_filters( 'bp_groups_get_orderby_converted_by_term', self::convert_orderby_to_order_by_term( $orderby ), $orderby, $r['type'] );

		// Random order is a special case.
		if ( 'rand()' === $orderby ) {
			$sql['orderby'] = "ORDER BY rand()";
		} else {
			$sql['orderby'] = "ORDER BY {$orderby} {$order}";
		}

		if ( ! empty( $r['per_page'] ) && ! empty( $r['page'] ) && $r['per_page'] != -1 ) {
			$sql['pagination'] = $wpdb->prepare( "LIMIT %d, %d", intval( ( $r['page'] - 1 ) * $r['per_page']), intval( $r['per_page'] ) );
		}

		$where = '';
		if ( ! empty( $where_conditions ) ) {
			$sql['where'] = implode( ' AND ', $where_conditions );
			$where = "WHERE {$sql['where']}";
		}

		$paged_groups_sql = "{$sql['select']} FROM {$sql['from']} {$where} {$sql['orderby']} {$sql['pagination']}";

		/**
		 * Filters the pagination SQL statement.
		 *
		 * @since 1.5.0
		 *
		 * @param string $value Concatenated SQL statement.
		 * @param array  $sql   Array of SQL parts before concatenation.
		 * @param array  $r     Array of parsed arguments for the get method.
		 */
		$paged_groups_sql = apply_filters( 'bp_groups_get_paged_groups_sql', $paged_groups_sql, $sql, $r );

		$cached = bp_core_get_incremented_cache( $paged_groups_sql, 'bp_groups' );
		if ( false === $cached ) {
			$paged_group_ids = $wpdb->get_col( $paged_groups_sql );
			bp_core_set_incremented_cache( $paged_groups_sql, 'bp_groups', $paged_group_ids );
		} else {
			$paged_group_ids = $cached;
		}

		$uncached_group_ids = bp_get_non_cached_ids( $paged_group_ids, 'bp_groups' );
		if ( $uncached_group_ids ) {
			$group_ids_sql = implode( ',', array_map( 'intval', $uncached_group_ids ) );
			$group_data_objects = $wpdb->get_results( "SELECT g.* FROM {$bp->groups->table_name} g WHERE g.id IN ({$group_ids_sql})" );
			foreach ( $group_data_objects as $group_data_object ) {
				wp_cache_set( $group_data_object->id, $group_data_object, 'bp_groups' );
			}
		}

		$paged_groups = array();
		foreach ( $paged_group_ids as $paged_group_id ) {
			$paged_groups[] = new BP_Groups_Group( $paged_group_id );
		}

		$total_groups_sql = "SELECT COUNT(DISTINCT g.id) FROM {$sql['from']} $where";

		/**
		 * Filters the SQL used to retrieve total group results.
		 *
		 * @since 1.5.0
		 *
		 * @param string $t_sql     Concatenated SQL statement used for retrieving total group results.
		 * @param array  $total_sql Array of SQL parts for the query.
		 * @param array  $r         Array of parsed arguments for the get method.
		 */
		$total_groups_sql = apply_filters( 'bp_groups_get_total_groups_sql', $total_groups_sql, $sql, $r );

		$cached = bp_core_get_incremented_cache( $total_groups_sql, 'bp_groups' );
		if ( false === $cached ) {
			$total_groups = (int) $wpdb->get_var( $total_groups_sql );
			bp_core_set_incremented_cache( $total_groups_sql, 'bp_groups', $total_groups );
		} else {
			$total_groups = (int) $cached;
		}

		$group_ids = array();
		foreach ( (array) $paged_groups as $group ) {
			$group_ids[] = $group->id;
		}

		// Grab all groupmeta.
		if ( ! empty( $r['update_meta_cache'] ) ) {
			bp_groups_update_meta_cache( $group_ids );
		}

		// Prefetch all administrator IDs, if requested.
		if ( $r['update_admin_cache'] ) {
			BP_Groups_Member::prime_group_admins_mods_cache( $group_ids );
		}

		// Set up integer properties needing casting.
		$int_props = array(
			'id', 'creator_id', 'enable_forum'
		);

		// Integer casting.
		foreach ( $paged_groups as $key => $g ) {
			foreach ( $int_props as $int_prop ) {
				$paged_groups[ $key ]->{$int_prop} = (int) $paged_groups[ $key ]->{$int_prop};
			}
		}

		unset( $sql, $total_sql );

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	/**
	 * Get the SQL for the 'meta_query' param in BP_Activity_Activity::get()
	 *
	 * We use WP_Meta_Query to do the heavy lifting of parsing the
	 * meta_query array and creating the necessary SQL clauses.
	 *
	 * @since 1.8.0
	 *
	 * @param array $meta_query An array of meta_query filters. See the
	 *                          documentation for {@link WP_Meta_Query} for details.
	 * @return array $sql_array 'join' and 'where' clauses.
	 */
	protected static function get_meta_query_sql( $meta_query = array() ) {
		global $wpdb;

		$sql_array = array(
			'join'  => '',
			'where' => '',
		);

		if ( ! empty( $meta_query ) ) {
			$groups_meta_query = new WP_Meta_Query( $meta_query );

			// WP_Meta_Query expects the table name at
			// $wpdb->group.
			$wpdb->groupmeta = buddypress()->groups->table_name_groupmeta;

			$meta_sql = $groups_meta_query->get_sql( 'group', 'g', 'id' );
			$sql_array['join']  = $meta_sql['join'];
			$sql_array['where'] = self::strip_leading_and( $meta_sql['where'] );
		}

		return $sql_array;
	}

	/**
	 * Convert the 'type' parameter to 'order' and 'orderby'.
	 *
	 * @since 1.8.0
	 *
	 * @param string $type The 'type' shorthand param.
	 *
	 * @return array {
	 *     @type string $order   SQL-friendly order string.
	 *     @type string $orderby SQL-friendly orderby column name.
	 * }
	 */
	protected static function convert_type_to_order_orderby( $type = '' ) {
		$order = $orderby = '';

		switch ( $type ) {
			case 'newest' :
				$order   = 'DESC';
				$orderby = 'date_created';
				break;

			case 'active' :
				$order   = 'DESC';
				$orderby = 'last_activity';
				break;

			case 'popular' :
				$order   = 'DESC';
				$orderby = 'total_member_count';
				break;

			case 'alphabetical' :
				$order   = 'ASC';
				$orderby = 'name';
				break;

			case 'random' :
				$order   = '';
				$orderby = 'random';
				break;
		}

		return array( 'order' => $order, 'orderby' => $orderby );
	}

	/**
	 * Get a list of groups, sorted by those that have the most legacy forum topics.
	 *
	 * @since 1.6.0
	 *
	 * @param int|null          $limit           Optional. The max number of results to return.
	 *                                           Default: null (no limit).
	 * @param int|null          $page            Optional. The page offset of results to return.
	 *                                           Default: null (no limit).
	 * @param int               $user_id         Optional. If present, groups will be limited to
	 *                                           those of which the specified user is a member.
	 * @param string|bool       $search_terms    Optional. Limit groups to those whose name
	 *                                           or description field contain the search string.
	 * @param bool              $populate_extras Optional. Whether to fetch extra
	 *                                           information about the groups. Default: true.
	 * @param string|array|bool $exclude         Optional. Array or comma-separated list of group
	 *                                           IDs to exclude from results.
	 * @return array {
	 *     @type array $groups Array of group objects returned by the
	 *                         paginated query.
	 *     @type int   $total  Total count of all groups matching non-
	 *                         paginated query params.
	 * }
	 */
	public static function get_by_most_forum_topics( $limit = null, $page = null, $user_id = 0, $search_terms = false, $populate_extras = true, $exclude = false ) {
		global $wpdb, $bbdb;

		if ( empty( $bbdb ) ) {

			/** This action is documented in bp-forums/bp-forums-screens */
			do_action( 'bbpress_init' );
		}

		if ( !empty( $limit ) && !empty( $page ) ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		if ( !is_user_logged_in() || ( !bp_current_user_can( 'bp_moderate' ) && ( $user_id != bp_loggedin_user_id() ) ) )
			$hidden_sql = " AND g.status != 'hidden'";

		if ( !empty( $search_terms ) ) {
			$search_terms_like = '%' . bp_esc_like( $search_terms ) . '%';
			$search_sql        = $wpdb->prepare( ' AND ( g.name LIKE %s OR g.description LIKE %s ) ', $search_terms_like, $search_terms_like );
		}

		if ( !empty( $exclude ) ) {
			$exclude     = implode( ',', wp_parse_id_list( $exclude ) );
			$exclude_sql = " AND g.id NOT IN ({$exclude})";
		}

		$bp = buddypress();

		if ( !empty( $user_id ) ) {
			$user_id      = absint( esc_sql( $user_id ) );
			$paged_groups = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bp->groups->table_name_members} m, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql} ORDER BY f.topics DESC {$pag_sql}" );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql}" );
		} else {
			$paged_groups = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} {$exclude_sql} ORDER BY f.topics DESC {$pag_sql}" );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.topics > 0 {$hidden_sql} {$search_sql} {$exclude_sql}" );
		}

		if ( !empty( $populate_extras ) ) {
			foreach ( (array) $paged_groups as $group ) {
				$group_ids[] = $group->id;
			}
			$paged_groups = BP_Groups_Group::get_group_extras( $paged_groups, $group_ids, 'newest' );
		}

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	/**
	 * Convert the 'orderby' param into a proper SQL term/column.
	 *
	 * @since 1.8.0
	 *
	 * @param string $orderby Orderby term as passed to get().
	 * @return string $order_by_term SQL-friendly orderby term.
	 */
	protected static function convert_orderby_to_order_by_term( $orderby ) {
		$order_by_term = '';

		switch ( $orderby ) {
			case 'date_created' :
			default :
				$order_by_term = 'g.date_created';
				break;

			case 'last_activity' :
				$order_by_term = 'gm_last_activity.meta_value';
				break;

			case 'total_member_count' :
				$order_by_term = 'CONVERT(gm_total_member_count.meta_value, SIGNED)';
				break;

			case 'name' :
				$order_by_term = 'g.name';
				break;

			case 'random' :
				$order_by_term = 'rand()';
				break;
		}

		return $order_by_term;
	}

	/**
	 * Get a list of groups, sorted by those that have the most legacy forum posts.
	 *
	 * @since 1.6.0
	 *
	 * @param int|null          $limit           Optional. The max number of results to return.
	 *                                           Default: null (no limit).
	 * @param int|null          $page            Optional. The page offset of results to return.
	 *                                           Default: null (no limit).
	 * @param string|bool       $search_terms    Optional. Limit groups to those whose name
	 *                                           or description field contain the search string.
	 * @param bool              $populate_extras Optional. Whether to fetch extra
	 *                                           information about the groups. Default: true.
	 * @param string|array|bool $exclude         Optional. Array or comma-separated list of group
	 *                                           IDs to exclude from results.
	 * @return array {
	 *     @type array $groups Array of group objects returned by the
	 *                         paginated query.
	 *     @type int   $total  Total count of all groups matching non-
	 *                         paginated query params.
	 * }
	 */
	public static function get_by_most_forum_posts( $limit = null, $page = null, $search_terms = false, $populate_extras = true, $exclude = false ) {
		global $wpdb, $bbdb;

		if ( empty( $bbdb ) ) {

			/** This action is documented in bp-forums/bp-forums-screens */
			do_action( 'bbpress_init' );
		}

		if ( !empty( $limit ) && !empty( $page ) ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		if ( !is_user_logged_in() || ( !bp_current_user_can( 'bp_moderate' ) && ( $user_id != bp_loggedin_user_id() ) ) )
			$hidden_sql = " AND g.status != 'hidden'";

		if ( !empty( $search_terms ) ) {
			$search_terms_like = '%' . bp_esc_like( $search_terms ) . '%';
			$search_sql        = $wpdb->prepare( ' AND ( g.name LIKE %s OR g.description LIKE %s ) ', $search_terms_like, $search_terms_like );
		}

		if ( !empty( $exclude ) ) {
			$exclude     = implode( ',', wp_parse_id_list( $exclude ) );
			$exclude_sql = " AND g.id NOT IN ({$exclude})";
		}

		$bp = buddypress();

		if ( !empty( $user_id ) ) {
			$user_id = esc_sql( $user_id );
			$paged_groups = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bp->groups->table_name_members} m, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql} ORDER BY f.posts ASC {$pag_sql}" );
			$total_groups = $wpdb->get_results( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bp->groups->table_name_members} m, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.posts > 0 {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql} " );
		} else {
			$paged_groups = $wpdb->get_results( "SELECT DISTINCT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) AND f.posts > 0 {$hidden_sql} {$search_sql} {$exclude_sql} ORDER BY f.posts ASC {$pag_sql}" );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_groupmeta} gm3, {$bbdb->forums} f, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND g.id = gm3.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND (gm3.meta_key = 'forum_id' AND gm3.meta_value = f.forum_id) {$hidden_sql} {$search_sql} {$exclude_sql}" );
		}

		if ( !empty( $populate_extras ) ) {
			foreach ( (array) $paged_groups as $group ) {
				$group_ids[] = $group->id;
			}
			$paged_groups = BP_Groups_Group::get_group_extras( $paged_groups, $group_ids, 'newest' );
		}

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	/**
	 * Get a list of groups whose names start with a given letter.
	 *
	 * @since 1.6.0
	 *
	 * @param string            $letter          The letter.
	 * @param int|null          $limit           Optional. The max number of results to return.
	 *                                           Default: null (no limit).
	 * @param int|null          $page            Optional. The page offset of results to return.
	 *                                           Default: null (no limit).
	 * @param bool              $populate_extras Optional. Whether to fetch extra
	 *                                           information about the groups. Default: true.
	 * @param string|array|bool $exclude         Optional. Array or comma-separated list of group
	 *                                           IDs to exclude from results.
	 * @return false|array {
	 *     @type array $groups Array of group objects returned by the
	 *                         paginated query.
	 *     @type int   $total  Total count of all groups matching non-
	 *                         paginated query params.
	 * }
	 */
	public static function get_by_letter( $letter, $limit = null, $page = null, $populate_extras = true, $exclude = false ) {
		global $wpdb;

		$pag_sql = $hidden_sql = $exclude_sql = '';

		// Multibyte compliance.
		if ( function_exists( 'mb_strlen' ) ) {
			if ( mb_strlen( $letter, 'UTF-8' ) > 1 || is_numeric( $letter ) || !$letter ) {
				return false;
			}
		} else {
			if ( strlen( $letter ) > 1 || is_numeric( $letter ) || !$letter ) {
				return false;
			}
		}

		$bp = buddypress();

		if ( !empty( $exclude ) ) {
			$exclude     = implode( ',', wp_parse_id_list( $exclude ) );
			$exclude_sql = " AND g.id NOT IN ({$exclude})";
		}

		if ( !bp_current_user_can( 'bp_moderate' ) )
			$hidden_sql = " AND status != 'hidden'";

		$letter_like = bp_esc_like( $letter ) . '%';

		if ( !empty( $limit ) && !empty( $page ) ) {
			$pag_sql      = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
		}

		$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND g.name LIKE %s {$hidden_sql} {$exclude_sql}", $letter_like ) );

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND g.name LIKE %s {$hidden_sql} {$exclude_sql} ORDER BY g.name ASC {$pag_sql}", $letter_like ) );

		if ( !empty( $populate_extras ) ) {
			foreach ( (array) $paged_groups as $group ) {
				$group_ids[] = $group->id;
			}
			$paged_groups = BP_Groups_Group::get_group_extras( $paged_groups, $group_ids, 'newest' );
		}

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	/**
	 * Get a list of random groups.
	 *
	 * Use BP_Groups_Group::get() with 'type' = 'random' instead.
	 *
	 * @since 1.6.0
	 *
	 * @param int|null          $limit           Optional. The max number of results to return.
	 *                                           Default: null (no limit).
	 * @param int|null          $page            Optional. The page offset of results to return.
	 *                                           Default: null (no limit).
	 * @param int               $user_id         Optional. If present, groups will be limited to
	 *                                           those of which the specified user is a member.
	 * @param string|bool       $search_terms    Optional. Limit groups to those whose name
	 *                                           or description field contain the search string.
	 * @param bool              $populate_extras Optional. Whether to fetch extra
	 *                                           information about the groups. Default: true.
	 * @param string|array|bool $exclude         Optional. Array or comma-separated list of group
	 *                                           IDs to exclude from results.
	 * @return array {
	 *     @type array $groups Array of group objects returned by the
	 *                         paginated query.
	 *     @type int   $total  Total count of all groups matching non-
	 *                         paginated query params.
	 * }
	 */
	public static function get_random( $limit = null, $page = null, $user_id = 0, $search_terms = false, $populate_extras = true, $exclude = false ) {
		global $wpdb;

		$pag_sql = $hidden_sql = $search_sql = $exclude_sql = '';

		if ( !empty( $limit ) && !empty( $page ) )
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );

		if ( !is_user_logged_in() || ( !bp_current_user_can( 'bp_moderate' ) && ( $user_id != bp_loggedin_user_id() ) ) )
			$hidden_sql = "AND g.status != 'hidden'";

		if ( !empty( $search_terms ) ) {
			$search_terms_like = '%' . bp_esc_like( $search_terms ) . '%';
			$search_sql = $wpdb->prepare( " AND ( g.name LIKE %s OR g.description LIKE %s )", $search_terms_like, $search_terms_like );
		}

		if ( !empty( $exclude ) ) {
			$exclude     = wp_parse_id_list( $exclude );
			$exclude     = esc_sql( implode( ',', $exclude ) );
			$exclude_sql = " AND g.id NOT IN ({$exclude})";
		}

		$bp = buddypress();

		if ( !empty( $user_id ) ) {
			$user_id = esc_sql( $user_id );
			$paged_groups = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name_members} m, {$bp->groups->table_name} g WHERE g.id = m.group_id AND g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' {$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql} ORDER BY rand() {$pag_sql}" );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT m.group_id) FROM {$bp->groups->table_name_members} m LEFT JOIN {$bp->groups->table_name_groupmeta} gm ON m.group_id = gm.group_id INNER JOIN {$bp->groups->table_name} g ON m.group_id = g.id WHERE gm.meta_key = 'last_activity'{$hidden_sql} {$search_sql} AND m.user_id = {$user_id} AND m.is_confirmed = 1 AND m.is_banned = 0 {$exclude_sql}" );
		} else {
			$paged_groups = $wpdb->get_results( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' {$hidden_sql} {$search_sql} {$exclude_sql} ORDER BY rand() {$pag_sql}" );
			$total_groups = $wpdb->get_var( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm INNER JOIN {$bp->groups->table_name} g ON gm.group_id = g.id WHERE gm.meta_key = 'last_activity'{$hidden_sql} {$search_sql} {$exclude_sql}" );
		}

		if ( !empty( $populate_extras ) ) {
			foreach ( (array) $paged_groups as $group ) {
				$group_ids[] = $group->id;
			}
			$paged_groups = BP_Groups_Group::get_group_extras( $paged_groups, $group_ids, 'newest' );
		}

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
	}

	/**
	 * Fetch extra data for a list of groups.
	 *
	 * This method is used throughout the class, by methods that take a
	 * $populate_extras parameter.
	 *
	 * Data fetched:
	 *     - Logged-in user's status within each group (is_member,
	 *       is_confirmed, is_pending, is_banned)
	 *
	 * @since 1.6.0
	 *
	 * @param array        $paged_groups Array of groups.
	 * @param string|array $group_ids    Array or comma-separated list of IDs matching
	 *                                   $paged_groups.
	 * @param string|bool  $type         Not used.
	 * @return array $paged_groups
	 */
	public static function get_group_extras( &$paged_groups, &$group_ids, $type = false ) {
		$user_id = bp_loggedin_user_id();

		foreach ( $paged_groups as &$group ) {
			$group->is_member  = groups_is_user_member( $user_id, $group->id )  ? 1 : 0;
			$group->is_invited = groups_is_user_invited( $user_id, $group->id ) ? 1 : 0;
			$group->is_pending = groups_is_user_pending( $user_id, $group->id ) ? 1 : 0;
			$group->is_banned  = (bool) groups_is_user_banned( $user_id, $group->id );
		}

		return $paged_groups;
	}

	/**
	 * Delete all invitations to a given group.
	 *
	 * @since 1.6.0
	 *
	 * @param int $group_id ID of the group whose invitations are being deleted.
	 * @return int|null Number of rows records deleted on success, null on
	 *                  failure.
	 */
	public static function delete_all_invites( $group_id ) {
		global $wpdb;

		$bp = buddypress();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->groups->table_name_members} WHERE group_id = %d AND invite_sent = 1", $group_id ) );
	}

	/**
	 * Get a total group count for the site.
	 *
	 * Will include hidden groups in the count only if
	 * current_user_can( 'bp_moderate' ).
	 *
	 * @since 1.6.0
	 *
	 * @return int Group count.
	 */
	public static function get_total_group_count() {
		global $wpdb;

		$hidden_sql = '';
		if ( !bp_current_user_can( 'bp_moderate' ) )
			$hidden_sql = "WHERE status != 'hidden'";

		$bp = buddypress();

		return $wpdb->get_var( "SELECT COUNT(id) FROM {$bp->groups->table_name} {$hidden_sql}" );
	}

	/**
	 * Get global count of forum topics in public groups (legacy forums).
	 *
	 * @since 1.6.0
	 *
	 * @param string $type Optional. If 'unreplied', count will be limited to
	 *                     those topics that have received no replies.
	 * @return int Forum topic count.
	 */
	public static function get_global_forum_topic_count( $type ) {
		global $bbdb, $wpdb;

		$bp = buddypress();

		if ( 'unreplied' == $type )
			$bp->groups->filter_sql = ' AND t.topic_posts = 1';

		/**
		 * Filters the portion of the SQL related to global count of forum topics in public groups.
		 *
		 * See https://buddypress.trac.wordpress.org/ticket/4306.
		 *
		 * @since 1.6.0
		 *
		 * @param string $filter_sql SQL portion for the query.
		 * @param string $type       Type of forum topics to query for.
		 */
		$extra_sql = apply_filters( 'get_global_forum_topic_count_extra_sql', $bp->groups->filter_sql, $type );

		// Make sure the $extra_sql begins with an AND.
		if ( 'AND' != substr( trim( strtoupper( $extra_sql ) ), 0, 3 ) )
			$extra_sql = ' AND ' . $extra_sql;

		return $wpdb->get_var( "SELECT COUNT(t.topic_id) FROM {$bbdb->topics} AS t, {$bp->groups->table_name} AS g LEFT JOIN {$bp->groups->table_name_groupmeta} AS gm ON g.id = gm.group_id WHERE (gm.meta_key = 'forum_id' AND gm.meta_value = t.forum_id) AND g.status = 'public' AND t.topic_status = '0' AND t.topic_sticky != '2' {$extra_sql} " );
	}

	/**
	 * Get the member count for a group.
	 *
	 * @since 1.6.0
	 *
	 * @param int $group_id Group ID.
	 * @return int Count of confirmed members for the group.
	 */
	public static function get_total_member_count( $group_id ) {
		global $wpdb;

		$bp = buddypress();

		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = 1 AND is_banned = 0", $group_id ) );
	}

	/**
	 * Get a total count of all topics of a given status, across groups/forums.
	 *
	 * @since 1.5.0
	 *
	 * @param string      $status       Which group type to count. 'public', 'private',
	 *                                  'hidden', or 'all'. Default: 'public'.
	 * @param string|bool $search_terms Provided search terms.
	 * @return int The topic count
	 */
	public static function get_global_topic_count( $status = 'public', $search_terms = false ) {
		global $bbdb, $wpdb;

		switch ( $status ) {
			case 'all' :
				$status_sql = '';
				break;

			case 'hidden' :
				$status_sql = "AND g.status = 'hidden'";
				break;

			case 'private' :
				$status_sql = "AND g.status = 'private'";
				break;

			case 'public' :
			default :
				$status_sql = "AND g.status = 'public'";
				break;
		}

		$bp = buddypress();

		$sql = array();

		$sql['select'] = "SELECT COUNT(t.topic_id)";
		$sql['from']   = "FROM {$bbdb->topics} AS t INNER JOIN {$bp->groups->table_name_groupmeta} AS gm ON t.forum_id = gm.meta_value INNER JOIN {$bp->groups->table_name} AS g ON gm.group_id = g.id";
		$sql['where']  = "WHERE gm.meta_key = 'forum_id' {$status_sql} AND t.topic_status = '0' AND t.topic_sticky != '2'";

		if ( !empty( $search_terms ) ) {
			$search_terms_like = '%' . bp_esc_like( $search_terms ) . '%';
			$sql['where'] .= $wpdb->prepare( " AND ( t.topic_title LIKE %s )", $search_terms_like );
		}

		return $wpdb->get_var( implode( ' ', $sql ) );
	}

	/**
	 * Get an array containing ids for each group type.
	 *
	 * A bit of a kludge workaround for some issues
	 * with bp_has_groups().
	 *
	 * @since 1.7.0
	 *
	 * @return array
	 */
	public static function get_group_type_ids() {
		global $wpdb;

		$bp  = buddypress();
		$ids = array();

		$ids['all']     = $wpdb->get_col( "SELECT id FROM {$bp->groups->table_name}" );
		$ids['public']  = $wpdb->get_col( "SELECT id FROM {$bp->groups->table_name} WHERE status = 'public'" );
		$ids['private'] = $wpdb->get_col( "SELECT id FROM {$bp->groups->table_name} WHERE status = 'private'" );
		$ids['hidden']  = $wpdb->get_col( "SELECT id FROM {$bp->groups->table_name} WHERE status = 'hidden'" );

		return $ids;
	}

	/**
	 * Get SQL clause for group type(s).
	 *
	 * @since 2.6.0
	 *
	 * @param  string|array $group_types Group type(s).
	 * @param  string       $operator    'IN' or 'NOT IN'.
	 * @return string       $clause      SQL clause.
	 */
	protected static function get_sql_clause_for_group_types( $group_types, $operator ) {
		global $wpdb;

		// Sanitize operator.
		if ( 'NOT IN' !== $operator ) {
			$operator = 'IN';
		}

		// Parse and sanitize types.
		if ( ! is_array( $group_types ) ) {
			$group_types = preg_split( '/[,\s+]/', $group_types );
		}

		$types = array();
		foreach ( $group_types as $gt ) {
			if ( bp_groups_get_group_type_object( $gt ) ) {
				$types[] = $gt;
			}
		}

		$tax_query = new WP_Tax_Query( array(
			array(
				'taxonomy' => 'bp_group_type',
				'field'    => 'name',
				'operator' => $operator,
				'terms'    => $types,
			),
		) );

		$site_id  = bp_get_taxonomy_term_site_id( 'bp_group_type' );
		$switched = false;
		if ( $site_id !== get_current_blog_id() ) {
			switch_to_blog( $site_id );
			$switched = true;
		}

		$sql_clauses = $tax_query->get_sql( 'g', 'id' );

		$clause = '';

		// The no_results clauses are the same between IN and NOT IN.
		if ( false !== strpos( $sql_clauses['where'], '0 = 1' ) ) {
			$clause = self::strip_leading_and( $sql_clauses['where'] );

		// The tax_query clause generated for NOT IN can be used almost as-is.
		} elseif ( 'NOT IN' === $operator ) {
			$clause = self::strip_leading_and( $sql_clauses['where'] );

		// IN clauses must be converted to a subquery.
		} elseif ( preg_match( '/' . $wpdb->term_relationships . '\.term_taxonomy_id IN \([0-9, ]+\)/', $sql_clauses['where'], $matches ) ) {
			$clause = " g.id IN ( SELECT object_id FROM $wpdb->term_relationships WHERE {$matches[0]} )";
		}

		if ( $switched ) {
			restore_current_blog();
		}

		return $clause;
	}

	/**
	 * Strips the leading AND and any surrounding whitespace from a string.
	 *
	 * Used here to normalize SQL fragments generated by `WP_Meta_Query` and
	 * other utility classes.
	 *
	 * @since 2.7.0
	 *
	 * @param string $s String.
	 * @return string
	 */
	protected static function strip_leading_and( $s ) {
		return preg_replace( '/^\s*AND\s*/', '', $s );
	}
}
