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

	QUnit.module( 'ext.smw', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';

	/**
	 * Test initialization and accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'init', 8, function ( assert ) {

		assert.ok( smw instanceof Object, pass + 'the smw instance was accessible' );
		assert.equal( $.type( smw.log ), 'function', pass + '.log() was accessible' );
		assert.equal( $.type( smw.msg ), 'function', pass + '.msg() was accessible' );
		assert.equal( $.type( smw.settings ), 'function', pass + '.settings() was accessible' );
		assert.equal( $.type( smw.settings.get ), 'function', pass + '.settings.get() was accessible' );
		assert.equal( $.type( smw.version ), 'function', pass + '.version() was accessible' );
		assert.equal( $.type( smw.formats.getName ), 'function', pass + '.formats.getName() was accessible' );
		assert.equal( $.type( smw.formats.getList ), 'function', pass + '.formats.getList() was accessible' );

	} );

	/**
	 * Test settings function
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'settings', 4, function ( assert ) {

		assert.equal( $.type( smw.settings() ), 'object', pass + 'returned a settings object' );
		assert.equal( $.type( smw.settings.get( 'smwgQMaxLimit' ) ), 'number', pass + 'returned a value for a specific key (smwgQMaxLimit)' );
		assert.equal( smw.settings.get( 'lula' ), undefined, pass + 'returned undefined for an unknown key' );
		assert.equal( smw.settings.get(), undefined, pass + 'returned undefined for an empty key' );

	} );

	/**
	 * Test formats function
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'formats', 4, function ( assert ) {

		assert.equal( $.type( smw.formats.getList() ), 'object', pass + '.getList() returned an object' );
		assert.equal( $.type( smw.formats.getName( 'table' ) ), 'string', pass + 'returned a name for a specific format (table)' );
		assert.equal( smw.formats.getName( 'lula' ), undefined, pass + 'returned undefined for an unknown key' );
		assert.equal( smw.formats.getName(), undefined, pass + 'returned undefined for an empty key' );

	} );

	/**
	 * Test version function
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'version', 1, function ( assert ) {

		assert.equal( $.type( smw.version() ), 'string', pass + 'returned a version' );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );