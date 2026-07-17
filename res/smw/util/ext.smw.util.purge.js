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

( function ( $, mw ) {

	'use strict';

	mw.smw = mw.smw || {};

	// Ceiling on the total time spent auto-reloading via a `.page-purge`
	// element (#7044). Once exceeded, stop reloading and notify instead.
	var MAX_RETRY_MS = 120000;

	// Backoff sequence (ms) between successive auto-reloads; the last value
	// repeats for any further attempt.
	var BACKOFF_SEQUENCE = [ 1000, 3000, 7000, 15000, 30000 ];

	var purge = {
		/**
		 * @param {string} title
		 * @return {string}
		 */
		storageKey: function ( title ) {
			return 'mw-smw-purge-retry-' + title;
		},

		/**
		 * @param {string} title
		 * @return {Object} { attempts: number, startTime: number }
		 */
		getRetryState: function ( title ) {
			var raw = mw.storage.session.get( purge.storageKey( title ) );
			var state;

			try {
				state = raw ? JSON.parse( raw ) : null;
			} catch ( e ) {
				state = null;
			}

			if ( !state || typeof state.attempts !== 'number' || typeof state.startTime !== 'number' ) {
				state = { attempts: 0, startTime: Date.now() };
			}

			return state;
		},

		/**
		 * @param {string} title
		 * @param {Object} state
		 */
		setRetryState: function ( title, state ) {
			mw.storage.session.set( purge.storageKey( title ), JSON.stringify( state ) );
		},

		/**
		 * @param {string} title
		 */
		clearRetryState: function ( title ) {
			mw.storage.session.remove( purge.storageKey( title ) );
		},

		/**
		 * @param {number} attempts number of auto-reloads already performed
		 * @return {number} delay in ms before the next reload
		 */
		computeBackoff: function ( attempts ) {
			var index = Math.min( attempts, BACKOFF_SEQUENCE.length - 1 );
			return BACKOFF_SEQUENCE[ index ];
		},

		/**
		 * Decide, for an auto-triggered (`.page-purge`) purge, whether another
		 * reload attempt is still within the retry budget.
		 *
		 * @param {string} title
		 * @return {Object} { allowed: boolean, delay: number, state: Object }
		 */
		nextAttempt: function ( title ) {
			var state = purge.getRetryState( title );
			var elapsed = Date.now() - state.startTime;

			if ( elapsed >= MAX_RETRY_MS ) {
				return { allowed: false, delay: 0, state: state };
			}

			return { allowed: true, delay: purge.computeBackoff( state.attempts ), state: state };
		},

		/**
		 * Indirection point so tests can substitute a stub, since
		 * `window.location.reload` cannot be reassigned directly in all
		 * environments.
		 */
		reload: function () {
			location.reload();
		},

		/**
		 * @param {jQuery} context
		 * @param {boolean} isAutoTriggered whether this purge originates from
		 *  a `.page-purge` element rather than a user click
		 */
		run: function ( context, isAutoTriggered ) {

			var forcelinkupdate = false;
			var title = context.data( 'title' ) || mw.config.get( 'wgPageName' );

			if ( context.data( 'forcelinkupdate' ) ) {
				forcelinkupdate = context.data( 'forcelinkupdate' );
			}

			var attempt = isAutoTriggered ?
				purge.nextAttempt( title ) :
				{ allowed: true, delay: 0, state: null };

			if ( !attempt.allowed ) {
				purge.clearRetryState( title );
				mw.notify( mw.msg( 'smw-purge-update-dependencies-timeout' ), { type: 'info', autoHide: false } );
				return;
			}

			if ( context.data( 'msg' ) ) {
				mw.notify( mw.msg( context.data( 'msg' ) ), { type: 'info', autoHide: false } );
			}

			var postArgs = { action: 'purge', titles: title, forcelinkupdate: forcelinkupdate };

			new mw.Api().post( postArgs ).then( function () {
				if ( isAutoTriggered ) {
					purge.setRetryState( title, {
						attempts: attempt.state.attempts + 1,
						startTime: attempt.state.startTime
					} );

					setTimeout( function () {
						purge.reload();
					}, attempt.delay );
				} else {
					purge.reload();
				}
			}, function () {
				mw.notify( mw.msg( 'smw-purge-failed' ), { type: 'error' } );
			} );
		}
	};

	mw.smw.purge = purge;

	// JS is loaded, now remove the "soft" disabled functionality
	$( '#ca-purge' ).removeClass( 'is-disabled' );

	// Observed on the chameleon skin
	$( '#ca-purge a' ).removeClass( 'is-disabled' );

	$( '#ca-purge a, .purge' ).on( 'click', function ( e ) {
		purge.run( $( this ), false );
		e.preventDefault();
	} );

	$( '.page-purge' ).each( function () {
		purge.run( $( this ), true );
	} );

}( jQuery, mediaWiki ) );
