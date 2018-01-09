<?php

/**
 * Registered report callbacks.
 *
 * @return array
 */
function olur_report_callbacks() {
	$callbacks = array(
		// Users.
		'User' => array(
			'Students' => array( 'label' => 'Studenti', 'type' => 'student' ),
			'Faculty'  => array( 'label' => 'Fakulty', 'type' => 'faculty' ),
			'Staff'    => array( 'label' => 'Zaměstnanci', 'type' => 'staff' ),
			'Alumni'   => array( 'label' => 'Absolventi', 'type' => 'alumni' ),
			'Other'    => array( 'label' => 'Ostatní', 'type' => 'other' ),
			'Total'    => array( 'label' => 'Celkem', 'type' => 'total' ),
		),

		// Groups.
		'Group' => array(
			array( 'label' => 'Kurzy (Veřejné)', 'type' => 'course', 'status' => 'public' ),
			array( 'label' => 'Kurzy (Soukromé)', 'type' => 'course', 'status' => 'private' ),
			array( 'label' => 'Kurzy (Skryté)', 'type' => 'course', 'status' => 'hidden' ),
			array( 'label' => 'Kurzy (Celkem)', 'type' => 'course', 'status' => 'any' ),
			'',

			array( 'label' => 'Skupiny (Veřejné)', 'type' => 'club', 'status' => 'public' ),
			array( 'label' => 'Skupiny (Private)', 'type' => 'club', 'status' => 'private' ),
			array( 'label' => 'Skupiny (Skryté)', 'type' => 'club', 'status' => 'hidden' ),
			array( 'label' => 'Skupiny (Celkem)', 'type' => 'club', 'status' => 'any' ),

			'',

			array( 'label' => 'Projekty (Veřejné)', 'type' => 'project', 'status' => 'public' ),
			array( 'label' => 'Projekty (Soukromé)', 'type' => 'project', 'status' => 'private' ),
			array( 'label' => 'Projekty (Skryté)', 'type' => 'project', 'status' => 'hidden' ),
			array( 'label' => 'Projekty (Celkem)', 'type' => 'project', 'status' => 'any' ),
		),

		// Portfolios.
		'Portfolio' => array(
			array( 'label' => 'Studentské portfolio (Veřejné)', 'type' => 'student', 'status' => 'public' ),
			array( 'label' => 'Studentské portfolio (Soukromé)', 'type' => 'student', 'status' => 'private' ),
			array( 'label' => 'Studentské portfolio (Skryté)', 'type' => 'student', 'status' => 'hidden' ),
			array( 'label' => 'Studentské portfolio (Celkem)', 'type' => 'student', 'status' => 'any' ),

			'',

			array( 'label' => 'Fakultní portfolio (Veřejné)', 'type' => 'faculty', 'status' => 'public' ),
			array( 'label' => 'Fakultní portfolio (Soukromé)', 'type' => 'faculty', 'status' => 'private' ),
			array( 'label' => 'Fakultní portfolio (Skryté)', 'type' => 'faculty', 'status' => 'hidden' ),
			array( 'label' => 'Fakultní portfolio (Celkem)', 'type' => 'faculty', 'status' => 'any' ),

			'',

			array( 'label' => 'Portfolio zaměstnance (Veřejné)', 'type' => 'staff', 'status' => 'public' ),
			array( 'label' => 'Portfolio zaměstnance (Soukromé)', 'type' => 'staff', 'status' => 'private' ),
			array( 'label' => 'Portfolio zaměstnance (Skryté)', 'type' => 'staff', 'status' => 'hidden' ),
			array( 'label' => 'Portfolio zaměstnance (Celkem)', 'type' => 'staff', 'status' => 'any' ),
		),

		// Activity.
		'Activity' => array(

			array( 'PROFILES', 'Total Instances', 'Total Unique Users', 'Students', 'Faculty', 'Staff', 'Alumni', 'Other Users' ),
			array( 'label' => 'New Avatar', 'component' => 'profile', 'type' => 'new_avatar' ),
			array( 'label' => 'Profile Update', 'component' => 'xprofile', 'type' => 'updated_profile' ),

			// @todo These are probably not accurate because of 'site_public'.
			'',
			array( 'SITES', 'Total Instances', 'Total Unique Users', 'Students', 'Faculty', 'Staff', 'Alumni', 'Other Users', 'Groups', 'Courses', 'Clubs', 'Projects', 'ePortfolios', 'Portfolios' ),
			array( 'label' => 'New Site', 'component' => 'groups', 'type' => 'new_blog' ),
			array( 'label' => 'New Site Posts', 'component' => 'groups', 'type' => 'new_blog_post' ),
			array( 'label' => 'New Site Comments', 'component' => 'groups', 'type' => 'new_blog_comment' ),

			'',
			array( 'GROUP FILES', 'Total Instances', 'Total Unique Users', 'Students', 'Faculty', 'Staff', 'Alumni', 'Other Users', 'Groups', 'Courses', 'Clubs', 'Projects', 'ePortfolios', 'Portfolios' ),
			array( 'label' => 'Group File Created', 'component' => 'groups', 'type' => 'added_group_document' ),
			array( 'label' => 'Group File Edited', 'component' => 'groups', 'type' => 'edited_group_document' ),
			array( 'label' => 'Group File Deleted', 'component' => 'groups', 'type' => 'deleted_group_document' ),

			'',
			array( 'DISCUSSION FORUMS (since 2014)', 'Total Instances', 'Total Unique Users', 'Students', 'Faculty', 'Staff', 'Alumni', 'Other Users', 'Groups', 'Courses', 'Clubs', 'Projects', 'ePortfolios', 'Portfolios' ),
			array( 'label' => 'New Topics', 'component' => 'groups', 'type' => 'bbp_topic_create' ),
			array( 'label' => 'Replies', 'component' => 'groups', 'type' => 'bbp_reply_create' ),

			'',
			array( 'DOCS', 'Total Instances', 'Total Unique Users', 'Students', 'Faculty', 'Staff', 'Alumni', 'Other Users', 'Groups', 'Courses', 'Clubs', 'Projects', 'ePortfolios', 'Portfolios' ),
			array( 'label' => 'New Doc', 'component' => 'groups', 'type' => 'bp_doc_created' ),
			array( 'label' => 'Edit Doc', 'component' => 'groups', 'type' => 'bp_doc_edited' ),
			array( 'label' => 'New Doc Comment', 'component' => 'groups', 'type' => 'bp_doc_comment' ),

			'',
			array( 'GROUP JOINS', 'Total Instances', 'Total Unique Users', 'Students', 'Faculty', 'Staff', 'Alumni', 'Other Users', 'Groups', 'Courses', 'Clubs', 'Projects', 'ePortfolios', 'Portfolios' ),
			array( 'label' => 'Joined Group', 'component' => 'groups', 'type' => 'joined_group' ),

		),

		// Friendships.
		'Friend' => array(
			array( 'FRIENDS (Confirmed/Pending)', 'Student', 'Faculty', 'Staff', 'Alumni', 'Other', 'Total' ),
			array( 'label' => 'Student', 'type' => 'student' ),
			array( 'label' => 'Faculty', 'type' => 'faculty' ),
			array( 'label' => 'Staff', 'type' => 'staff' ),
			array( 'label' => 'Alumni', 'type' => 'alumni' ),
			array( 'label' => 'Other', 'type' => 'other' ),
			array( 'label' => 'Total', 'type' => 'total' ),
		),
	);

	return $callbacks;
}

