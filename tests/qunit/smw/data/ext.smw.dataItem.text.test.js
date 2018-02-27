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

	QUnit.module( 'ext.smw.dataItem.text', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', function ( assert ) {
		assert.expect( 4 );

		var result = new smw.dataItem.text( 'foo' );
		assert.ok( result instanceof Object, pass + 'the smw.dataItem.text instance was accessible' );

		assert.throws( function() {
			new smw.dataItem.text( {} );
		}, pass + 'an error was raised due to the wrong type' );

		assert.throws( function() {
			new smw.dataItem.text( [] );
		}, pass + 'an error was raised due to the wrong type' );

		assert.throws( function() {
			new smw.dataItem.text( 3 );
		}, pass + 'an error was raised due to the wrong type' );

	} );

	/**
	 * Test type
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getDIType', function ( assert ) {
		assert.expect( 2 );

		var result;

		result = new smw.dataItem.text( 'foo' );
		assert.equal( result.getDIType(), '_txt', pass + 'getDIType() returned _txt' );

		result = new smw.dataItem.text( 'bar', '_str' );
		assert.equal( result.getDIType(), '_str', pass + 'getDIType() returned _str' );

	} );

	/**
	 * Test getText
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getText', function ( assert ) {
		assert.expect( 2 );

		var result;

		var testString = 'Lorem ipsum dolor sit ...';

		result = new smw.dataItem.text( testString );
		assert.equal( result.getText(), testString, pass + 'getText() returned ' + testString );
		assert.equal( result.getString(), testString, pass + 'getString() returned ' + testString );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );
