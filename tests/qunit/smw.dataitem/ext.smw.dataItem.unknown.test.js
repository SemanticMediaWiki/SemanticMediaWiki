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

	QUnit.module( 'ext.smw.dataItem.unknown', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';

	// Data provider
	var testCases = [
		{ test: [ '' ], expected: [ null, null ] } ,
		{ test: [ 'Foo', '_fooBar' ], expected: [ 'Foo', '_fooBar' ] }
	];

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', 1, function ( assert ) {

		var result = new smw.dataItem.unknown( 'foo', '_bar' );
		assert.ok( result instanceof Object, pass + 'the smw.dataItem.unknown instance was accessible' );

	} );

	/**
	 * Test type
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getDIType', 1, function ( assert ) {

		var result = new smw.dataItem.unknown( 'foo', '_bar' );
		assert.equal( result.getDIType(), '_bar', pass + 'returned "_bar"' );

	} );

	/**
	 * Test getValue
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getValue', 2, function ( assert ) {
		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.unknown( testCase.test[0], testCase.test[1] );
			assert.equal( result.getValue(), testCase.expected[0] , pass + 'returned ' + testCase.expected[0]  );
		} );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );