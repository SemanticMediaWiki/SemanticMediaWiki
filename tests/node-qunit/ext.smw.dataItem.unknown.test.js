/**
 * Ported from tests/qunit/smw/data/ext.smw.dataItem.unknown.test.js (#7045).
 *
 * @licence GNU GPL v2 or later
 */
( function () {
	'use strict';

	QUnit.module( 'ext.smw.dataItem.unknown', QUnit.newMwEnvironment() );

	var testCases = [
		{ test: [ '' ], expected: [ null, null ] },
		{ test: [ 'Foo', '_fooBar' ], expected: [ 'Foo', '_fooBar' ] }
	];

	QUnit.test( 'instance', function ( assert ) {
		var result = new smw.dataItem.unknown( 'foo', '_bar' );
		assert.ok( result instanceof Object, 'the smw.dataItem.unknown instance was accessible' );
	} );

	QUnit.test( 'getDIType', function ( assert ) {
		var result = new smw.dataItem.unknown( 'foo', '_bar' );
		assert.equal( result.getDIType(), '_bar', 'returned "_bar"' );
	} );

	QUnit.test( 'getValue', function ( assert ) {
		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.unknown( testCase.test[ 0 ], testCase.test[ 1 ] );
			assert.equal( result.getValue(), testCase.expected[ 0 ], 'returned ' + testCase.expected[ 0 ] );
		} );
	} );

}() );
