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
	QUnit.test( 'toString sanity test', 11, function ( assert ) {
		var result;

		QUnit.raises( function() {
			new smw.query( '', '' ,'' ).toString();
		}, pass + 'an error was raised due to missing conditions' );

		QUnit.raises( function() {
			new smw.query( [], {} ,'' ).toString();
		}, pass + 'an error was raised due to missing conditions' );

		QUnit.raises( function() {
			new smw.query( [], [] , '[[Modification date::+]]' ).toString();
		}, pass + 'an error was raised due to parameters being a non object' );

		QUnit.raises( function() {
			new smw.query( '', [] , '[[Modification date::+]]' ).toString();
		}, pass + 'an error was raised due to parameters being a non object' );

		QUnit.raises( function() {
			new smw.query( '?Modification date', {'limit' : 10, 'offset': 0 } , '[[Modification date::+]]' ).toString();
		}, pass + 'an error was raised due to printouts weren\'t empty at first, contained values but those weren\'t of type array' );

		QUnit.raises( function() {
			new smw.query( ['?Modification date'], ['limit'], '[[Modification date::+]]' ).toString();
		}, pass + 'an error was raised due to parameters weren\'t empty at first, contained values but those weren\'t of type object' );

		result = new smw.query( '', '' , ['[[Modification date::+]]'] ).toString();
		assert.equal( result, '[[Modification date::+]]', pass + '.toString() returned a string' );

		result = new smw.query( [], {} , ['[[Modification date::+]]'] ).toString();
		assert.equal( result, '[[Modification date::+]]', pass + '.toString() returned a string' );

		result = new smw.query(
			'',
			{'limit' : 10, 'offset': 0 } ,
			'[[Modification date::+]]'
		).toString();
		assert.equal( result, '[[Modification date::+]]|limit=10|offset=0', pass + '(printouts = empty, parameters = object, conditions = array).toString() returned a string,' );

		result = new smw.query(
			['?Modification date'],
			{'limit' : 10, 'offset': 0 } ,
			'[[Modification date::+]]'
		).toString();
		assert.equal( result, '[[Modification date::+]]|?Modification date|limit=10|offset=0', pass + '(printouts = array, parameters = object, conditions = array).toString() returned a string,' );

		result = new smw.query(
			['?Modification date'],
			{'limit' : 10, 'offset': 0 } ,
			{ foo: '[[Modification date::+]]', bar: '[[Modification date::>2013-04-01]]' }
		).toString();
		assert.equal( result, '[[Modification date::+]][[Modification date::>2013-04-01]]|?Modification date|limit=10|offset=0', pass + '(printouts = array, parameters = object, conditions = object).toString() returned a string,' );

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
		QUnit.stop();
		smwApi.fetch( query.toString() )
		.done( function ( results ) {

			assert.ok( true, pass + 'the query returned with a positive server response' );
			assert.ok( results instanceof Object, pass + 'the query returned with a result object' );
			QUnit.start();
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


	/**
	 * Test getLink
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getLink', 5, function ( assert ) {
		var result, context;

		var smwApi = new smw.api();
		var queryObject = smwApi.parse( jsonString );

		result = new smw.query ( queryObject.printouts, queryObject.parameters, queryObject.conditions ).getLink();
		assert.equal( $.type( result ), 'string', pass + 'the query link returned was a string' );

		// DOM test
		context = $( '<div></div>', '#qunit-fixture' );
		context.append( result );
		assert.equal( context.find( 'a' ).attr( 'class' ), 'query-link', pass + 'DOM object returned class attribute "query-link"' );
		assert.ok( context.find( 'a' ).attr( 'href' ), pass + 'DOM object returned a href attribute' );

		// Caption text test
		context = $( '<div></div>', '#qunit-fixture' );
		queryObject.parameters.searchlabel = 'test';
		result = new smw.query ( queryObject.printouts, queryObject.parameters, queryObject.conditions ).getLink();
		context.append( result );
		assert.equal( context.find( 'a' ).text( ), 'test', pass + 'parameters.searchlabel is used to set the caption text' );

		context = $( '<div></div>', '#qunit-fixture' );
		result = new smw.query ( queryObject.printouts, queryObject.parameters, queryObject.conditions ).getLink( 'test 2' );
		context.append( result );
		assert.equal( context.find( 'a' ).text( ), 'test 2', pass + 'getLink() is used to set the caption text' );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );