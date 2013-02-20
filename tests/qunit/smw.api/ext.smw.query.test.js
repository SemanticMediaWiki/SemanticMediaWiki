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

	QUnit.module( 'ext.smw.Query', QUnit.newMwEnvironment() );
	var jsonString = '{\"conditions\":\"[[Modification date::+]]\",\"parameters\":{\"limit\":10,\"offset\":0,\"link\":\"all\",\"headers\":\"show\",\"mainlabel\":\"\",\"intro\":\"\",\"outro\":\"\",\"searchlabel\":\"\\u2026 further results\",\"default\":\"\",\"class\":\"\"},\"printouts\":[\"?Modification date\"]}';

	var pass = 'Passes because ';

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', 2, function ( assert ) {
		var result;

		result = new smw.api();
		assert.ok( result instanceof Object, pass + 'the api instance was accessible' );

		result = new smw.query();
		assert.ok( result instanceof Object, pass + 'the api.query instance was accessible' );

	} );

	/**
	 * Test toString sanity
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'toString sanity test', 10, function ( assert ) {
		var result;

		raises( function() {
			new smw.query( '', '' ,'' ).toString();
		}, pass + 'an error was raised due to missing conditions' );

		raises( function() {
			new smw.query( [], {} ,'' ).toString();
		}, pass + 'an error was raised due to missing conditions' );

		raises( function() {
			new smw.query( [], [] , '[[Modification date::+]]' ).toString();
		}, pass + 'an error was raised due to parameters being a non object' );

		raises( function() {
			new smw.query( '', [] , '[[Modification date::+]]' ).toString();
		}, pass + 'an error was raised due to parameters being a non object' );

		raises( function() {
			new smw.query( '?Modification date', {'limit' : 10, 'offset': 0 } , '[[Modification date::+]]' ).toString();
		}, pass + 'an error was raised due to printouts weren\'t empty at first, contained values but those weren\'t of type array' );

		raises( function() {
			new smw.query( ['?Modification date'], ['limit'], '[[Modification date::+]]' ).toString();
		}, pass + 'an error was raised due to parameters weren\'t empty at first, contained values but those weren\'t of type object' );

		result = new smw.query( '', '' , ['[[Modification date::+]]'] ).toString();
		assert.equal( result, '[[Modification date::+]]', pass + '.toString() returned a string' );

		result = new smw.query( [], {} , ['[[Modification date::+]]'] ).toString();
		assert.equal( result, '[[Modification date::+]]', pass + '.toString() returned a string' );

		result = new smw.query( '', {'limit' : 10, 'offset': 0 } , '[[Modification date::+]]' ).toString();
		assert.equal( result, '[[Modification date::+]]|limit=10|offset=0', pass + '.toString() returned a string' );

		result = new smw.query( ['?Modification date'], {'limit' : 10, 'offset': 0 } , '[[Modification date::+]]' ).toString();
		assert.equal( result, '[[Modification date::+]]|?Modification date|limit=10|offset=0', pass + '.toString() returned a string' );

	} );

	/**
	 * Test toString
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'toString Ajax response test', 4, function ( assert ) {

		var smwApi = new smw.api();
		var queryObject = smwApi.parse( jsonString );

		var query = new smw.query ( queryObject.printouts, queryObject.parameters, queryObject.conditions );

		assert.ok( $.type( query.toString() ) === 'string', pass + 'the query is a string' );
		assert.ok( $.type( query.getQueryString() ) === 'string', pass + 'the function alias returned a string' );

		// Ajax
		stop();
		smwApi.fetch( query.toString() )
		.done( function ( results ) {

			assert.ok( true, pass + 'the query returned with a positive server response' );
			assert.ok( results instanceof Object, pass + 'the query returned with a result object' );
			start();
		} );

	} );

	/**
	 * Test getLimit
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getLimit', 1, function ( assert ) {

		var smwApi = new smw.api();
		var queryObject = smwApi.parse( jsonString );

		var query = new smw.query ( queryObject.printouts, queryObject.parameters, queryObject.conditions );
		assert.equal( query.getLimit(), 10, pass + 'the query limit parameter returned 10' );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );