/**
 * @license GNU GPL v2+
 * @since 0.3
 *
 * @author mwjames
 */

/*global jQuery */
/*jslint white: true */

( function( $ ) {

	'use strict';

	/**
	 * @since 0.3
	 */
	$( document ).ready( function() {

		// `data-onoi-clipboard-field` defines the field which holds the text value
		new Clipboard( '.clipboard', {
		    text: function( trigger ) {
		        return trigger.getAttribute( trigger.getAttribute( 'data-onoi-clipboard-field' ) );
		    }
		} );

	} );

}( jQuery ) );
