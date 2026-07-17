/**
 * Ported from tests/qunit/smw/data/ext.smw.dataItem.number.test.js (#7045).
 *
 * @licence GNU GPL v2 or later
 */
( function () {
	'use strict';

	QUnit.module( 'ext.smw.dataItem.number', QUnit.newMwEnvironment() );

	var testCases = [
		{ test: [ 1 ], expected: [ 1 ] },
		{ test: [ 0.0001 ], expected: [ 0.0001 ] },
		{ test: [ 1000 ], expected: [ 1000 ] }
	];

	QUnit.test( 'instance', function ( assert ) {
		assert.expect( 2 );

		var result = new smw.dataItem.number( 3 );
		assert.ok( result instanceof Object, 'the smw.dataItem.number instance was accessible' );

		assert.throws( function () {
			new smw.dataItem.number( 'foo' );
		}, 'an error was raised due to wrong type' );
	} );

	QUnit.test( 'getDIType', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataItem.number( 3 );
		assert.equal( result.getDIType(), '_num', 'returned _num' );
	} );

	QUnit.test( 'getNumber', function ( assert ) {
		assert.expect( 3 );

		var result;

		testCases.forEach( function ( testCase ) {
			result = new smw.dataItem.number( testCase.test[ 0 ] );
			assert.equal( result.getNumber(), testCase.expected[ 0 ], 'returned ' + testCase.expected[ 0 ] );
		} );
	} );

}() );
