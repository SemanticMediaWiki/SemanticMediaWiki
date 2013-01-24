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

	QUnit.module( 'ext.smw.util.tooltip', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';

	/**
	 * Test initialization and accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'init', 1, function ( assert ) {
		var tooltip;

		tooltip = new smw.util.tooltip();

		assert.ok( tooltip instanceof Object, pass + 'the smw.util.tooltip instance was accessible' );

	} );

	/**
	 * Test .show() function
	 *
	 * @since: 1.9
	 */
	QUnit.test( '.show()', 2, function ( assert ) {
		var tooltip;
		var fixture = $( '#qunit-fixture' );

		tooltip = new smw.util.tooltip();

		assert.equal( $.type( tooltip.show ), 'function', pass + '.show() was accessible' );

		tooltip.show( {
			context: fixture,
			content: 'Test',
			title: 'Test',
			button: false
		} );

		assert.ok( fixture.data( 'hasqtip' ), pass + '.data( "hasqtip" ) was accessible' );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );