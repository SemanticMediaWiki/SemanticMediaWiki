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

		// JS is loaded, now remove the "soft" disabled functionality
		$( "#ca-purge" ).removeClass( 'is-disabled' );

		// Observed on the chameleon skin
		$( "#ca-purge a" ).removeClass( 'is-disabled' );

		$( "#ca-purge a, .purge" ).on( 'click', function ( e ) {

			if ( $( this ).data( 'title' ) ) {
				var title = $( this ).data( 'title' );
			} else {
				var title = mw.config.get( 'wgPageName' );
			}

			var postArgs = { action: 'purge', titles: title };
			new mw.Api().post( postArgs ).then( function () {
				location.reload();
			}, function () {
				mw.notify( mw.msg( 'smw-purge-failed' ), { type: 'error' } );
			} );
			e.preventDefault();
		} );

	} );

}( jQuery, mediaWiki ) );
