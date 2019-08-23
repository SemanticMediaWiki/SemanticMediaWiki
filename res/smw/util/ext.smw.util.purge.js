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

	var purge = function( context ) {

		var forcelinkupdate = false;

		if ( context.data( 'title' ) ) {
			var title = context.data( 'title' );
		} else {
			var title = mw.config.get( 'wgPageName' );
		}

		if ( context.data( 'msg' ) ) {
			mw.notify( mw.msg( context.data( 'msg' ) ), { type: 'info', autoHide: false } );
		};

		if ( context.data( 'forcelinkupdate' ) ) {
			forcelinkupdate = context.data( 'forcelinkupdate' );
		};

		var postArgs = { action: 'purge', titles: title, forcelinkupdate: forcelinkupdate };

		new mw.Api().post( postArgs ).then( function () {
			location.reload();
		}, function () {
			mw.notify( mw.msg( 'smw-purge-failed' ), { type: 'error' } );
		} );
	}

	mw.loader.using( [ 'mediawiki.api', 'mediawiki.notify' ] ).then( function () {

		// JS is loaded, now remove the "soft" disabled functionality
		$( "#ca-purge" ).removeClass( 'is-disabled' );

		// Observed on the chameleon skin
		$( "#ca-purge a" ).removeClass( 'is-disabled' );

		$( "#ca-purge a, .purge" ).on( 'click', function ( e ) {
			purge( $( this ) );
			e.preventDefault();
		} );

		$( ".page-purge" ).each( function () {
			purge( $( this ) );
		} );

	} );

}( jQuery, mediaWiki ) );
