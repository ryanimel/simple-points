<?php

/**
 * Core Listener: User post count
 */

// Add as filter?
simple_points_core_listener_user_post_count() {
	
	// Include arguments necessary to know trigger info
	$args = array(
		'id'	=> 'user_post_count',
		'human'	=> 'User post count',
		'type'	=> 'numeral'
	);
	
	return $array;
}
