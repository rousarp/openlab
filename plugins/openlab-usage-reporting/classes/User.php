<?php

namespace OLUR;

class User implements Counter {
	use CounterTools;

	public function query( $query ) {
		global $wpdb;

		$bp = buddypress();

		$user_type = $query['type'];

		$ut_clause = '';
		if ( 'other' === $user_type ) {
			$ut_clause = "AND u.ID NOT IN (SELECT user_id FROM {$bp->profile->table_name_data} WHERE field_id = 7 AND value IN ('Student','Faculty','Staff','Alumni'))";
		} elseif ( 'total' !== $user_type ) {
			$ut_clause = $wpdb->prepare( "AND u.ID IN (SELECT user_id FROM {$bp->profile->table_name_data} WHERE field_id = 7 AND value = %s)", $user_type );
		}

		$counts = array(
			'start'   => '',
			'created' => '',
			'end'     => '',
			'activea' => '',
			'activep' => 'N/A',
		);

		// Start
		$counts['start'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->users} u WHERE u.deleted != 1 AND u.spam != 1 {$ut_clause} AND u.user_registered < %s", $this->start ) );

		// End
		$counts['end'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->users} u WHERE u.deleted != 1 AND u.spam != 1 {$ut_clause} AND u.user_registered < %s", $this->end ) );

		// Created
		$counts['created'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->users} u WHERE u.deleted != 1 AND u.spam != 1 {$ut_clause} AND u.user_registered >= %s AND u.user_registered < %s", $this->start, $this->end ) );

		// Active
		$counts['activea'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u JOIN {$bp->activity->table_name} a ON a.user_id = u.ID WHERE u.deleted != 1 AND u.spam != 1 {$ut_clause} AND ( a.component != 'members' OR a.type != 'last_activity' ) AND a.date_recorded >= %s AND a.date_recorded <= %s", $this->start, $this->end ) );

		// Passively active (last_activity). Only count if `$end` is today.
		$end_day = date( 'Y-m-d', strtotime( $this->end ) );
		$today   = date( 'Y-m-d' );
		if ( $end_day === $today ) {
			$counts['activep'] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u JOIN {$bp->activity->table_name} a ON a.user_id = u.ID WHERE u.deleted != 1 AND u.spam != 1 {$ut_clause} AND a.date_recorded >= %s", $this->start, $this->end ) );
		}

		$this->counts = $counts;

		return $this->counts;
	}
}
