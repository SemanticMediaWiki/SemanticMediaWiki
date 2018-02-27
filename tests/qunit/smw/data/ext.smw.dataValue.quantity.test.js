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

	QUnit.module( 'ext.smw.dataValue.quantity', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';

	// Data provider
	var testCases = [
		{ test: { value: 1 }, expected: { value: 1 } },
		{ test: { value: 0.0001, unit: 'km²' }, expected:  { value: 0.0001, unit: 'km²' } },
		{ test: { value: 1000, unit: 'm²' }, expected: { value: 1000, unit: 'm²' } }
	];

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', function ( assert ) {
		assert.expect( 2 );

		var result = new smw.dataValue.quantity( 3 );
		assert.ok( result instanceof Object, pass + 'the smw.dataValue.quantity instance was accessible' );

		assert.throws( function() {
			new smw.dataValue.quantity( 'foo' );
		}, pass + 'an error was raised due to wrong type' );

	} );

	/**
	 * Test type
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getDIType', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataValue.quantity( 3 );
		assert.equal( result.getDIType(), '_qty', pass + 'returned _qty' );

	} );

	/**
	 * Test getValue
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getValue', function ( assert ) {
		assert.expect( 3 );

		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataValue.quantity( testCase.test.value, testCase.test.unit );
				assert.equal( result.getValue(), testCase.expected.value , pass + 'returned ' + testCase.expected.value );
		} );

	} );

	/**
	 * Test getUnit
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getUnit', function ( assert ) {
		assert.expect( 3 );

		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataValue.quantity( testCase.test.value, testCase.test.unit );
				assert.equal( result.getUnit(), testCase.expected.unit , pass + 'returned ' + testCase.expected.unit );
		} );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );
