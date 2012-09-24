<?php

/**
 * User profile email listener
 */
add_filter( 'simple_points_helper', 'rwi_user_profile_email', 10, 1 );
function rwi_user_profile_email( $array ) {
	$array[] = array(
		'id'	=> 'rwi_user_profile_email',
		'name'	=> 'User profile email',
		'value'	=> 'user_profile_email',
		'type'	=> 'string'
	);
	
	return $array;
}

add_action( 'profile_update', 'rwi_user_profile_email_action', 9999, 1 );
function rwi_user_profile_email_action( $user_id ) {

	$email = get_usermeta( $user_id, 'user_email' );

	global $SimpleCondition;
	$SimpleCondition->check( 'rwi_user_profile_email', $user_id, $email );
}