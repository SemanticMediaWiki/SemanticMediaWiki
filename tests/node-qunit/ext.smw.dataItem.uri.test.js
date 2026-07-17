/**
 * Ported from tests/qunit/smw/data/ext.smw.dataItem.uri.test.js (#7045).
 *
 * @licence GNU GPL v2 or later
 */
( function () {
	'use strict';

	QUnit.module( 'ext.smw.dataItem.uri', QUnit.newMwEnvironment() );

	var testCases = [
		{ test: [ '' ], expected: [ null, null ] },
		{ test: [ 'http://fooBar/test' ], expected: [ 'http://fooBar/test', '<a href="http://fooBar/test">http://fooBar/test</a>' ] }
	];

	QUnit.test( 'instance', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataItem.uri( 'http://foo.com/test/' );
		assert.ok( result instanceof Object, 'the smw.dataItem.uri instance was accessible' );
	} );

	QUnit.test( 'getDIType', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataItem.uri( 'http://foo.com/test/' );
		assert.equal( result.getDIType(), '_uri', 'returned _uri' );
	} );

	QUnit.test( 'getUri', function ( assert ) {
		assert.expect( 2 );

		var result;

		testCases.forEach( function ( testCase ) {
			result = new smw.dataItem.uri( testCase.test[ 0 ] );
			assert.equal( result.getUri(), testCase.expected[ 0 ], 'returned ' + testCase.expected[ 0 ] );
		} );
	} );

	QUnit.test( 'getHtml', function ( assert ) {
		assert.expect( 4 );

		var result;

		testCases.forEach( function ( testCase ) {
			result = new smw.dataItem.uri( testCase.test[ 0 ] );
			assert.equal( result.getHtml(), testCase.expected[ 0 ], 'returned ' + testCase.expected[ 0 ] );
		} );

		testCases.forEach( function ( testCase ) {
			result = new smw.dataItem.uri( testCase.test[ 0 ] );
			assert.equal( result.getHtml( true ), testCase.expected[ 1 ], 'returned ' + testCase.expected[ 1 ] );
		} );
	} );

}() );
