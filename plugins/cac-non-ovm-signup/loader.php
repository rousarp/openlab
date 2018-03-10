<?php

/*
Plugin Name: CAC Non-OVM Signup
Description: Allows admin to enter validation codes for non-CUNY email addresses to register
Version: 1.0
Author: Boone Gorges / otevreny urad
*/

function cac_non_ovm_signup_loader() {
	require( dirname( __FILE__ ) . '/cac-non-ovm-signup.php' );
}
add_action( 'bp_include', 'cac_non_ovm_signup_loader' );

?>
