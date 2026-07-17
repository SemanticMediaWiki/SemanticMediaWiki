/**
 * Ported from tests/qunit/smw/data/ext.smw.dataItem.property.test.js (#7045).
 *
 * getHtml's href assertion now goes through mw.util.getUrl(), the modern
 * replacement for mw.util.wikiGetlink (removed from MediaWiki core after
 * deprecation; see res/smw/data/ext.smw.dataItem.property.js).
 *
 * @licence GNU GPL v2 or later
 */
( function () {
	'use strict';

	QUnit.module( 'ext.smw.dataItem.property', QUnit.newMwEnvironment() );

	QUnit.test( 'instance', function ( assert ) {
		var result = new smw.dataItem.property( 'Has test' );
		assert.ok( result instanceof Object, 'the smw.dataItem.property instance was accessible' );
	} );

	QUnit.test( 'getLabel', function ( assert ) {
		var result = new smw.dataItem.property( 'Has test' );
		assert.equal( result.getLabel(), 'Has test', 'a label was returned' );
	} );

	QUnit.test( 'getHtml', function ( assert ) {
		var result = new smw.dataItem.property( 'Has type' );
		var href = mw.util.getUrl( 'Property:' + result.getLabel() );

		assert.equal( result.getHtml(), 'Has type', 'a text label was returned' );
		assert.equal( result.getHtml( true ), '<a href="' + href + '">Has type</a>', 'a href link was returned' );
	} );

}() );
