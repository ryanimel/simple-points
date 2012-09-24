<?php

/**
 * Core Listener: User post count
 */


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
	global $SimpleCondition;
	
	$the_comment = get_comment( $comment_id );
	$commenter_email = $the_comment->comment_author_email;
	$user_info = get_user_by( 'email', $commenter_email );
	
	if ( ! $user_info )
		return;
		
	$user_id = $user_info->ID;
	$id = 'rwi_user_comment_count';
	
	global $wpdb;
	$comment_count = $wpdb->get_var('SELECT COUNT(comment_ID) FROM ' . $wpdb->comments. ' WHERE comment_author_email = "' . $commenter_email . '"');
	
	$SimpleCondition->check( $id, $user_id, $comment_count );
}




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
	global $SimpleCondition;
	
	if ( is_user_logged_in() ) {
		$status = true;
		$user_id = get_current_user_id();
		$SimpleCondition->check( 'rwi_user_login', $user_id, $status );
	}

}
