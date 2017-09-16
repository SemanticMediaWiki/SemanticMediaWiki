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
	mw.loader.using( [ 'mediawiki.api', 'mediawiki.notify' ] ).then( function () {

		$( '.smw-postproc' ).each( function() {

			var ref = $( this ).data( 'ref' );

			if ( ref !== '' ) {
				mw.notify( mw.msg( 'smw-postproc-queryref' ), { type: 'info', autoHide: false } );

				var params = {
					'subject': $( this ).data( 'subject' ),
					'origin': 'api-postproc',
					'ref' : ref
				};

				var postArgs = {
					'action': 'smwtask',
					'task': 'update',
					'params': JSON.stringify( params )
				};

				new mw.Api().postWithToken( 'csrf', postArgs ).then( function ( data ) {
					location.reload( true );
				}, function () {
					// Do nothing
				} );
			};

		} );

	} );

}( jQuery, mediaWiki ) );
