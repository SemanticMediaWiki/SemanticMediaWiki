/**
 * This file is part of the Semantic MediaWiki QUnit test suite
 * @see https://semantic-mediawiki.org/wiki/QUnit
 *
 * @section LICENSE
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @since 1.9
 *
 * @file
 * @ignore
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests methods provided by ext.smw.util.tooltip.js
 * @ignore
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