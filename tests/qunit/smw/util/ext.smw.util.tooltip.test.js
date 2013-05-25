/*!
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
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
( function ( $, mw, smw ) {
	'use strict';

	QUnit.module( 'ext.smw.util.tooltip', QUnit.newMwEnvironment() );

	/**
	 * Test initialization and accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', 1, function ( assert ) {
		var tooltip = new smw.util.tooltip();

		assert.ok( tooltip instanceof Object, 'smw.util.tooltip instance was accessible' );

	} );

	/**
	 * Test .show() function
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'show', 2, function ( assert ) {
		var tooltip = new smw.util.tooltip();
		var fixture = $( '#qunit-fixture' );

		assert.equal( $.type( tooltip.show ), 'function', '.show() was accessible' );

		tooltip.show( {
			context: fixture,
			content: 'Test',
			title: 'Test',
			button: false
		} );

		assert.ok( fixture.data( 'hasqtip' ), '.data( "hasqtip" ) was accessible' );

	} );

	/**
	 * Test .add() function
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'add', 3, function ( assert ) {
		var tooltip = new smw.util.tooltip();
		var fixture = $( '#qunit-fixture' );

		assert.equal( $.type( tooltip.add ), 'function', '.add() was accessible' );

		tooltip.add( {
			contextClass: 'test',
			contentClass: 'test-content',
			targetClass : 'test-target',
			context: fixture,
			content: 'Test 2',
			title: 'Test 2',
			type: 'info',
			button: true
		} );

		assert.ok( fixture.find( '.test' ), 'created context class' );
		assert.ok( fixture.data( 'hasqtip' ), '.data( "hasqtip" ) was accessible' );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );