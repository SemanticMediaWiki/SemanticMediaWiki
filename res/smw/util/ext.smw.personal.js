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
					text = text + '<li style="font-size:80%;">'+ '[&nbsp;<span class="">' + watchlist[prop] + '</span>&nbsp;]&nbsp;' + prop +'</li>';
				}
			}

			if ( text !== '' ) {

				if ( document.documentElement.dir === "rtl" ) {
					var line = '<div style="border-top: 1px solid #ebebeb;margin-top: 10px;margin-bottom: 8px;margin-right: -10px;width: 259px;"></div>';
				} else{
					var line = '<div style="border-top: 1px solid #ebebeb;margin-top: 10px;margin-bottom: 8px;margin-left: -10px;width: 259px;"></div>';
				}

				text = '<p style="font-size:12px;">' + mw.msg( 'smw-personal-jobqueue-watchlist-explain' ) + '</p>' + line + '<ul>' + text + '</ul>';

				var tooltip = smw.Factory.newTooltip();
				tooltip.show ( {
					context: $( this ),
					title: mw.msg( 'smw-personal-jobqueue-watchlist' ),
					type: 'inline',
					content: text,
					maxWidth: 280
				} );
			};
		} );

	} );

}( jQuery, mediaWiki ) );
