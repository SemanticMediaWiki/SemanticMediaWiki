/**
 * Ported from tests/qunit/smw/data/ext.smw.dataItem.wikiPage.test.js (#7045).
 *
 * @licence GNU GPL v2 or later
 */
( function () {
	'use strict';

	QUnit.module( 'ext.smw.dataItem.wikiPage', QUnit.newMwEnvironment() );

	var testCases = [
		{
			test: [ '', '', '' ],
			expected: { name: null, text: null, uri: null, ns: 0, html: null, known: true }
		},
		{
			test: [ 'foo', '', '' ],
			expected: { name: 'foo', text: 'foo', uri: null, ns: 0, html: 'foo', known: true }
		},
		{
			test: [ 'bar', '', 6 ],
			expected: { name: 'bar', text: 'bar', uri: null, ns: 6, html: 'bar', known: true }
		},
		{
			test: [ 'bar', '', 0, true ],
			expected: { name: 'bar', text: 'bar', uri: null, ns: 0, html: 'bar', known: true }
		},
		{
			test: [ 'bar', '', 2, false ],
			expected: { name: 'bar', text: 'bar', uri: null, ns: 2, html: 'bar', known: false }
		},
		{
			test: [ 'fooBar', 'http://fooBar', 0, false ],
			expected: { name: 'fooBar', text: 'fooBar', uri: 'http://fooBar', ns: 0, html: '<a href="http://fooBar" class="new">fooBar</a>', known: false }
		},
		{
			test: [ 'fooBar#_9a0c8abb8ef729b5c7', 'http://fooBar#_9a0c8abb8ef729b5c7', 0, false ],
			expected: { name: 'fooBar#_9a0c8abb8ef729b5c7', text: 'fooBar', uri: 'http://fooBar#_9a0c8abb8ef729b5c7', ns: 0, html: '<a href="http://fooBar#_9a0c8abb8ef729b5c7" class="new">fooBar</a>', known: false }
		},
		{
			test: [ 'fooBar', 'http://fooBar', 0, true ],
			expected: { name: 'fooBar', text: 'fooBar', uri: 'http://fooBar', ns: 0, html: '<a href="http://fooBar">fooBar</a>', known: true }
		},
		{
			test: [ 'Foo#_QUERY9a665a578eb95c1', 'http://Foo#_QUERY9a665a578eb95c1', 0, true ],
			expected: { name: 'Foo#_QUERY9a665a578eb95c1', text: 'Foo', uri: 'http://Foo#_QUERY9a665a578eb95c1', ns: 0, html: '<a href="http://Foo#_QUERY9a665a578eb95c1">Foo</a>', known: true }
		},
		{
			test: [ 'Foo#_QUERY#9a665a578eb95c1', 'http://Foo#_QUERY#9a665a578eb95c1', 0, true ],
			expected: { name: 'Foo#_QUERY#9a665a578eb95c1', text: 'Foo', uri: 'http://Foo#_QUERY#9a665a578eb95c1', ns: 0, html: '<a href="http://Foo#_QUERY#9a665a578eb95c1">Foo</a>', known: true }
		}
	];

	QUnit.test( 'instance', function ( assert ) {
		var result = new smw.dataItem.wikiPage( 'foo', 'bar' );
		assert.ok( result instanceof Object, 'the smw.dataItem.wikiPage instance was accessible' );
	} );

	QUnit.test( 'getDIType', function ( assert ) {
		var result = new smw.dataItem.wikiPage( 'foo', 'bar' );
		assert.equal( result.getDIType(), '_wpg', 'returned _wpg' );
	} );

	QUnit.test( 'getPrefixedText', function ( assert ) {
		var result;

		testCases.forEach( function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[ 0 ], testCase.test[ 1 ], testCase.test[ 2 ] );
			assert.equal( result.getPrefixedText(), testCase.expected.name, 'returned ' + testCase.expected.name );
		} );
	} );

	QUnit.test( 'getText', function ( assert ) {
		var result;

		testCases.forEach( function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[ 0 ], testCase.test[ 1 ], testCase.test[ 2 ] );
			assert.equal( result.getText(), testCase.expected.text, 'returned ' + testCase.expected.text );
		} );
	} );

	QUnit.test( 'getUri', function ( assert ) {
		var result;

		testCases.forEach( function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[ 0 ], testCase.test[ 1 ], testCase.test[ 2 ] );
			assert.equal( result.getUri(), testCase.expected.uri, 'returned ' + testCase.expected.uri );
		} );
	} );

	QUnit.test( 'getNamespaceId', function ( assert ) {
		var result;

		testCases.forEach( function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[ 0 ], testCase.test[ 1 ], testCase.test[ 2 ] );
			assert.equal( result.getNamespaceId(), testCase.expected.ns, 'returned ' + testCase.expected.ns );
		} );
	} );

	QUnit.test( 'getTitle', function ( assert ) {
		var wikiPage = new smw.dataItem.wikiPage( 'File:foo', 'bar' );
		var title = new mw.Title( 'File:foo' );

		assert.ok( wikiPage.getTitle() instanceof mw.Title, '.getTitle() returned a Title instance' );
		assert.deepEqual( wikiPage.getTitle(), title, 'returned a Title instance' );
	} );

	QUnit.test( 'isKnown', function ( assert ) {
		var result;

		testCases.forEach( function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[ 0 ], testCase.test[ 1 ], testCase.test[ 2 ], testCase.test[ 3 ] );
			assert.equal( result.isKnown(), testCase.expected.known, 'returned ' + testCase.expected.known );
		} );
	} );

	QUnit.test( 'getHtml( false )', function ( assert ) {
		var result;

		testCases.forEach( function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[ 0 ], testCase.test[ 1 ], testCase.test[ 2 ] );
			assert.equal( result.getHtml( false ), testCase.expected.text, 'returned ' + testCase.expected.text );
		} );
	} );

	QUnit.test( 'getHtml( true )', function ( assert ) {
		var result;

		testCases.forEach( function ( testCase ) {
			result = new smw.dataItem.wikiPage( testCase.test[ 0 ], testCase.test[ 1 ], testCase.test[ 2 ], testCase.test[ 3 ] );
			assert.equal( result.getHtml( true ), testCase.expected.html, 'returned ' + testCase.expected.html );
		} );
	} );

}() );
