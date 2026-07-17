/**
 * QUnit coverage for ext.smw.util.purge.js (#7044).
 *
 * @licence GNU GPL v2 or later
 */
( function () {
	'use strict';

	QUnit.module( 'ext.smw.util.purge', QUnit.newMwEnvironment( {
		afterEach: function () {
			mw.smw.purge.clearRetryState( 'Foo' );
		}
	} ) );

	QUnit.test( 'storageKey builds a title-scoped key', function ( assert ) {
		assert.strictEqual(
			mw.smw.purge.storageKey( 'Foo' ),
			'mw-smw-purge-retry-Foo'
		);
	} );

	QUnit.test( 'getRetryState returns a fresh state when nothing is stored', function ( assert ) {
		var state = mw.smw.purge.getRetryState( 'Foo' );

		assert.strictEqual( state.attempts, 0, 'no attempts recorded yet' );
		assert.strictEqual( typeof state.startTime, 'number', 'startTime is set' );
	} );

	QUnit.test( 'getRetryState returns a fresh state on corrupt storage', function ( assert ) {
		mw.storage.session.set( mw.smw.purge.storageKey( 'Foo' ), 'not-json' );

		var state = mw.smw.purge.getRetryState( 'Foo' );

		assert.strictEqual( state.attempts, 0 );
	} );

	QUnit.test( 'setRetryState persists attempts and startTime', function ( assert ) {
		mw.smw.purge.setRetryState( 'Foo', { attempts: 2, startTime: 12345 } );

		var state = mw.smw.purge.getRetryState( 'Foo' );

		assert.strictEqual( state.attempts, 2 );
		assert.strictEqual( state.startTime, 12345 );
	} );

	QUnit.test( 'clearRetryState removes the stored state', function ( assert ) {
		mw.smw.purge.setRetryState( 'Foo', { attempts: 2, startTime: 12345 } );
		mw.smw.purge.clearRetryState( 'Foo' );

		var state = mw.smw.purge.getRetryState( 'Foo' );

		assert.strictEqual( state.attempts, 0, 'state was reset, not merely re-read' );
	} );

	QUnit.test( 'computeBackoff increases with attempts and then plateaus', function ( assert ) {
		assert.strictEqual( mw.smw.purge.computeBackoff( 0 ), 1000 );
		assert.strictEqual( mw.smw.purge.computeBackoff( 1 ), 3000 );
		assert.strictEqual( mw.smw.purge.computeBackoff( 2 ), 7000 );

		var last = mw.smw.purge.computeBackoff( 4 );
		assert.strictEqual( mw.smw.purge.computeBackoff( 100 ), last, 'plateaus at the last configured delay' );
	} );

	QUnit.test( 'nextAttempt allows a reload within the retry budget', function ( assert ) {
		mw.smw.purge.setRetryState( 'Foo', { attempts: 1, startTime: Date.now() } );

		var attempt = mw.smw.purge.nextAttempt( 'Foo' );

		assert.strictEqual( attempt.allowed, true );
		assert.strictEqual( attempt.delay, mw.smw.purge.computeBackoff( 1 ) );
	} );

	QUnit.test( 'nextAttempt denies a reload once the retry budget is exceeded', function ( assert ) {
		// startTime far enough in the past to exceed the fixed retry ceiling.
		mw.smw.purge.setRetryState( 'Foo', { attempts: 5, startTime: Date.now() - 121000 } );

		var attempt = mw.smw.purge.nextAttempt( 'Foo' );

		assert.strictEqual( attempt.allowed, false );
	} );

	QUnit.test( 'run() on a click-triggered purge reloads immediately on success', function ( assert ) {
		var done = assert.async();
		var reloadCalled = false;
		var originalReload = mw.smw.purge.reload;
		var originalApi = mw.Api;

		mw.smw.purge.reload = function () {
			reloadCalled = true;
		};

		mw.Api = function () {};
		mw.Api.prototype.post = function () {
			return $.Deferred().resolve( {} ).promise();
		};

		var $context = $( '<a>' ).attr( 'href', '#' );

		mw.smw.purge.run( $context, false );

		setTimeout( function () {
			assert.strictEqual( reloadCalled, true, 'reload was called without delay' );

			mw.smw.purge.reload = originalReload;
			mw.Api = originalApi;
			done();
		}, 0 );
	} );

	QUnit.test( 'run() on a click-triggered purge notifies on failure and does not reload', function ( assert ) {
		var done = assert.async();
		var reloadCalled = false;
		var originalReload = mw.smw.purge.reload;
		var originalApi = mw.Api;

		mw.smw.purge.reload = function () {
			reloadCalled = true;
		};

		mw.Api = function () {};
		mw.Api.prototype.post = function () {
			return $.Deferred().reject().promise();
		};

		var $context = $( '<a>' ).attr( 'href', '#' );

		mw.smw.purge.run( $context, false );

		setTimeout( function () {
			assert.strictEqual( reloadCalled, false, 'no reload on a failed purge request' );

			mw.smw.purge.reload = originalReload;
			mw.Api = originalApi;
			done();
		}, 0 );
	} );

	QUnit.test( 'run() auto-triggered (.page-purge) delays the reload using the backoff', function ( assert ) {
		var done = assert.async();
		var reloadCalled = false;
		var originalReload = mw.smw.purge.reload;
		var originalApi = mw.Api;
		var originalSetTimeout = window.setTimeout;
		var capturedDelay = null;

		mw.smw.purge.reload = function () {
			reloadCalled = true;
		};

		window.setTimeout = function ( fn, delay ) {
			capturedDelay = delay;
			return originalSetTimeout( fn, 0 );
		};

		mw.Api = function () {};
		mw.Api.prototype.post = function () {
			return $.Deferred().resolve( {} ).promise();
		};

		var $context = $( '<div>' ).addClass( 'page-purge' ).data( 'title', 'Foo' );

		mw.smw.purge.run( $context, true );

		setTimeout( function () {
			assert.strictEqual( capturedDelay, 1000, 'first auto-retry uses the initial backoff delay' );
			assert.strictEqual( reloadCalled, true, 'reload still happens after the delay' );

			var state = mw.smw.purge.getRetryState( 'Foo' );
			assert.strictEqual( state.attempts, 1, 'attempt counter was incremented' );

			mw.smw.purge.reload = originalReload;
			mw.Api = originalApi;
			window.setTimeout = originalSetTimeout;
			done();
		}, 10 );
	} );

	QUnit.test( 'run() auto-triggered stops reloading once the retry budget is exceeded', function ( assert ) {
		var reloadCalled = false;
		var notifyCalled = false;
		var originalReload = mw.smw.purge.reload;
		var originalNotify = mw.notify;

		mw.smw.purge.reload = function () {
			reloadCalled = true;
		};

		mw.notify = function ( msg, opt ) {
			if ( opt && opt.type === 'info' ) {
				notifyCalled = true;
			}
		};

		mw.smw.purge.setRetryState( 'Foo', { attempts: 5, startTime: Date.now() - 121000 } );

		var $context = $( '<div>' ).addClass( 'page-purge' ).data( 'title', 'Foo' );

		mw.smw.purge.run( $context, true );

		assert.strictEqual( reloadCalled, false, 'no further reload once the ceiling is exceeded' );
		assert.strictEqual( notifyCalled, true, 'a low-key notice is shown instead' );

		var state = mw.smw.purge.getRetryState( 'Foo' );
		assert.strictEqual( state.attempts, 0, 'retry state was reset' );

		mw.smw.purge.reload = originalReload;
		mw.notify = originalNotify;
	} );

}() );
