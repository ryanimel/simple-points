jQuery(document).ready(function($) {
	
	// Set vars with the trigger row and the conditionals (that hide/show).
	var $trigger = "#simplepoints_condition_triggers tr:eq(1) input:eq(0)";
	var $conditionals = "#simplepoints_condition_triggers tr:eq(2), #simplepoints_condition_triggers tr:eq(3), #simplepoints_condition_triggers tr:eq(4)";
	
	// Show the conditionals if automatic badges is checked.
	if ( $( $trigger ).is( ":checked" ) ) {
		
		$( $conditionals ).css( "display", "table-row" );
		
	} else {
		
		// Hide the three conditionals to begin with.
		$( $conditionals ).hide();
		
	}
	
	// Pop options open if the checkbox is checked.
	$( $trigger ).click( function(){

		$( $conditionals ).toggle();
		
	} );
	
});