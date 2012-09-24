<?php

/**
 * User login listener
 */
add_filter( 'simple_points_helper', 'rwi_user_login_bool', 10, 1 );
function rwi_user_login_bool( $array ) {
	$array[] = array(
		'id'	=> 'rwi_user_login',
		'name'	=> 'User has logged in',
		'value'	=> 'user_logged_in',
		'type'	=> 'bool'
	);
	
	return $array;
}

add_action( 'init', 'rwi_user_login_bool_action', 10 );
function rwi_user_login_bool_action() {
	global $SimplePointsHelper;
	
	if ( is_user_logged_in() ) {
		$status = true;
		$user_id = get_current_user_id();
		$SimplePointsHelper->check( 'rwi_user_login', $user_id, $status );
	}

}