/**
 * Generate a report and serve as a CSV.
 *
 * @param string $start MySQL-formatted start date.
 * @param string $end MySQL-formatted end date.
 */
function olur_generate_report( $start, $end ) {
	$data = olur_generate_report_data( $start, $end );

	$start_formatted = date( 'Y-m-d', strtotime( $start ) );
	$end_formatted   = date( 'Y-m-d', strtotime( $end ) );
	$filename = sprintf( 'openlab-usage-%s-through-%s.csv', $start_formatted, $end_formatted );

	$title_row = array(
		sprintf( 'OpenLab usage for the dates %s through %s', $start_formatted, $end_formatted ),
	);

	$header_row = array(
		0 => '',
		1 => 'Start #',
		2 => '# Created',
		4 => 'End #',
		5 => 'Actively Active',
		6 => 'Passively Active',
	);

	$fh = @fopen( 'php://output', 'w' );

	//fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );

	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Content-Description: File Transfer' );
	header( 'Content-type: text/csv' );
	header( "Content-Disposition: attachment; filename={$filename}" );
	header( 'Expires: 0' );
	header( 'Pragma: public' );

	fputcsv( $fh, $title_row );
	fputcsv( $fh, $header_row );

	foreach ( $data as $data_row ) {
		fputcsv( $fh, $data_row );
	}

	fclose( $fh );
	die();
}

/**
 * Generate report data.
 *
 * @param string $start MySQL-formatted start date.
 * @param string $end MySQL-formatted end date.
 * @return array
 */
function olur_generate_report_data( $start, $end ) {
	$data = array();
	$callbacks = olur_report_callbacks();

	foreach ( $callbacks as $class_name => $queries ) {
		$class_name = '\OLUR\\' . $class_name;
		$counter    = new $class_name;

		$counter->set_start( $start );
		$counter->set_end( $end );

		foreach ( $queries as $query ) {
			// If the query doesn't have a label, it's a literal.
			// Used for blank rows and other labels.
			if ( ! isset( $query['label'] ) ) {
				$data[] = (array) $query;
			} else {
				$counter->set_label( $query['label'] );
				unset( $query['label'] );

				$counter->query( $query );

				$data[] = $counter->format_results_for_csv();
			}
		}

		// Insert an empty row after each section.
		$data[] = array();
	}

	return $data;
}
