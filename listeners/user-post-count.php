<?php

/**
 * User post count listener
 */
add_filter( 'simple_points_helper', 'rwi_user_post_count', 10, 1 );
function rwi_user_post_count( $array ) {
	$array[] = array(
		'id'	=> 'rwi_user_post_count',
		'name'	=> 'User post count',
		'value'	=> 'user_post_count',
		'type'	=> 'decimal'
	);
	
	return $array;
}

add_action( 'new_to_publish', 'rwi_user_post_count_action', 9999, 1 );
add_action( 'draft_to_publish', 'rwi_user_post_count_action', 9999, 1 );
add_action( 'pending_to_publish', 'rwi_user_post_count_action', 9999, 1 );
function rwi_user_post_count_action( $post ) {
	global $wpdb;
	
	$user_id = $post->post_author;
	$id = 'rwi_user_post_count';
	$count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_author = '" . $user_id . "' AND post_type = 'post' AND post_status = 'publish'");
	
	
	global $SimpleCondition;
	$SimpleCondition->check( $id, $user_id, $count );

}
