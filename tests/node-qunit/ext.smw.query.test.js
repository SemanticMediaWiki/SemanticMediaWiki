/**
 * Ported from tests/qunit/smw/query/ext.smw.query.test.js (#7045). The Ajax
 * response subtest stubs $.ajax instead of making a live network call, see
 * tests/node-qunit/ext.smw.api.test.js for the same pattern.
 *
 * @licence GNU GPL v2 or later
 */
( function () {
	'use strict';

	QUnit.module( 'ext.smw.Query', QUnit.newMwEnvironment() );

	var jsonString = '{"conditions":"[[Modification date::+]]","parameters":{"limit":10,"offset":0,"link":"all","headers":"show","mainlabel":"","intro":"","outro":"","searchlabel":"\\u2026 further results","default":"","class":""},"printouts":["?Modification date"]}';
	var sampleResponseJson = '{"query":{"result":{"printrequests":[],"results":{},"meta":{"hash":"abc","count":0,"offset":0}},"ask":{"conditions":"[[Modification date::+]]","parameters":{},"printouts":[]}},"version":"0.1"}';

	QUnit.test( 'instance', function ( assert ) {
		var result;

		result = new smw.api();
		assert.ok( result instanceof Object, 'the api instance was accessible' );

		result = new smw.query();
		assert.ok( result instanceof Object, 'the api.query instance was accessible' );
	} );

	QUnit.test( 'toString sanity test', function ( assert ) {
		var result;

		assert.throws( function () {
			new smw.query( '', '', '' ).toString();
		}, 'an error was raised due to missing conditions' );

		assert.throws( function () {
			new smw.query( [], {}, '' ).toString();
		}, 'an error was raised due to missing conditions' );

		assert.throws( function () {
			new smw.query( [], [], '[[Modification date::+]]' ).toString();
		}, 'an error was raised due to parameters being a non object' );

		assert.throws( function () {
			new smw.query( '', [], '[[Modification date::+]]' ).toString();
		}, 'an error was raised due to parameters being a non object' );

		assert.throws( function () {
			new smw.query( '?Modification date', { limit: 10, offset: 0 }, '[[Modification date::+]]' ).toString();
		}, 'an error was raised due to printouts weren\'t empty at first, contained values but those weren\'t of type array' );

		assert.throws( function () {
			new smw.query( [ '?Modification date' ], [ 'limit' ], '[[Modification date::+]]' ).toString();
		}, 'an error was raised due to parameters weren\'t empty at first, contained values but those weren\'t of type object' );

		result = new smw.query( '', '', [ '[[Modification date::+]]' ] ).toString();
		assert.equal( result, '[[Modification date::+]]', '.toString() returned a string' );

		result = new smw.query( [], {}, [ '[[Modification date::+]]' ] ).toString();
		assert.equal( result, '[[Modification date::+]]', '.toString() returned a string' );

		result = new smw.query(
			'',
			{ limit: 10, offset: 0 },
			'[[Modification date::+]]'
		).toString();
		assert.equal( result, '[[Modification date::+]]|limit=10|offset=0', '(printouts = empty, parameters = object, conditions = array).toString() returned a string,' );

		result = new smw.query(
			[ '?Modification date' ],
			{ limit: 10, offset: 0 },
			'[[Modification date::+]]'
		).toString();
		assert.equal( result, '[[Modification date::+]]|?Modification date|limit=10|offset=0', '(printouts = array, parameters = object, conditions = array).toString() returned a string,' );

		result = new smw.query(
			[ '?Modification date' ],
			{ limit: 10, offset: 0 },
			{ foo: '[[Modification date::+]]', bar: '[[Modification date::>2013-04-01]]' }
		).toString();
		assert.equal( result, '[[Modification date::+]][[Modification date::>2013-04-01]]|?Modification date|limit=10|offset=0', '(printouts = array, parameters = object, conditions = object).toString() returned a string,' );
	} );

	QUnit.test( 'toString Ajax response test', function ( assert ) {
		var done = assert.async();

		var ajaxStub = sinon.stub( $, 'ajax' ).callsFake( function ( options ) {
			var deferred = $.Deferred();
			deferred.resolve( options.converters[ 'text json' ]( sampleResponseJson ) );
			return deferred.promise();
		} );

		var smwApi = new smw.api();
		var queryObject = smwApi.parse( jsonString );

		var query = new smw.query( queryObject.printouts, queryObject.parameters, queryObject.conditions );

		assert.ok( $.type( query.toString() ) === 'string', 'the query is a string' );
		assert.ok( $.type( query.getQueryString() ) === 'string', 'the function alias returned a string' );

		smwApi.fetch( query.toString() )
			.done( function ( results ) {
				assert.ok( true, 'the query returned with a positive server response' );
				assert.ok( results instanceof Object, 'the query returned with a result object' );
			} )
			.always( function () {
				ajaxStub.restore();
				done();
			} );
	} );

	QUnit.test( 'getLimit', function ( assert ) {
		var smwApi = new smw.api();
		var queryObject = smwApi.parse( jsonString );

		var query = new smw.query( queryObject.printouts, queryObject.parameters, queryObject.conditions );
		assert.equal( query.getLimit(), 10, 'the query limit parameter returned 10' );
	} );

	QUnit.test( 'getLink', function ( assert ) {
		var result, context;

		var smwApi = new smw.api();
		var queryObject = smwApi.parse( jsonString );

		result = new smw.query( queryObject.printouts, queryObject.parameters, queryObject.conditions ).getLink();
		assert.equal( $.type( result ), 'string', 'the query link returned was a string' );

		context = $( '<div></div>' );
		context.append( result );
		assert.equal( context.find( 'a' ).attr( 'class' ), 'query-link', 'DOM object returned class attribute "query-link"' );
		assert.ok( context.find( 'a' ).attr( 'href' ), 'DOM object returned a href attribute' );

		context = $( '<div></div>' );
		queryObject.parameters.searchlabel = 'test';
		result = new smw.query( queryObject.printouts, queryObject.parameters, queryObject.conditions ).getLink();
		context.append( result );
		assert.equal( context.find( 'a' ).text(), 'test', 'parameters.searchlabel is used to set the caption text' );

		context = $( '<div></div>' );
		result = new smw.query( queryObject.printouts, queryObject.parameters, queryObject.conditions ).getLink( 'test 2' );
		context.append( result );
		assert.equal( context.find( 'a' ).text(), 'test 2', 'getLink() is used to set the caption text' );
	} );

}() );
