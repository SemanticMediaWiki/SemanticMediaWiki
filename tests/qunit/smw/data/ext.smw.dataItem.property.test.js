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

	QUnit.module( 'ext.smw.dataItem.property', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataItem.property( 'Has test' );
		assert.ok( result instanceof Object, pass + 'the smw.dataItem.property instance was accessible' );

	} );

	/**
	 * Test getLabel
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getLabel', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataItem.property( 'Has test' );
		assert.equal( result.getLabel(), 'Has test', pass + 'a label was returned' );

	} );

	/**
	 * Test getHtml
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getHtml', function ( assert ) {
		assert.expect( 2 );

		var result = new smw.dataItem.property( 'Has type' );
		var href = mw.util.wikiGetlink( 'Property:');
		assert.equal( result.getHtml(), 'Has type', pass + 'a text label was returned' );
		assert.equal( result.getHtml( true ), '<a href=\"' + href + 'Has_type\">Has type</a>', pass + 'a href link was returned' );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );
