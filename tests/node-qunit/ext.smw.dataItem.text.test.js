/**
 * Ported from tests/qunit/smw/data/ext.smw.dataItem.text.test.js (#7045).
 *
 * @licence GNU GPL v2 or later
 */
( function () {
	'use strict';

	QUnit.module( 'ext.smw.dataItem.text', QUnit.newMwEnvironment() );

	QUnit.test( 'instance', function ( assert ) {
		assert.expect( 4 );

		var result = new smw.dataItem.text( 'foo' );
		assert.ok( result instanceof Object, 'the smw.dataItem.text instance was accessible' );

		assert.throws( function () {
			new smw.dataItem.text( {} );
		}, 'an error was raised due to the wrong type' );

		assert.throws( function () {
			new smw.dataItem.text( [] );
		}, 'an error was raised due to the wrong type' );

		assert.throws( function () {
			new smw.dataItem.text( 3 );
		}, 'an error was raised due to the wrong type' );
	} );

	QUnit.test( 'getDIType', function ( assert ) {
		assert.expect( 2 );

		var result;

		result = new smw.dataItem.text( 'foo' );
		assert.equal( result.getDIType(), '_txt', 'getDIType() returned _txt' );

		result = new smw.dataItem.text( 'bar', '_str' );
		assert.equal( result.getDIType(), '_str', 'getDIType() returned _str' );
	} );

	QUnit.test( 'getText', function ( assert ) {
		assert.expect( 2 );

		var result;
		var testString = 'Lorem ipsum dolor sit ...';

		result = new smw.dataItem.text( testString );
		assert.equal( result.getText(), testString, 'getText() returned ' + testString );
		assert.equal( result.getString(), testString, 'getString() returned ' + testString );
	} );

}() );
