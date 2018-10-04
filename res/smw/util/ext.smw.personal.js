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

		$( '.smw-personal-jobqueue-watchlist' ).removeClass( 'is-disabled' );

		// Iterate over available nav links onClick
		$( '.smw-personal-jobqueue-watchlist' ).each( function() {

			var watchlist = mw.config.get( 'smwgJobQueueWatchlist' );
			var text = '';

			for ( var prop in watchlist ) {
				if ( watchlist.hasOwnProperty( prop ) ) {
					text = text + '<tr><td>' + prop + '</td><td>&nbsp;</td><td><span class="item-count active">' + watchlist[prop] + '</span></td></tr>';
				}
			}

			if ( text !== '' ) {
				text = '<table class="smw-personal-table"><tbody>' + text + '</tbody></table>';

				var tooltip = smw.Factory.newTooltip();
				tooltip.show ( {
					context: $( this ),
					title: mw.msg( 'smw-personal-jobqueue-watchlist' ),
					type: 'inline',
					content: text
				} );
			};
		} );

	} );

}( jQuery, mediaWiki ) );
