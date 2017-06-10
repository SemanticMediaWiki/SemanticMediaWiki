/*!
 * This file is part of the Semantic MediaWiki Purge module
 * @see https://www.semantic-mediawiki.org/wiki/Help:Purge
 *
 * @since 2.5
 * @revision 0.0.1
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author samwilson, mwjames
 */

/*global jQuery, mediaWiki, smw */
/*jslint white: true */

( function( $, mw ) {

	'use strict';

	mw.loader.using( [ 'mediawiki.api', 'mediawiki.notify' ] ).then( function () {

		$( "#ca-purge a" ).on( 'click', function ( e ) {
			var postArgs = { action: 'purge', titles: mw.config.get( 'wgPageName' ) };
			new mw.Api().post( postArgs ).then( function () {
				location.reload();
			}, function () {
				mw.notify( mw.msg( 'smw-purge-failed' ), { type: 'error' } );
			} );
			e.preventDefault();
		} );

	} );

}( jQuery, mediaWiki ) );
