<?php


// Comment count filter
add_filter( 'simple_points_helper', 'rwi_user_comment_count', 10, 1 );

function rwi_user_comment_count( $array ) {
	$array[] = array(
		'id'	=> 'rwi_user_comment_count',
		'name'	=> 'User comment count', 
		'value'	=> 'user_comment_count',
		'type'	=> 'decimal'
	);
	
	return $array;
}



add_action( 'comment_post', 'rwi_user_comment_count_action', 99999, 1 );

function rwi_user_comment_count_action( $comment_id ) {
	global $SimplePointsHelper;
	
	$the_comment = get_comment( $comment_id );
	$commenter_email = $the_comment->comment_author_email;
	$user_info = get_user_by( 'email', $commenter_email );
	
	if ( ! $user_info )
		return;
		
	$user_id = $user_info->ID;
	$id = 'rwi_user_comment_count';
	
	global $wpdb;
	$comment_count = $wpdb->get_var('SELECT COUNT(comment_ID) FROM ' . $wpdb->comments. ' WHERE comment_author_email = "' . $commenter_email . '"');
	
	$SimplePointsHelper->check( $id, $user_id, $comment_count );
}