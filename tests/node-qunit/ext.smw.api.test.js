/**
 * Ported from tests/qunit/smw/api/ext.smw.api.test.js (#7045).
 *
 * The original fetch() tests made live $.ajax calls against a real wiki's
 * api.php and timed out after 60s each in a sandboxed/offline environment
 * (one of the pre-existing failures surfaced by #7045). $.ajax is stubbed
 * here so fetch()'s caching/converter logic is exercised without a network
 * dependency; the "vs. $.ajax" comparison test (a 2013-era timing curiosity,
 * not a behavioural assertion) is dropped, see tests/qunit/README.md.
 *
 * @licence GNU GPL v2 or later
 */
( function () {
	'use strict';

	QUnit.module( 'ext.smw.Api', QUnit.newMwEnvironment() );

	var sampleResponseJson = '{"query":{"result":{"printrequests":[],"results":{},"meta":{"hash":"abc","count":0,"offset":0}},"ask":{"conditions":"[[Modification date::+]]","parameters":{},"printouts":[]}},"version":"0.1"}';

	QUnit.test( 'instance', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.api();
		assert.ok( result instanceof Object, 'the api instance was accessible' );
	} );

	QUnit.test( 'fetch()', function ( assert ) {
		assert.expect( 4 );

		var done = assert.async();
		var smwApi = new smw.api();

		assert.throws( function () {
			smwApi.fetch( '', '' );
		}, 'an error was raised (query was empty)' );

		assert.throws( function () {
			smwApi.fetch( { foo: 'bar' }, '' );
		}, 'an error was raised (query was an object)' );

		var ajaxStub = sinon.stub( $, 'ajax' ).callsFake( function ( options ) {
			var deferred = $.Deferred();
			deferred.resolve( options.converters[ 'text json' ]( sampleResponseJson ) );
			return deferred.promise();
		} );

		var query = '[[Modification date::+]]|?Modification date|limit=10|offset=0';

		smwApi.fetch( query )
			.done( function ( results ) {
				assert.ok( true, 'of a positive server response' );
				assert.ok( results instanceof Object, 'an object was returned' );
			} )
			.fail( function () {
				assert.ok( false, 'fetch() unexpectedly rejected' );
			} )
			.always( function () {
				ajaxStub.restore();
				done();
			} );
	} );

	QUnit.test( 'fetch() cache test', function ( assert ) {
		assert.expect( 4 );

		var smwApi = new smw.api();
		var queryString = '[[Modification date::+]]|?Modification date|?Modification date|?Modification date|limit=100';

		var ajaxStub = sinon.stub( $, 'ajax' ).callsFake( function ( options ) {
			var deferred = $.Deferred();
			deferred.resolve( options.converters[ 'text json' ]( sampleResponseJson ) );
			return deferred.promise();
		} );

		var undefCacheDone = assert.async();
		smwApi.fetch( queryString )
			.done( function ( results ) {
				assert.equal( results.isCached, false, 'caching is set "undefined" and results are not cached' );
				undefCacheDone();
			} );

		var falseCacheDone = assert.async();
		smwApi.fetch( queryString, false )
			.done( function ( results ) {
				assert.equal( results.isCached, false, 'caching is set "false" and results are not cached' );
				falseCacheDone();
			} );

		// Make sure the cache is initialized otherwise the asserts will fail
		// for the first test run
		var cacheDone = assert.async();
		smwApi.fetch( queryString, true )
			.done( function () {
				var firstCacheDone = assert.async();

				smwApi.fetch( queryString, 60 * 1000 )
					.done( function ( results ) {
						assert.equal( results.isCached, true, 'caching is set to "60 * 1000" and results are cached' );
						firstCacheDone();
					} );

				var otherCacheDone = assert.async();
				smwApi.fetch( queryString, true )
					.done( function ( results ) {
						assert.equal( results.isCached, true, 'caching is set "true" and results are cached' );
						otherCacheDone();
					} );

				ajaxStub.restore();
				cacheDone();
			} );
	} );

}() );
