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

	QUnit.module( 'ext.smw.dataItem.wikiPage', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';

	// Data provider
	var testCases = [
		{ test: [ '', '', '' ], expected: [ null, null, 0 , null] } ,
		{ test: [ 'foo', '', '' ], expected: [ 'foo', null, 0, 'foo' ] },
		{ test: [ 'bar', '', 6 ], expected: [ 'bar', null, 6, 'bar'] },
		{ test: [ 'fooBar', 'http://fooBar', 0 ], expected: [ 'fooBar', 'http://fooBar', 0, '<a href=\"http://fooBar\">fooBar</a>' ] }
	];

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', 1, function ( assert ) {

		var result = new smw.dataItem.wikiPage( 'foo', 'bar' );
		assert.ok( result instanceof Object, pass + 'the smw.dataItem.wikiPage instance was accessible' );

	} );

	/**
	 * Test type
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getDIType', 1, function ( assert ) {

		var result = new smw.dataItem.wikiPage( 'foo', 'bar' );
		assert.equal( result.getDIType(), '_wpg', pass + 'returned _wpg' );

	} );

	/**
	 * Test getName
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getName', 4, function ( assert ) {
		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[0], testCase.test[1], testCase.test[2] );
			assert.equal( result.getName(), testCase.expected[0] , pass + 'returned ' + testCase.expected[0]  );
		} );

	} );

	/**
	 * Test getUri
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getUri', 4, function ( assert ) {
		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[0], testCase.test[1], testCase.test[2] );
			assert.equal( result.getUri(), testCase.expected[1] , pass + 'returned ' + testCase.expected[1]  );
		} );

	} );

	/**
	 * Test getNamespace
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getNamespace', 4, function ( assert ) {
		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[0], testCase.test[1], testCase.test[2] );
			assert.equal( result.getNamespace(), testCase.expected[2] , pass + 'returned ' + testCase.expected[2]  );
		} );

	} );

	/**
	 * Test getTitle
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getTitle', 1, function ( assert ) {

		var wikiPage = new smw.dataItem.wikiPage( 'File:foo', 'bar' );
		var title = new mw.Title( 'File:foo' );

		assert.deepEqual( wikiPage.getTitle(), title ,pass + 'returned a Title instance' );

	} );

	/**
	 * Test isKnown
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'isKnown', 1, function ( assert ) {

		var wikiPage = new smw.dataItem.wikiPage( 'File:foo', 'bar' );

		assert.assertTrue( wikiPage.isKnown(), pass + 'the page is known' );

	} );

	/**
	 * Test getHtml
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getHtml', 8, function ( assert ) {
		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[0], testCase.test[1], testCase.test[2] );
			assert.equal( result.getHtml(), testCase.expected[0] , pass + 'returned ' + testCase.expected[0]  );
		} );

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[0], testCase.test[1], testCase.test[2] );
			assert.equal( result.getHtml( true ), testCase.expected[3] , pass + 'returned ' + testCase.expected[3]  );
		} );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );