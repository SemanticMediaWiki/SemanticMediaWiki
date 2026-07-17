/**
 * Ported from tests/qunit/smw/data/ext.smw.dataValue.quantity.test.js (#7045).
 *
 * @licence GNU GPL v2 or later
 */
( function () {
	'use strict';

	QUnit.module( 'ext.smw.dataValue.quantity', QUnit.newMwEnvironment() );

	var testCases = [
		{ test: { value: 1 }, expected: { value: 1 } },
		{ test: { value: 0.0001, unit: 'km²' }, expected: { value: 0.0001, unit: 'km²' } },
		{ test: { value: 1000, unit: 'm²' }, expected: { value: 1000, unit: 'm²' } }
	];

	QUnit.test( 'instance', function ( assert ) {
		var result = new smw.dataValue.quantity( 3 );
		assert.ok( result instanceof Object, 'the smw.dataValue.quantity instance was accessible' );

		assert.throws( function () {
			new smw.dataValue.quantity( 'foo' );
		}, 'an error was raised due to wrong type' );
	} );

	QUnit.test( 'getDIType', function ( assert ) {
		var result = new smw.dataValue.quantity( 3 );
		assert.equal( result.getDIType(), '_qty', 'returned _qty' );
	} );

	QUnit.test( 'getValue', function ( assert ) {
		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataValue.quantity( testCase.test.value, testCase.test.unit );
			assert.equal( result.getValue(), testCase.expected.value, 'returned ' + testCase.expected.value );
		} );
	} );

	QUnit.test( 'getUnit', function ( assert ) {
		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataValue.quantity( testCase.test.value, testCase.test.unit );
			assert.equal( result.getUnit(), testCase.expected.unit, 'returned ' + testCase.expected.unit );
		} );
	} );

}() );
