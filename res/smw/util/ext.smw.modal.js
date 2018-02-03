/**
 * @see https://www.w3schools.com/howto/howto_css_modals.asp
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */

/*global jQuery, mediaWiki, mw */
( function ( $, mw ) {

	'use strict';

	$( document ).ready( function() {

		$( '.smw-modal-link' ).removeClass( 'is-disabled' );

		// Iterate over available nav links onClick
		$( '.smw-modal-link' ).on( "click", function( event ) {

			var that = this;

			// Avoid visual disruption on the parameter list (uses a fading field
			// display with the help of an ::after element) and override the element
			// for the time the modal window is displayed
			var parameterList = $( '.options-parameter-list' );
			parameterList.removeClass( 'options-parameter-list' );
			parameterList.addClass( 'options-parameter-list-plain' );

			// Find the content ID
			var id = $( this ).data( 'id' );

			// Get the modal
			var modal = document.getElementById( id );

			// When the user clicks the button, open the modal
			modal.style.display = "block";

			$( '#' + id + ' .smw-modal-close' ).on( "click", function( event ) {
				modal.style.display = "none";
				parameterList.removeClass( 'options-parameter-list-plain' );
				parameterList.addClass( 'options-parameter-list' );
			} );

			// When the user clicks anywhere outside of the modal, close it
			window.onclick = function( e ) {

				var isClickInside = that.contains( e.target );

				if ( e.target == modal || ( isClickInside === false && modal.contains( e.target ) === false ) ) {
					modal.style.display = "none";
					parameterList.removeClass( 'options-parameter-list-plain' );
					parameterList.addClass( 'options-parameter-list' );
				}
			}

			event.preventDefault();
		} );

	} );

}( jQuery, mediaWiki ) );
