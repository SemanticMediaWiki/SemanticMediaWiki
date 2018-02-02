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

	QUnit.module( 'ext.smw.dataItem.number', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';

	// Data provider
	var testCases = [
		{ test: [ 1 ], expected: [ 1 ] },
		{ test: [ 0.0001 ], expected: [ 0.0001 ] },
		{ test: [ 1000 ], expected: [ 1000 ] }
	];

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', function ( assert ) {
		assert.expect( 2 );

		var result = new smw.dataItem.number( 3 );
		assert.ok( result instanceof Object, pass + 'the smw.dataItem.number instance was accessible' );

		assert.throws( function() {
			new smw.dataItem.number( 'foo' );
		}, pass + 'an error was raised due to wrong type' );

	} );

	/**
	 * Test type
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getDIType', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataItem.number( 3 );
		assert.equal( result.getDIType(), '_num', pass + 'returned _num' );

	} );

	/**
	 * Test getNumber
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getNumber', function ( assert ) {
		assert.expect( 3 );

		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.number( testCase.test[0] );
			assert.equal( result.getNumber(), testCase.expected[0] , pass + 'returned ' + testCase.expected[0]  );
		} );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );
