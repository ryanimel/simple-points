<?php

/**
 * Badge award listener
 */
add_filter( 'simple_points_helper', 'rwi_user_badge_count', 10, 1 );
function rwi_user_badge_count( $array ) {
	$array[] = array(
		'id'	=> 'rwi_user_badge_count',
		'name'	=> 'User badge count',
		'value'	=> 'user_badge_count',
		'type'	=> 'decimal'
	);
	
	return $array;
}

add_action( 'simplebadges_after_adding', 'rwi_user_badge_count_action', 10, 2 );
function rwi_user_badge_count_action( $badge_id, $user_id ) {
	global $SimpleCondition;
	
	$user_badges = get_user_meta( $user_id, 'simplebadges_badges' );
	$count = count( $user_badges );
	
	$SimpleCondition->check( 'rwi_user_badge_count', $user_id, $count );
}
