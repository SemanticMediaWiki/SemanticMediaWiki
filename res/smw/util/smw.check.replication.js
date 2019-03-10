/*!
 * This file is part of the Semantic MediaWiki Reload module
 * @see https://www.semantic-mediawiki.org/wiki/Help:Purge
 *
 * @since 3.0
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/*global jQuery, mediaWiki, smw */
/*jslint white: true */

( function( $, mw ) {

	'use strict';

	/**
	 * @since 3.0
	 */
	mw.loader.using( [ 'mediawiki.api', 'smw.tippy' ] ).then( function () {

		$( '.smw-es-replication' ).each( function() {

			var self = $( this );
			var api = new mw.Api();
			var subject = $( this ).data( 'subject' );

			if ( subject !== undefined && subject !== '' ) {

				var params = {
					'subject': subject,
					'dir': $( this ).data( 'dir' )
				};

				var postArgs = {
					'action': 'smwtask',
					'task': 'check-es-replication',
					'params': JSON.stringify( params )
				};

				api.postWithToken( 'csrf', postArgs ).then( function ( data ) {
					self.replaceWith( data.task.html );
					self.find( '.is-disabled' ).removeClass( 'is-disabled' );

					// Enable the `mw-indicator-mw-helplink` in case it was disabled
					if ( data.task.html === '' && document.getElementById( 'mw-indicator-mw-helplink' ) !== null ) {
						document.getElementById( 'mw-indicator-mw-helplink' ).style.display = 'inline-block';
					};
				} );
			}

		} );

	} );

}( jQuery, mediaWiki ) );
