/**
 * Ported from tests/qunit/smw/ext.smw.test.js (#7045); the async/eachAsync
 * subtest is dropped here, see tests/qunit/README.md.
 *
 * @licence GNU GPL v2 or later
 */
( function () {
	'use strict';

	QUnit.module( 'ext.smw', QUnit.newMwEnvironment() );

	QUnit.test( 'init', function ( assert ) {
		assert.ok( smw instanceof Object, 'the smw instance was accessible' );

		assert.equal( $.type( smw.log ), 'function', '.log() was accessible' );
		assert.equal( $.type( smw.msg ), 'function', '.msg() was accessible' );
		assert.equal( $.type( smw.debug ), 'function', '.debug() was accessible' );
		assert.equal( $.type( smw.version ), 'function', '.version() was accessible' );

		assert.equal( $.type( smw.settings.getList ), 'function', '.settings.getList() was accessible' );
		assert.equal( $.type( smw.settings.get ), 'function', '.settings.get() was accessible' );

		assert.equal( $.type( smw.formats.getName ), 'function', '.formats.getName() was accessible' );
		assert.equal( $.type( smw.formats.getList ), 'function', '.formats.getList() was accessible' );

		assert.equal( $.type( smw.async.isEnabled ), 'function', '.async.isEnabled() was accessible' );
		assert.equal( $.type( smw.async.load ), 'function', '.async.load() was accessible' );

		assert.equal( $.type( smw.util.clean ), 'function', '.util.clean() was accessible' );
		assert.equal( $.type( smw.util.ucFirst ), 'function', '.util.ucFirst() was accessible' );
		assert.equal( $.type( smw.util.namespace ), 'object', '.util.namespace object was accessible' );
		assert.equal( $.type( smw.util.namespace.getList ), 'function', '.util.namespace.getList() was accessible' );
		assert.equal( $.type( smw.util.namespace.getId ), 'function', '.util.namespace.getId() was accessible' );
		assert.equal( $.type( smw.util.namespace.getName ), 'function', '.util.namespace.getName() was accessible' );
	} );

	QUnit.test( 'settings', function ( assert ) {
		assert.equal( $.type( smw.settings.getList() ), 'object', '.getList() returned a list of settings object' );
		assert.equal( $.type( smw.settings.get( 'smwgQMaxLimit' ) ), 'number', '.get( "smwgQMaxLimit" ) returned a value for the key' );
		assert.equal( smw.settings.get( 'lula' ), undefined, '.get( "lula" ) returned undefined for an unknown key' );
		assert.equal( smw.settings.get(), undefined, '.get() returned undefined for an empty key' );
	} );

	QUnit.test( 'util', function ( assert ) {
		assert.equal( smw.util.clean( ' Foo | ; : - < >_= () {} bar ' ), 'Foo_;_:_-__=_()_bar', '.clean() returned a cleaned string' );
		assert.equal( smw.util.clean( 'Foo | ; : - < >_= () {} bar' ), 'Foo_;_:_-__=_()_bar', '.clean() returned a cleaned string' );
		assert.equal( smw.util.ucFirst( 'foo Foo bar' ), 'Foo Foo bar', '.ucFirst() returned a capitalized string' );
	} );

	QUnit.test( 'util.namespace', function ( assert ) {
		assert.equal( $.type( smw.util.namespace.getList() ), 'object', '.getList() returned a list of namespaces' );

		assert.equal( $.type( smw.util.namespace.getId( 'property' ) ), 'number', '.getId( "property" ) returned a number' );
		assert.equal( $.type( smw.util.namespace.getId( 'Property' ) ), 'number', '.getId( "Property" ) returned a number' );
		assert.equal( $.type( smw.util.namespace.getId( 'concept' ) ), 'number', '.getId( "concept" ) returned a number' );
		assert.equal( smw.util.namespace.getId( 'lula' ), undefined, '.getId( "lula" )  returned undefined for an unknown key' );

		assert.equal( $.type( smw.util.namespace.getName( 'property' ) ), 'string', '.getName( "property" ) returned a string' );
		assert.equal( smw.util.namespace.getName( 'lula' ), undefined, '.getName( "lula" ) returned undefined for an unknown key' );
	} );

	QUnit.test( 'formats', function ( assert ) {
		assert.equal( $.type( smw.formats.getList() ), 'object', '.getList() returned an object' );
		assert.equal( $.type( smw.formats.getName( 'table' ) ), 'string', '.getName( "table" ) returned a string' );
		assert.equal( $.type( smw.formats.getName( ' table ' ) ), 'string', '.getName( " table " ) returned a string' );
		assert.equal( $.type( smw.formats.getName( 'Table' ) ), 'string', '.getName( "Table" ) returned a string' );

		assert.equal( smw.formats.getName( 'lula' ), undefined, '.getName() returned undefined for an unknown key' );
		assert.equal( smw.formats.getName( 123456 ), undefined, '.getName() returned undefined for an unknown key' );
		assert.equal( smw.formats.getName(), undefined, '.getName() returned undefined for an empty key' );
	} );

	QUnit.test( 'version', function ( assert ) {
		assert.equal( $.type( smw.version() ), 'string', '.version() returned a string' );
	} );

}() );
