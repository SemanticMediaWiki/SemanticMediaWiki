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

			var api = new mw.Api();
			var ref = $( this ).data( 'ref' );
			var query = $( this ).data( 'query' );

			if ( ref !== undefined && ref !== '' ) {
				mw.notify( mw.msg( 'smw-postproc-queryref' ), { type: 'info', autoHide: false } );

				var params = {
					'subject': $( this ).data( 'subject' ),
					'origin': 'api-postproc',
					'ref' : ref,
					'cache-key': $( this ).data( 'cache-key' )
				};

				var postArgs = {
					'action': 'smwtask',
					'task': 'update',
					'params': JSON.stringify( params )
				};

				api.postWithToken( 'csrf', postArgs ).then( function ( data ) {
					location.reload( true );
				} );
			} else if ( query !== undefined ) {

				var params = {
					'subject': $( this ).data( 'subject' ),
					'origin': 'api-postproc',
					'query' : query,
					'cache-key': $( this ).data( 'cache-key' )
				};

				var postArgs = {
					'action': 'smwtask',
					'task': 'check-query',
					'params': JSON.stringify( params )
				};

				api.postWithToken( 'csrf', postArgs ).then( function ( data ) {
					if ( data.task.hasOwnProperty( 'reload' ) ) {
						mw.notify( mw.msg( 'smw-postproc-queryref' ), { type: 'info', autoHide: false } );
						location.reload( true );
					};
				} );
			}

			var jobs = $( this ).data( 'jobs' );

			if ( jobs !== '' ) {

				var params = {
					'subject': $( this ).data( 'subject' ),
					'origin': 'api-postproc',
					'jobs' : jobs
				};

				var postArgs = {
					'action': 'smwtask',
					'task': 'run-joblist',
					'params': JSON.stringify( params )
				};

				api.postWithToken( 'csrf', postArgs );
			};

		} );

	} );

}( jQuery, mediaWiki ) );
