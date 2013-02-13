/**
 * QUnit tests
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2 or later
 * @author mwjames
 */
( function ( $, mw, smw ) {
	'use strict';

	QUnit.module( 'ext.smw.Api', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', 1, function ( assert ) {

		var result = new smw.api();
		assert.ok( result instanceof Object, pass + 'the api instance was accessible' );

	} );

	/**
	 * Test fetch
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'fetch()', 4, function ( assert ) {

		var smwApi = new smw.api();

		raises( function() { smwApi.fetch( '' , '' ); }, pass + 'an error was raised (query was empty)' );
		raises( function() { smwApi.fetch( {'foo': 'bar' } , '' ); }, pass + 'an error was raised (query was an object)' );

		// Ajax
		var query = '[[Modification date::+]]|?Modification date|limit=10|offset=0';
		stop();
		smwApi.fetch( query )
		.done( function ( results ) {

			assert.ok( true, pass + 'of an positive server response' );
			assert.ok( results instanceof Object, pass + 'an object was returned' );
			start();
		} )

	} );

	/**
	 * Test caching
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'fetch() cache test', 4, function ( assert ) {

		var smwApi = new smw.api();

		// Ajax
		var queryString = '[[Modification date::+]]|?Modification date|?Modification date|?Modification date|limit=100';

		stop();
		smwApi.fetch( queryString )
		.done( function ( results ) {
			assert.equal( results.isCached, false , pass + ' caching is set "undefined" and results are not cached' );
			start();
		} );

		stop();
		smwApi.fetch( queryString, false )
		.done( function ( results ) {
			assert.equal( results.isCached , false , pass + ' caching is set "false" and results are not cached' );
			start();
		} );

		// Make sure the cache is initialized otherwise the asserts will fail
		// for the first test run
		stop();
		smwApi.fetch( queryString, true )
		.done( function ( results ) {

			stop();
			smwApi.fetch( queryString, 60 * 1000 )
			.done( function ( results ) {
				assert.equal( results.isCached , true , pass + ' caching is set to "60 * 1000" and results are cached' );
				start();
			} );

			stop();
			smwApi.fetch( queryString, true )
			.done( function ( results ) {
				assert.equal( results.isCached , true , pass + ' caching is set "true" and results are cached' );
				start();
			} );

			start();
		} );

	} );

	/**
	 * Test fetch vs. a normal $.ajax call
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'fetch() vs. $.ajax', 3, function ( assert ) {

		var smwApi = new smw.api();
		var startDate;

		// Ajax
		var queryString = '[[Modification date::+]]|?Modification date|?Modification date|?Modification date|limit=100';

		startDate = new Date();

		stop();
		$.ajax( {
			url: mw.util.wikiScript( 'api' ),
			dataType: 'json',
			data: {
				'action': 'ask',
				'format': 'json',
				'query' : queryString
				}
			} )
			.done( function ( results ) {
				assert.ok( results, 'Fetch ' + results.query.meta.count + ' items using $.ajax which took: ' + ( new Date().getTime() - startDate.getTime() ) + ' ms' );
				start();
				startDate = new Date();
			} );

		stop();
		smwApi.fetch( queryString )
		.done( function ( results ) {
			assert.ok( results, 'Fetch ' + results.query.meta.count + ' items using smw.Api.fetch() which took: ' + ( new Date().getTime() - startDate.getTime() ) + ' ms' );
			start();
		} );

		stop();
		smwApi.fetch( queryString, true )
		.done( function ( results ) {
			assert.ok( results, 'Fetch ' + results.query.meta.count + ' items using smw.Api.fetch() which were cached and took: ' + ( new Date().getTime() - startDate.getTime() ) + ' ms' );
			start();
		} );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );