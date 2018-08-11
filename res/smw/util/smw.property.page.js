/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */

/*global jQuery, mediaWiki, mw */
( function ( $, mw ) {

	'use strict';

	$( document ).ready( function() {

		$( '.smw-property-page-info' ).removeClass( 'is-disabled' );

		$( '.smw-property-page-info' ).each( function() {

			var context = $( this );

			var params = {
				'search': $( this ).data( 'label' ),
				'limit' : 1,
				'strict': true,
				'usageCount': true
			};

			var postArgs = {
				'action': 'smwbrowse',
				'browse': 'property',
				'params': JSON.stringify( params )
			};

			new mw.Api().post( postArgs ).then( function ( data ) {
				var text = '<table class="smw-personal-table"><tbody>' + data.query[context.data( 'key' )].usageCount + '</tbody></table>';

				smw.Factory.newTooltip().show ( {
					context: context,
					title: mw.msg( 'smw-personal-jobqueue-watchlist' ),
					type: 'persitent',
					content: text
				} );
			}, function () {
				// Do nothing
			} );
		} );

	} );

}( jQuery, mediaWiki ) );
