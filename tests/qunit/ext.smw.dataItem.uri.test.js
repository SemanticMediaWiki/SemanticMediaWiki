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

	QUnit.module( 'ext.smw.dataItem.uri', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';

	// Data provider
	var testCases = [
		{ test: [ '' ], expected: [ null, null ] } ,
		{ test: [ 'http://fooBar/test' ], expected: [ 'http://fooBar/test', '<a href=\"http://fooBar/test\">http://fooBar/test</a>' ] }
	];

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', 1, function ( assert ) {

		var result = new smw.dataItem.uri( 'http://foo.com/test/' );
		assert.ok( result instanceof Object, pass + 'the smw.dataItem.uri instance was accessible' );

	} );

	/**
	 * Test type
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getDIType', 1, function ( assert ) {

		var result = new smw.dataItem.uri( 'http://foo.com/test/' );
		assert.equal( result.getDIType(), '_uri', pass + 'returned _uri' );

	} );

	/**
	 * Test getUri
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getUri', 2, function ( assert ) {
		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.uri( testCase.test[0] );
			assert.equal( result.getUri(), testCase.expected[0] , pass + 'returned ' + testCase.expected[0]  );
		} );

	} );

	/**
	 * Test getHtml
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getHtml', 4, function ( assert ) {
		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.uri( testCase.test[0] );
			assert.equal( result.getHtml(), testCase.expected[0] , pass + 'returned ' + testCase.expected[0]  );
		} );

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.uri( testCase.test[0] );
			assert.equal( result.getHtml( true ), testCase.expected[1] , pass + 'returned ' + testCase.expected[1]  );
		} );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );