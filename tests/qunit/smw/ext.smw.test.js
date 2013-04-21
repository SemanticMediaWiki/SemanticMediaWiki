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
	QUnit.test( 'init', 2, function ( assert ) {

		assert.ok( smw instanceof Object, pass + 'the smw instance was accessible' );
		assert.equal( $.type( smw.log ), 'function', pass + '.log() was accessible' );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );