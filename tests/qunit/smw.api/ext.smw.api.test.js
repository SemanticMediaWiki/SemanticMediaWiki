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
	QUnit.test( 'fetch', 4, function ( assert ) {

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
	 * Test fetch
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'comparison $.ajax vs. smw.Api.parse()', 2, function ( assert ) {

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
			assert.ok( results, 'Fetch ' + results.query.meta.count + ' items using smw.Api.parse() which took: ' + ( new Date().getTime() - startDate.getTime() ) + ' ms' );
			start();
		} );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );