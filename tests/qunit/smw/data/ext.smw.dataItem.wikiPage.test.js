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
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @since 1.9
 *
 * @file
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
( function ( $, mw, smw ) {
	'use strict';

	QUnit.module( 'ext.smw.dataItem.wikiPage', QUnit.newMwEnvironment() );

	// Data provider
	var testCases = [
		{
			test: [ '', '', '' ],
			expected: { 'name': null, 'text': null, 'uri': null, 'ns': 0, 'html': null, 'known': true }
		} ,
		{
			test: [ 'foo', '', '' ],
			expected: { 'name': 'foo', 'text': 'foo', 'uri': null, 'ns': 0, 'html': 'foo', 'known': true }
		},
		{
			test: [ 'bar', '', 6 ],
			expected: { 'name': 'bar', 'text': 'bar', 'uri': null, 'ns': 6, 'html': 'bar', 'known': true }
		},
		{
			test: [ 'bar', '', 0, true ],
			expected: { 'name': 'bar', 'text': 'bar', 'uri': null, 'ns': 0, 'html': 'bar', 'known': true }
		},
		{
			test: [ 'bar', '', 2, false ],
			expected: { 'name': 'bar', 'text': 'bar', 'uri': null, 'ns': 2, 'html': 'bar', 'known': false }
		},
		{
			test: [ 'fooBar', 'http://fooBar', 0, false ],
			expected: { 'name': 'fooBar', 'text': 'fooBar', 'uri': 'http://fooBar', 'ns': 0, 'html': '<a href=\"http://fooBar\" class=\"new\">fooBar</a>', 'known': false }
		},
		{
			test: [ 'fooBar#_9a0c8abb8ef729b5c7', 'http://fooBar#_9a0c8abb8ef729b5c7', 0, false ],
			expected: { 'name': 'fooBar#_9a0c8abb8ef729b5c7', 'text': 'fooBar', 'uri': 'http://fooBar#_9a0c8abb8ef729b5c7', 'ns': 0, 'html': '<a href=\"http://fooBar#_9a0c8abb8ef729b5c7\" class=\"new\">fooBar</a>', 'known': false }
		},
		{
			test: [ 'fooBar', 'http://fooBar', 0, true ],
			expected: { 'name': 'fooBar', 'text': 'fooBar', 'uri': 'http://fooBar', 'ns': 0, 'html': '<a href=\"http://fooBar\">fooBar</a>', 'known': true }
		},
		{
			test: [ 'Foo#_QUERY9a665a578eb95c1', 'http://Foo#_QUERY9a665a578eb95c1', 0, true ],
			expected: { 'name': 'Foo#_QUERY9a665a578eb95c1', 'text': 'Foo', 'uri': 'http://Foo#_QUERY9a665a578eb95c1', 'ns': 0, 'html': '<a href=\"http://Foo#_QUERY9a665a578eb95c1\">Foo</a>', 'known': true }
		},
		{
			test: [ 'Foo#_QUERY#9a665a578eb95c1', 'http://Foo#_QUERY#9a665a578eb95c1', 0, true ],
			expected: { 'name': 'Foo#_QUERY#9a665a578eb95c1', 'text': 'Foo', 'uri': 'http://Foo#_QUERY#9a665a578eb95c1', 'ns': 0, 'html': '<a href=\"http://Foo#_QUERY#9a665a578eb95c1\">Foo</a>', 'known': true }
		}
	];

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataItem.wikiPage( 'foo', 'bar' );
		assert.ok( result instanceof Object, 'the smw.dataItem.wikiPage instance was accessible' );

	} );

	/**
	 * Test type
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getDIType', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataItem.wikiPage( 'foo', 'bar' );
		assert.equal( result.getDIType(), '_wpg', 'returned _wpg' );

	} );

	/**
	 * Test getPrefixedText
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getPrefixedText', function ( assert ) {
		assert.expect( 10 );

		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[0], testCase.test[1], testCase.test[2] );
			assert.equal( result.getPrefixedText(), testCase.expected.name , 'returned ' + testCase.expected.name  );
		} );

	} );

	/**
	 * Test getText
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getText', function ( assert ) {
		assert.expect( 10 );

		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[0], testCase.test[1], testCase.test[2] );
			assert.equal( result.getText(), testCase.expected.text , 'returned ' + testCase.expected.text  );
		} );

	} );

	/**
	 * Test getUri
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getUri', function ( assert ) {
		assert.expect( 10 );

		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[0], testCase.test[1], testCase.test[2] );
			assert.equal( result.getUri(), testCase.expected.uri , 'returned ' + testCase.expected.uri  );
		} );

	} );

	/**
	 * Test getNamespaceId
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getNamespaceId', function ( assert ) {
		assert.expect( 10 );

		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[0], testCase.test[1], testCase.test[2] );
			assert.equal( result.getNamespaceId(), testCase.expected.ns , 'returned ' + testCase.expected.ns  );
		} );

	} );

	/**
	 * Test getTitle
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getTitle', function ( assert ) {
		assert.expect( 2 );

		var wikiPage = new smw.dataItem.wikiPage( 'File:foo', 'bar' );
		var title = new mw.Title( 'File:foo' );

		assert.ok( wikiPage.getTitle() instanceof mw.Title, '.getTitle() returned a Title instance' );
		assert.deepEqual( wikiPage.getTitle(), title, 'returned a Title instance' );

	} );

	/**
	 * Test isKnown
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'isKnown', function ( assert ) {
		assert.expect( 10 );

		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[0], testCase.test[1], testCase.test[2], testCase.test[3] );
			assert.equal( result.isKnown(), testCase.expected.known, 'returned ' + testCase.expected.known  );
		} );

	} );

	/**
	 * Test getHtml
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getHtml( false )', function ( assert ) {
		assert.expect( 10 );

		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[0], testCase.test[1], testCase.test[2] );
			assert.equal( result.getHtml( false ), testCase.expected.text, 'returned ' + testCase.expected.text  );
		} );

	} );

	/**
	 * Test getHtml
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getHtml( true )', function ( assert ) {
		assert.expect( 10 );

		var result;

		$.map( testCases, function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[0], testCase.test[1], testCase.test[2], testCase.test[3] );
			assert.equal( result.getHtml( true ), testCase.expected.html, 'returned ' + testCase.expected.html  );
		} );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );
