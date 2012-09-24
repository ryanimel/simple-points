<?php

/**
 * User profile website listener
 */
add_filter( 'simple_points_helper', 'rwi_user_profile_website', 10, 1 );
function rwi_user_profile_website( $array ) {
	$array[] = array(
		'id'	=> 'rwi_user_profile_website',
		'name'	=> 'User has website in profile',
		'value'	=> 'user_profile_website',
		'type'	=> 'bool'
	);
	
	return $array;
}

add_action( 'profile_update', 'rwi_user_profile_website_action', 9999, 1 );
function rwi_user_profile_website_action( $user_id ) {

	$website = get_usermeta( $user_id, 'user_url' );

	if ( $website ) {
		$status = true;
	} else {
		$status = false;
	}

	global $SimplePointsHelper;
	$SimplePointsHelper->check( 'rwi_user_profile_website', $user_id, $status );
}
