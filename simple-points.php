<?php
/* 
Plugin Name: Simple Points
Plugin URI: TBD
Description: Award points to your users.
Version: 0.1.alpha-201209
Author: Ryan Imel
Author URI: http://wpcandy.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


// Require files
require plugin_dir_path( __FILE__ ) . 'listeners.php';



/**
 * Simple Points Condition Class
 */
Class RWI_Simple_Points_Condition {

	/* 
	 * Static property to hold our singleton instance
	 * @var SimpleBadges
	 */
	static $instance = false;


	/*
	 * This is our constructor, which is private to force the use of
	 * getInstance() to make this a singleton
	 * 
	 * @return SimpleBadges
	*/
	public function __construct() {

		add_action( 'init', array( $this, 'create_content_types' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'init', array( $this, 'initialize_metaboxes' ), 9999 );
		add_filter( 'cmb_meta_boxes', array( $this, 'metaboxes' ) );
		
		add_action( 'admin_enqueue_scripts', array( $this, 'sledge' ) );
		add_action( 'admin_footer', array( $this, 'print_results' ) );
		
		add_action( 'admin_head', array( $this, 'logic_js' ) );

	}
	
	
	/**
	 * Enqueue the javascript for the admin pages of this plugin.
	 *
	 * @static wp_enqueue_script plugins_url
	 * @global $typenow, $pagenow
	 */
	public function scripts() {
		
		global $typenow, $pagenow;
		
		if( ($pagenow == 'post-new.php' || $pagenow == 'post.php') && $typenow == 'sp_condition' )
			wp_enqueue_script( 'simplepoints-admin-script', plugins_url( 'simple-points.js', __FILE__ ) , array( 'jquery' ), 0.2, true );
	
	}
	
	
	/**
	 * Is the trigger within any point conditions?
	 */
	private function is_relevant( $trigger ) {
		$status = false;
		$relevant_posts = $this->get_conditions( $trigger );
		
		if ( $relevant_posts )
			$status = true;
		
		return $status;
	}
	
	
	/**
	 * Is this trigger one that the user can win?
	 */
	private function is_available( $condition, $user ) {
		
		$repeat = get_post_meta( $condition, '_simplepoints_condition_repeat', true );
		$user_repeats = get_user_meta( $user, 'simplepoints_repeats', false );
		
		// If this is repeatable, let it pass. If not, check for 
		// whether the one-time-use has passed.
		if ( $repeat == 'on' ) {
			return true;
		} else if ( in_array( $condition, $user_repeats ) ) { 
			return false;
		} else {
			return true;
		}

	}
	
	
	/**
	 * Check to see if the user has won this particular (non-repeatable) point already.
	 */
	private function user_has_won_already( $condition, $user ) {
		$status = false;
		$repeats = get_user_meta( $user, 'simplepoints_repeats', true );
		
		if ( ! is_array( $repeats ) ) {
			if ( $status = $repeats )
				$status = true;
		} else if ( in_array( $condition, $repeats ) ) {
			$status = true;
		}
			
		return $status;
	}
	
	
	/**
	 * Check to see if the points are one-time-only.
	 */
	private function is_one_time_only( $condition, $user ) {		
		$repeat = get_post_meta( $condition, '_simplepoints_condition_repeat', true );

		if ( $repeat == 'on' ) {
			return false;
		} else {
			return true;
		}

	}
	
	
	/**
	 * Check to see if a given point value deserves to be awarded.
	 */
	public function check( $trigger, $user, $target ) {
		
		// We don't care if the trigger isn't in use. Kill early.
		if ( ! $this->is_relevant( $trigger ) )
			return;
		
		// Get only the relevant conditions	
		$conditions = $this->get_conditions( $trigger );

		foreach( $conditions as $condition ) {
			
			// Is this available to the current user?
			if ( $this->is_available( $condition->ID, $user ) ) {

				$check = array(
					'condition_id'			=> $condition->ID,
					'trigger_id'			=> $trigger,
					'trigger_type'			=> $this->get_trigger_type( $trigger ),
					'trigger_argument'		=> $this->get_condition_trigger_argument( $condition->ID, $this->get_trigger_type( $trigger ) ),
					'user'					=> $user,
					'target'				=> $target,
					'trigger_user_input'	=> $this->get_condition_user_input( $condition->ID ),
					'point_value'			=> get_post_meta( $condition->ID, '_simplepoints_condition_point_value', true )
				);

				if ( $this->is_one_time_only( $condition->ID, $user ) ) {
					$this->one_time_complete( $condition->ID, $user );
				}
				
				$this->evaluate( $check );

			}

		}

	}


	/**
	 * Save a one time only point condition as complete.
	 */
	private function one_time_complete( $condition, $user ) {
		$repeats = get_user_meta( $user, 'simplepoints_repeats', false );

		if ( ! in_array( $condition, $repeats ) ) {
			add_user_meta( $user, 'simplepoints_repeats', $condition, false );
		}
	}


	/**
	 * Evaluate whether a point should be given.
	 */
	private function evaluate( $check ) {
		
		$type = $check['trigger_type'];
		$argument = $check['trigger_argument'];
		$input = $check['trigger_user_input'];
		$target = $check['target'];
		
		$status = false;
		
		switch ( $type ) {
			case 'decimal':
				
				switch ( $argument ) {
					
					case 'is_equal_to':
						if ( $target == $input )
							$status = true;
						break;
						
					case 'is_less_than':
						if ( $target < $input )
							$status = true;
						break;
					
					case 'is_greater_than':
						if ( $target > $input )
							$status = true;
						break;
						
					case 'is_greater_than_or_equal_to':
						if ( $target >= $input )
							$status = true;
						break;
						
					case 'is_less_than_or_equal_to':
						if ( $target <= $input )
							$status = true;
						break;
						
					case 'is_not_equal_to':
						if ( $target !== $input )
							$status = true;
						break;
					
				}
				
				break;
				
			case 'string':
			
				switch ( $argument ) {
					case 'string_is':
						if ( $target == $input )
							$status = true;
						break;
					case 'string_is_not':
						if ( $target !== $input )
							$status = true;
						break;
				}
			
				break;
				
			case 'bool':
				if ( $target == true )
					$status = true;
				break;
		}
		
		if ( $status == true )
			$this->add_points( $check['condition_id'], $check['user'], $check['point_value'] );
			
	}
	
	
	/**
	 * Add points to someone already!
	 */
	private function add_points( $condition, $user, $new_points ) {
		$points = get_user_meta( $user, 'simplepoints_points', true );
		$points = $points + $new_points;
		update_user_meta( $user, 'simplepoints_points', $points );
		
		// Action so we can do cool stuff when this happens.
		do_action( 'simplepoints_after_adding', $condition, $user, $new_points );
	}
	

	/**
	 * Print the results of something. Only for testing.
	 */
	public function print_results( $stuff ) {
		//delete_user_meta( 1, 'simplepoints_repeats' );
		//print_r( get_user_meta( 1 ) );
	}
	
	
	/**
	 * Get a trigger's information.
	 */
	private function get_trigger( $trigger ) {
		
		$triggers = $this->helper();
		$trigger_info = $triggers[$trigger];
		
		return $trigger_info;
	}
	
	
	/**
	 * Get a trigger's type from ID.
	 */
	private function get_trigger_type( $trigger ) {
		$this_trigger = $this->get_trigger( $trigger );
		$type = $this_trigger['type'];
		
		return $type;
	}
	
	
	/**
	 * Get a condition's argument based on trigger type and condition ID.
	 */
	private function get_condition_trigger_argument( $condition, $trigger_type ) {
		if ( $trigger_type == 'decimal' )
			return get_post_meta( $condition, '_simplepoints_condition_parttwo-decimal', true );
			
		if ( $trigger_type == 'string' )
			return get_post_meta( $condition, '_simplepoints_condition_parttwo-string', true );
	
		if ( $trigger_type == 'bool' )
			return 'bool';
	}
	
	
	/**
	 * Get a condition's user input value.
	 */
	private function get_condition_user_input( $condition ) {
		$input = get_post_meta( $condition, '_simplepoints_condition_partthree', true );
		
		return $input;
	}
	
	
	/**
	 * Get a trigger's ID via its value.
	 */
	private function get_trigger_by_value( $trigger_value ) {
		$triggers = $this->helper();
		$triggers_by_value = wp_list_pluck( $triggers, 'value' );
		$target = array_search( $trigger_value, $triggers_by_value );
		
		$trigger_id = $triggers[$target]['id'];
		
		return $trigger_id;
	}
	
	
	/**
	 * Get a trigger's value via its ID.
	 */
	private function get_value_by_trigger_id( $trigger_id ) {

		$triggers = $this->helper();		
		$trigger_value = $triggers[$trigger_id]['value'];
		
		return $trigger_value;
	}
	
	
	/**
	 * Get a condition's trigger.
	 */
	private function get_condition_trigger( $condition ) {
		$trigger_value = get_post_meta( $condition, '_simplepoints_condition_partone' );
		$trigger_id = $this->get_trigger_by_value( $trigger_value );
		
		return $trigger_id;
	}
	
	
	/**
	 * Query for conditions based on specific queries. 
	 */
	private function get_conditions( $trigger ) {
		
		// Query all conditions with the given trigger value.
		$conditions = get_posts( array(
			'post_type'		=> 'sp_condition',
			'meta_query'	=> array(
				array(
					'key'	=> '_simplepoints_condition_automatic',
					'value'	=> 'on'
				),
				array(
					'key'	=> '_simplepoints_condition_partone',
					'value'	=> $this->get_value_by_trigger_id( $trigger )
				)
			)
		) );
		
		return $conditions;		
	}


	/**
	 * Content creation we need.
	 */
	public function create_content_types() {

		// Logging post type
		register_post_type( 'sp_condition',
			array(

				'labels' => array(

					'name' => __( 'Point Conditions' ),
					'singular_name' => __( 'Point Condition' ),
					'add_new' => __( 'Add New' ),
					'all_items' => __( 'Conditions' ),
					'add_new_item' => __( 'Add New Condition' ),
					'edit_item' => __( 'Edit Condition' ),
					'new_item' => __( 'New Condition' ),
					'view_item' => __( 'View Condition' ),
					'search_items' => __( 'Search Conditions' ),
					'not_found' => __( 'Point conditions not found.' ),
					'not_found_in_trash' => __( 'Point conditions not found in Trash' ),
					'parent_item_colon' => __( 'Parent Condition' ),
					'menu_name' => __( 'Points' )

				),

				'description' => 'Provided by the Simple Points plugin.',
				'public' => false,
				'exclude_from_search' => true,	 			
				'publicly_queryable' => false,
				'show_ui' => true,
				'show_in_nav_menus' => true,
				'show_in_admin_bar' => false,
				'menu_position' => 200,
				'menu_icon' => plugins_url( 'icon.png', __FILE__ ),
				'capabilities' => array(
					'publish_posts' => 'manage_options',
					'edit_posts' => 'manage_options',
					'edit_others_posts' => 'manage_options',
					'delete_posts' => 'manage_options',
					'read_private_posts' => 'manage_options',
					'edit_post' => 'manage_options',
					'delete_post' => 'manage_options',
					'read_post' => 'read'
				),
				'hierarchical' => false,
				'supports'	=> array( 'title' ),
				'has_archive' => false,
				'rewrite'	=> false,
				'can_export' => false,

	 		)
	 	);

	}
	
	
	/**
	 * Helper for listeners.
	 */
	public function helper() {
		
		$listeners = apply_filters( 'simple_points_helper', array( 
			array(
				"id"	=> "sp_core_choose",
				"name"	=> "Choose your condition.",
				"value"	=> "choose",
				"type"	=> "choose"
			)
		) );

		foreach ( $listeners as $listener ) {
			$triggers[$listener['id']] = array(
				'id'	=> $listener['id'],
				'name'	=> $listener['name'],
				'value'	=> $listener['value'],
				'type'	=> $listener['type']
			);
		}
		
		return $triggers;
	}
	
	
	/**
	 * Specify which trigger IDs will queue up which arguments.
	 */
	public function metabox_logic() {
		
		$triggers = $this->helper();
		
		foreach( $triggers as $trigger ) {
			if ( $trigger['type'] == 'decimal' )
				$decimals[] = $trigger['value'];
			if ( $trigger['type'] == 'string' )
				$strings[] = $trigger['value'];
			if ( $trigger['type'] == 'bool' )
				$bools[] = $trigger['value'];
		}
		
		if ( ! isset( $decimals ) )
			$decimals = array();
				
		if ( ! isset( $strings ) )
			$strings = array();
			
		if ( ! isset( $bools ) )
			$bools = array();
		
		$types = array(
			'decimals'	=> $decimals,
			'strings'	=> $strings,
			'bools'		=> $bools
		);
		
		return $types;
	}
	
	
	/**
	 * Print out the jQuery we will need to show/hide the right metabox items.
	 */
	public function logic_js() {
		
		global $typenow, $pagenow;
		
		// If the page isn't our target, then kill it
		if( ! ( ($pagenow == 'post-new.php' || $pagenow == 'post.php') && $typenow == 'sp_condition' ) )
			return;

		$types = $this->metabox_logic();
		
		// If part one dropdown = decimals, load those specific options
		// If part one dropdown = strings, load those specific options
		// If part one dropdown = bools, load those specific options
		
		echo '<script>
		
			jQuery(document).ready(function() {
				
				jQuery( window ).load( function() {
					hideRows();
				})
				
				jQuery( "#_simplepoints_condition_partone" ).change( function() {
					hideRows();
				});
				
				jQuery( "#_simplepoints_condition_partone" ).change( function() {
					hideRows();
				});

				var hideRows = function() {
					var decimals = ' . json_encode( $types['decimals'] ) . ';
					var strings = ' . json_encode( $types['strings'] ) . ';
					var bools = ' . json_encode( $types['bools'] ) . ';
					$drows = "#simplepoints_condition_triggers tr:eq(4), #simplepoints_condition_triggers tr:eq(6)";
					$srows = "#simplepoints_condition_triggers tr:eq(5), #simplepoints_condition_triggers tr:eq(6)";
					
					$value = String(jQuery( "#_simplepoints_condition_partone ").val());
					
					if ( jQuery.inArray( $value, decimals ) > -1 ) {
						jQuery( $srows ).css( "display", "none" );
						jQuery( $drows ).css( "display", "table-row" );
					}
					
					if ( jQuery.inArray( $value, strings ) > -1 ) {
						jQuery( $drows ).css( "display", "none" );
						jQuery( $srows ).css( "display", "table-row" );
					}
					
					if ( jQuery.inArray( $value, bools ) > -1 ) {
						jQuery( $drows ).css( "display", "none" );
						jQuery( $srows ).css( "display", "none" );
					}
				}
				
			});
		
		</script>';

	}


	/**
	 * Sledgehammer.
	 */ 
	public function sledge() {
	}
	
	
	/**
	 * Define the triggers the metabox will offer up.
	 */
	private function metabox_triggers() {
		
		$triggers = $this->helper();
		
		foreach( $triggers as $trigger ) {
			$all[] = array(
				'name'	=> $trigger['name'],
				'value'	=> $trigger['value']
			);
		}
		
		return $all;
	}
	
	
	/**
	 * Define the metabox and field configurations.
	 *
	 * @param  array $meta_boxes
	 * @return array
	 */
	function metaboxes( array $meta_boxes ) {

		// Start with an underscore to hide fields from custom fields list
		$prefix = '_simplepoints_condition_';

		$meta_boxes[] = array(
			'id'         => 'simplepoints_condition_triggers',
			'title'      => 'Options',
			'pages'      => array( 'sp_condition' ), // Post type it's active on
			'context'    => 'normal',
			'priority'   => 'high',
			'show_names' => true, // Show field names on the left
			'fields'     => array(
				array(
					'name'	=> 'Point Value',
					'id'	=> $prefix . 'point_value',
					'type'	=> 'text_small'
				),
				array(
					'name'	=> 'Automatic Points',
					'desc'	=> 'Add these points automatically (default is manual).',
					'id'	=> $prefix . 'automatic',
					'type'	=> 'checkbox',
				),
				array(
					'name'	=> 'Repeat Points',
					'desc'	=> 'These points can be won over and over again (default is only once).',
					'id'	=> $prefix . 'repeat',
					'type'	=> 'checkbox'
				),
				array(
					'name' => 'Award if&hellip;',
					//'desc' => 'field description (optional)',
					'id'   => $prefix . 'partone',
					'type' => 'select',
					'options' => $this->metabox_triggers()
				),
				array(
					'name' => '',
					//'desc' => 'field description (optional)',
					'id'   => $prefix . 'parttwo-decimal',
					'type' => 'select',
					'options' => array(
						array( 'name' => 'is equal to', 'value' => 'is_equal_to', ),
						array( 'name' => 'is less than', 'value' => 'is_less_than', ),
						array( 'name' => 'is greater than', 'value' => 'is_greater_than', ),
						array( 'name' => 'is greater than or equal to', 'value' => 'is_greater_than_or_equal_to', ),
						array( 'name' => 'is less than or equal to', 'value' => 'is_less_than_or_equal_to', ),
						array( 'name' => 'is not equal to', 'value' => 'is_not_equal_to', ),
					)
				),
				array(
					'name' => '',
					'id'   => $prefix . 'parttwo-string',
					'type' => 'select',
					'options' => array(
						array( 'name' => 'is', 'value' => 'string_is', ),
						array( 'name' => 'is not', 'value' => 'string_is_not', ),
					)
				),
				array(
					'name' => '',
					'id'   => $prefix . 'partthree',
					'type' => 'text_small',
				),
			),
		);

		// Add other metaboxes as needed

		return $meta_boxes;
	}
	
	
	/**
	 * Initialize the metabox class.
	 */
	function initialize_metaboxes() {

		// Looks for the CMB class
		if ( ! class_exists( 'cmb_Meta_Box' ) )
			require_once 'metabox/custom-metaboxes-and-fields.php';

	}
	

}


// Instantiate our class
$SimpleCondition = new RWI_Simple_Points_Condition;



add_filter('manage_users_columns', 'pippin_add_user_id_column');

function pippin_add_user_id_column($columns) {
    $columns['simplepoints_points'] = 'Points';
    return $columns;
}
 
add_action('manage_users_custom_column', 'pippin_show_user_id_column_content', 10, 3);

function pippin_show_user_id_column_content($value, $column_name, $user_id) {
	$user = get_userdata( $user_id );
	$points = get_user_meta( $user_id, 'simplepoints_points', true );
	$repeats = implode( ", ", get_user_meta( $user_id, 'simplepoints_repeats', false ) );
	
	if ( ! $points )
		$points = 0;
	
	if ( 'simplepoints_points' == $column_name )
		return $points;
}


// Credit The Noun Project for the use of their icon in our dashboard menu.
add_filter( 'admin_footer_text', 'rwi_simplepoints_admin_dashboard_footer', 1 );

function rwi_simplepoints_admin_dashboard_footer() {
	
	if ( function_exists( 'rwi_simplebadges_admin_dashboard_footer' ) ) {
		
		remove_filter( 'admin_footer_text', 'rwi_simplebadges_admin_dashboard_footer' );
		
		echo 'Thank you for creating with <a href="http://wordpress.org/">WordPress</a>. &ldquo;Badge&rdquo; symbol by P.J. Onori, &ldquo;Video Game Controller&rdquo; <br />symbol by Joshua Theissen from <a href="http://thenounproject.com/">The Noun Project</a> collection.';
	
	} else {
		
		echo 'Thank you for creating with <a href="http://wordpress.org/">WordPress</a>. &ldquo;Video Game Controller&rdquo; symbol by Joshua Theissen from <a href="http://thenounproject.com/">The Noun Project</a> collection.';
	
	}

}

