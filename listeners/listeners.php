<?php

/**
 * Core Listeners
 */

// Listener array
$listeners = array(
	'user-comment-count',
	'user-has-logged-in',
	'user-post-count',
	'user-has-website',
	'user-email'
);

foreach ( $listeners as $listener ) {
	include plugin_dir_path( __FILE__ ) . $listener . '.php';
}