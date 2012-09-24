<?php

/**
 * Core Listeners
 */

// Listener array
$listeners = array(
	'user-comment-count',
	'badge-award-count',
	'user-has-logged-in',
	'user-post-count'
);

foreach ( $listeners as $listener ) {
	include plugin_dir_path( __FILE__ ) . $listener . '.php';
}