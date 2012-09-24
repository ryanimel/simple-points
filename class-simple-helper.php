<?php

/**
 * Simple Helper Class
 *
 * Used by the Simple Points, Simple Badges, and Simple Notifications 
 * plugins. It simple handles formatting and forwarding of dev input.
 */
Class RWI_Simple_Helper {
	
	/* 
	 * Static property to hold our singleton instance
	 * @var SimpleBadges
	 */
	static $instance = false;


	/*
	 * @return SimpleBadges
	*/
	public function __construct() {

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
	 * Format the information given it.
	 */
	public function check( $trigger, $user, $target ) {
		global $SimpleCondition;
		
		// Get only the relevant conditions	
		$conditions = $SimpleCondition->get_conditions( $trigger );

		foreach( $conditions as $condition ) {
			
			// Is this available to the current user?
			if ( $SimpleCondition->is_available( $condition->ID, $user ) ) {

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
	
}


Class RWI_Simple_Points_Helper extends RWI_Simple_Helper {
	
}


// Instantiate helper class
$SimplePointsHelper = new RWI_Simple_Points_Helper;