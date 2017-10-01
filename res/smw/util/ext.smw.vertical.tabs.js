/**
 * @see https://www.w3schools.com/howto/howto_js_vertical_tabs.asp
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

		// Re-enable (i.e. make fully visible) the nav menu after JS has been loaded
		$( '.smw-vtab-nav' ).each( function() {
			$( this ).css( 'opacity', '1' ).css( 'pointer-events', 'all' );
		} );

		// https://stackoverflow.com/questions/1634748/how-can-i-delete-a-query-string-parameter-in-javascript
		var removeURIKeyParam = function ( uri, key ) {
			return uri.replace( new RegExp('([\?&])' + key + '=[^&;]+[&;]?'), '');
		}

		var setLocation = function ( id, target ) {

			var i, tabcontent, tablinks;

			// Get all elements with class="smw-vtab-content" and hide them
			tabcontent = document.getElementsByClassName( "smw-vtab-content" );

			for ( i = 0; i < tabcontent.length; i++ ) {
				if ( tabcontent[i] ) {
					tabcontent[i].style.display = "none";
				};
			}

			// Get all elements with class="smw-vtab-link" and remove the class "active"
			tablinks = document.getElementsByClassName( "smw-vtab-link" );

			for ( i = 0; i < tablinks.length; i++ ) {
				if ( tablinks[i] ) {
					tablinks[i].className = tablinks[i].className.replace(" active", "" );
				};
			}

			// Show the current tab, and add an "active" class to the link that opened the tab
			document.getElementById( id ).style.display = 'inline';
			if ( target ) {
				target.className += " active";
			};
		}

		// A request was initiated with a href hash
		var id = window.location.hash;

		// @see HtmlVTabs::link
		if ( id !== '' && id.indexOf( 'tab-' ) > 0 ) {
			setLocation(
				id.replace( '#', '' ),
				document.getElementById( 'vtab-item-' + id.replace( '#', '' ) )
			);
		};

		// Iterate over available nav links onClick
		$( '.smw-vtab-link' ).on( "click", function( event ) {

			// Remove any &tab=... query parameter to avoid a contradictory history
			// when used in combination with #href hash
			if( window.location.search.indexOf( '&tab' ) > 0 ) {
				// https://developer.mozilla.org/en-US/docs/Web/API/History_API
				if( window.history != undefined && window.history.pushState != undefined ) {
					window.history.pushState(
						{},
						'',
						window.location.pathname + removeURIKeyParam( window.location.search, 'tab' )
					);
				}
			}

			// Set the href in case the click went on the button element and not
			// directly on the a element
			window.location.href = '#' + $( this ).data( 'id' );

			// Scroll the page to the top left to a void some jumping
			window.scrollTo( 0, 0 );

			setLocation(
				$( this ).data( 'id' ),
				event.currentTarget
			);

			event.preventDefault();
		} );

	} );

}( jQuery, mediaWiki ) );
