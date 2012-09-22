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
		
		// Query all conditions with the given trigger value.
		$relevant_posts = get_posts( 'post_type=sp_condition&meta_value=user_post_count' );
		
		if ( $relevant_posts )
			$status = true;
		
		return $status;
	}
	
	
	/**
	 * Sledge check.
	 */ 
	public function sledge() {
		//if ( $this->is_relevant( 'user_post_count' ) )
		//	print_r( 'Woot' );
	}
	
	
	/**
	 * Query for conditions based on specific queries. 
	 */
	private function condition_query() {
		
		// Not sure if I'll need this.
		
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
				//'show_in_menu' => 'tools.php',
				'show_in_admin_bar' => false,
				'menu_position' => 80,
				// 'menu_icon' => URL,
				// TODO
				'capabilities' => array(
				// Cribbed from http://plugins.svn.wordpress.org/wp-help/tags/0.3/wp-help.php
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
					'name' => 'Award if&hellip;',
					//'desc' => 'field description (optional)',
					'id'   => $prefix . 'partone',
					'type' => 'select',
					'options' => array(
						array( 'name' => 'User post count', 'value' => 'user_post_count', ),
						array( 'name' => 'User comment count', 'value' => 'user_comment_count', ),
						// array( 'name' => 'User registration date', 'value' => 'user_registration_date', ),
						// array( 'name' => 'User ID', 'value' => 'user_id', ),
					)
				),
				array(
					'name' => '&hellip;is&hellip;',
					//'desc' => 'field description (optional)',
					'id'   => $prefix . 'parttwo',
					'type' => 'select',
					'options' => array(
						array( 'name' => 'equal to', 'value' => 'is_equal_to', ),
						array( 'name' => 'less than', 'value' => 'is_less_than', ),
						array( 'name' => 'greater than', 'value' => 'is_greater_than', ),
					)
				),
				array(
					'name' => '',
					//'desc' => 'field description (optional)',
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