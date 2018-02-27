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
 * Tests methods provided by ext.smw.data.js
 * @ignore
 */
( function ( $, mw, smw ) {
	'use strict';

	QUnit.module( 'ext.smw.Data', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';
	var testString = '{\"query\":{\"result\":{\"printrequests\":[{\"label\":\"\",\"typeid\":\"_wpg\",\"mode\":2},{\"label\":\"Has test date\",\"typeid\":\"_dat\",\"mode\":1},{\"label\":\"Has test page\",\"typeid\":\"_wpg\",\"mode\":1},{\"label\":\"Has test string\",\"typeid\":\"_str\",\"mode\":1},{\"label\":\"Has test text\",\"typeid\":\"_txt\",\"mode\":1},{\"label\":\"Has test url\",\"typeid\":\"_uri\",\"mode\":1}],\"results\":{\"DataitemFactory\\/1\":{\"printouts\":{\"Has test date\":[\"947548800\"],\"Has test page\":[{\"fulltext\":\"File:FooBarfoo.png\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/File:FooBarfoo.png\",\"namespace\":6},{\"fulltext\":\"Foo page\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/Foo_page\",\"namespace\":0}],\"Has test string\":[\"Foobar string\"],\"Has test text\":[\"foo foo string\"],\"Has test url\":[\"http:\\/\\/localhost\\/mw\\/foobarfoo\"]},\"fulltext\":\"DataitemFactory\\/1\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/DataitemFactory\\/1\",\"namespace\":0},\"DataitemFactory\\/2\":{\"printouts\":{\"Has test date\":[\"1010707200\"],\"Has test page\":[{\"fulltext\":\"File:Foo.png\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/File:Foo.png\",\"namespace\":6},{\"fulltext\":\"Bar page\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/Bar_page\",\"namespace\":0}],\"Has test string\":[\"foo string\"],\"Has test text\":[\"fooBar string\"],\"Has test url\":[\"http:\\/\\/localhost\\/mw\\/foo\"]},\"fulltext\":\"DataitemFactory\\/2\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/DataitemFactory\\/2\",\"namespace\":0},\"DataitemFactory\\/3\":{\"printouts\":{\"Has test date\":[\"1010725200\"],\"Has test page\":[{\"fulltext\":\"Foo page\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/Foo_page\",\"namespace\":0},{\"fulltext\":\"File:FooBar.png\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/File:FooBar.png\",\"namespace\":6}],\"Has test string\":[\"bar string\"],\"Has test text\":[\"fooBar foo string\"],\"Has test url\":[\"http:\\/\\/localhost\\/index.php?title=foo\"]},\"fulltext\":\"DataitemFactory\\/3\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/DataitemFactory\\/3\",\"namespace\":0}},\"meta\":{\"hash\":\"c26caabe31af28817c3f87f26cfd0a3d\",\"count\":3,\"offset\":0}},\"ask\":{\"conditions\":\"[[Has test string::+]]\",\"parameters\":{\"limit\":50,\"offset\":0,\"format\":\"datatables\",\"link\":\"all\",\"headers\":\"show\",\"mainlabel\":\"\",\"intro\":\"\",\"outro\":\"\",\"searchlabel\":\"\\u2026 further results\",\"default\":\"\",\"class\":\"\",\"theme\":\"bootstrap\"},\"printouts\":[\"?Has test date\",\"?Has test page\",\"?Has test string\",\"?Has test text\",\"?Has test url\"]}},\"version\":\"0.1\"}';
	var subjectLessResult = '{\"query\":{\"result\":{\"printrequests\":[{\"label\":\"Modification date\",\"typeid\":\"_dat\",\"mode\":1}],\"results\":{\"Concepttest3\":{\"printouts\":{\"Modification date\":[\"1358906761\"]}},\"Concepttest4\":{\"printouts\":{\"Modification date\":[\"1358905485\"]}},\"Category:Concepts\":{\"printouts\":{\"Modification date\":[\"1358896550\"]}}},\"meta\":{\"hash\":\"8c727bd6aa52f47caa39bd0c1b93ee59\",\"count\":3,\"offset\":0}},\"ask\":{\"conditions\":\"[[Modification date::+]]\",\"parameters\":{\"limit\":3,\"offset\":0,\"format\":\"datatables\",\"link\":\"all\",\"headers\":\"show\",\"mainlabel\":\"-\",\"intro\":\"\",\"outro\":\"\",\"searchlabel\":\"\\u2026 further results\",\"default\":\"\",\"class\":\"\",\"theme\":\"bootstrap\"},\"printouts\":[\"?Modification date\"]}},\"version\":\"0.1\"}';
	var unknownType = '{\"query\":{\"result\":{\"printrequests\":[{\"label\":\"\",\"typeid\":\"_wpg\",\"mode\":2},{\"label\":\"Has description\",\"typeid\":\"_foo\",\"mode\":1}],\"results\":{\"File:IMG0027040123.jpg\":{\"printouts\":{\"Has description\":[\"Mauris pellentesque aliquet leo Nam nibh metus facilisi et sem laoreet. Netus ipsum montes et a neque in pulvinar nibh\",\"Facilisi et sem laoreet. Netus ipsum montes et a neque in pulvinar nibh\"]},\"fulltext\":\"File:IMG0027040123.jpg\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/File:IMG0027040123.jpg\",\"namespace\":6}},\"meta\":{\"hash\":\"a116c98703f48d231ec4c8eaee4038f8\",\"count\":1,\"offset\":0}},\"ask\":{\"conditions\":\"[[Has description::+]]\",\"parameters\":{\"limit\":1,\"offset\":0,\"link\":\"all\",\"headers\":\"show\",\"mainlabel\":\"\",\"intro\":\"\",\"outro\":\"\",\"searchlabel\":\"\\u2026 further results\",\"default\":\"\"},\"printouts\":[\"?Has description\"]}},\"version\":\"0.1\"}';
	var numberType = '{\"query\":{\"result\":{\"printrequests\":[{\"label\":\"\",\"typeid\":\"_wpg\",\"mode\":2},{\"label\":\"Has number\",\"typeid\":\"_num\",\"mode\":1}],\"results\":{\"Image\\/1\":{\"printouts\":{\"Has number\":[1220,1320,99]},\"fulltext\":\"Image\\/1\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/Image\\/1\",\"namespace\":0,\"exists\":true}},\"meta\":{\"hash\":\"c2aa1dc43849ad148c9649e818eeb29b\",\"count\":1,\"offset\":0}},\"ask\":{\"conditions\":\"[[has number::+]]\",\"parameters\":{\"limit\":50,\"offset\":0,\"format\":\"datatables\",\"link\":\"all\",\"headers\":\"show\",\"mainlabel\":\"\",\"intro\":\"\",\"outro\":\"\",\"searchlabel\":\"\\u2026 further results\",\"default\":\"\",\"class\":\"\",\"theme\":\"bootstrap\"},\"printouts\":[\"?Has number\"]}},\"version\":\"0.1\"}';
	var quantityType = '{\"query\":{\"result\":{\"printrequests\":[{\"label\":\"\",\"typeid\":\"_wpg\",\"mode\":2},{\"label\":\"Area\",\"typeid\":\"_qty\",\"mode\":1}],\"results\":{\"Berlin\":{\"printouts\":{\"Area\":[{\"value\":891.85,\"unit\":\"km\\u00b2\"}]},\"fulltext\":\"Berlin\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/Berlin\",\"namespace\":0,\"exists\":true}},\"meta\":{\"hash\":\"f0f072f414c3e814c847aff1ba87dfb3\",\"count\":1,\"offset\":0}},\"ask\":{\"conditions\":\"[[Area::+]]\",\"parameters\":{\"limit\":50,\"offset\":0,\"format\":\"datatables\",\"link\":\"all\",\"headers\":\"show\",\"mainlabel\":\"\",\"intro\":\"\",\"outro\":\"\",\"searchlabel\":\"\\u2026 further results\",\"default\":\"\",\"class\":\"\",\"theme\":\"bootstrap\"},\"printouts\":[\"?Area\"]}},\"version\":\"0.2.5\"}';

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.Data();
		assert.ok( result instanceof Object, pass + 'the smw.dataItem instance was accessible' );

	} );

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'comparison $.parseJSON() vs. smw.Api.parse()', function ( assert ) {
		assert.expect( 2 );

		var result;
		var startDate;
		var smwApi = new smw.Api();

		startDate = new Date();
		result = $.parseJSON( testString );
		assert.ok( result, 'Using parseJSON took: ' + ( new Date().getTime() - startDate.getTime() ) + ' ms' );

		startDate = new Date();
		result = smwApi.parse( testString );
		assert.ok( result, 'using smw.Api.parse took: ' + ( new Date().getTime() - startDate.getTime() ) + ' ms' );

	} );

	/**
	 * Test smw.dataItem.property factory
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'smw.dataItem.property factory test', function ( assert ) {
		assert.expect( 5 );

		// Testing indirect via the smw.Api otherwise the whole JSON parsing
		// needs to be copied
		var smwApi = new smw.Api();
		var result = smwApi.parse( testString );

		$.map ( result.query.result.results['DataitemFactory/1'].printouts, function( value, key ) {
			if ( value instanceof smw.dataItem.property ){
				assert.equal( value.getLabel(), key , pass + 'the parser returned ' + key );
			}
		} );

	} );

	/**
	 * Test subject less
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'smw.dataItem.property subject less factory test', function ( assert ) {
		assert.expect( 3 );

		// Testing indirect via the smw.Api otherwise the whole JSON parsing
		// needs to be copied
		var smwApi = new smw.Api();
		var result = smwApi.parse( subjectLessResult );

		$.map ( result.query.result.results, function( printouts, head ) {
			$.map ( printouts, function( values ) {
				$.map ( values, function( value, key ) {
					if ( value instanceof smw.dataItem.property ){
						assert.equal( value.getLabel(), key , pass + 'the parser returned ' + key + ' for ' + head );
					}
				} );
			} );
		} );

	} );

	/**
	 * Test smw.dataItem.wikiPage factory
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'smw.dataItem.wikiPage subject factory test', function ( assert ) {
		assert.expect( 3 );

		// Testing indirect via the smw.Api otherwise the whole JSON parsing
		// needs to be copied
		var smwApi = new smw.Api();
		var result = smwApi.parse( testString );

		assert.ok( result.query.result.results['DataitemFactory/1'] instanceof smw.dataItem.wikiPage, pass + 'the parser returned a smw.dataItem.wikiPage object' );
		assert.equal( result.query.result.results['DataitemFactory/1'].getHtml(), 'DataitemFactory/1', pass + 'the object method .getHtml() was accessible' );
		assert.equal( result.query.result.results['DataitemFactory/1'].getUri(), 'http://localhost/mw/index.php/DataitemFactory/1', pass + 'the object method .getUri() was accessible' );

	} );

	/**
	 * Test smw.dataItem.wikiPage factory
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'smw.dataItem.wikiPage multiValue factory test', function ( assert ) {
		assert.expect( 4 );

		// Testing indirect via the smw.Api otherwise the whole JSON parsing
		// needs to be copied
		var smwApi = new smw.Api();
		var result = smwApi.parse( testString );

		var expectedMultiValue = ['File:FooBarfoo.png', 'Foo page'];

		$.map ( result.query.result.results['DataitemFactory/1'].printouts, function( values ) {
			if ( values instanceof smw.dataItem.property ){
				$.map ( values, function( value, key ) {
					if ( value instanceof smw.dataItem.wikiPage ){
						assert.ok( value instanceof smw.dataItem.wikiPage, pass + 'the parser returned a smw.dataItem.wikiPage object' );
						assert.equal( value.getHtml(), expectedMultiValue[key] , pass + 'the parser returned ' + expectedMultiValue[key] );
					}
				} );
			}
		} );

	} );

	/**
	 * Test smw.dataItem.time factory
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'smw.dataItem.time factory', function ( assert ) {
		assert.expect( 4 );

		// Use as helper to fetch language dep. month name
		var monthNames = [];
		$.map ( mw.config.get( 'wgMonthNames' ), function( value ) {
			if( value !== '' ){
				monthNames.push( value );
			}
		} );

		// Testing indirect via the smw.Api otherwise the whole JSON parsing
		// needs to be copied
		var smwApi = new smw.Api();
		var result = smwApi.parse( testString );

		$.map ( result.query.result.results['DataitemFactory/1'].printouts, function( values ) {
			if ( values instanceof smw.dataItem.property ){
				$.map ( values, function( value ) {
					if ( value instanceof smw.dataItem.time ){
						assert.ok( value instanceof smw.dataItem.time, pass + 'the parser returned a smw.dataItem.time object' );
						assert.equal( value.getMediaWikiDate(), '11 ' + monthNames[0] + ' 2000' , pass + 'the parser returned "11 '+ monthNames[0] +' 2000"' );
					}
				} );
			}
		} );

		$.map ( result.query.result.results['DataitemFactory/3'].printouts, function( values ) {
			if ( values instanceof smw.dataItem.property ){
				$.map ( values, function( value ) {
					if ( value instanceof smw.dataItem.time ){
						assert.ok( value instanceof smw.dataItem.time, pass + 'the parser returned a smw.dataItem.time object' );
						assert.equal( value.getMediaWikiDate(), '11 '+ monthNames[0] +' 2002 05:00:00' , pass + 'the parser returned "11 '+ monthNames[0] +' 2002 05:00:00"' );
					}
				} );
			}
		} );

	} );

	/**
	 * Test smw.dataItem.uri factory
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'smw.dataItem.uri factory', function ( assert ) {
		assert.expect( 4 );

		// Testing indirect via the smw.Api otherwise the whole JSON parsing
		// needs to be copied
		var smwApi = new smw.Api();
		var result = smwApi.parse( testString );

		$.map ( result.query.result.results['DataitemFactory/2'].printouts, function( values ) {
			if ( values instanceof smw.dataItem.property ){
				$.map ( values, function( value ) {
					if ( value instanceof smw.dataItem.uri ){
					assert.ok( value instanceof smw.dataItem.uri, pass + 'the parser returned a smw.dataItem.uri object' );
					assert.equal( value.getUri(), 'http://localhost/mw/foo' , pass + 'getUri() returned "http://localhost/mw/foo"' );
					assert.equal( value.getHtml( false ), 'http://localhost/mw/foo' , pass + 'getHtml( false ) returned "http://localhost/mw/foo"' );
					assert.equal( value.getHtml( true ), '<a href=\"http://localhost/mw/foo\">http://localhost/mw/foo</a>' , pass + 'getHtml( true ) returned a href element' );
						}
				} );
			}
		} );
	} );

	/**
	 * Test number factory
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'smw.dataItem.number factory', function ( assert ) {
		assert.expect( 3 );

		// Testing indirect via the smw.Api otherwise the whole JSON parsing
		// needs to be copied
		var smwApi = new smw.Api();
		var result = smwApi.parse( numberType );
		var expectedNumber = [1220,1320,99];
		var i=0;

		Object.values( result.query.result.results ).forEach( function ( result ) {
			Object.values( result.printouts ).forEach( function ( property ) {
				if ( property instanceof smw.dataItem.property ){
					$.map ( property, function( value ) {
						if ( value instanceof smw.dataItem.number ){
							assert.equal( value.getNumber(), expectedNumber[i] , pass + 'getNumber() returned ' + expectedNumber[i] );
							i++;
						}
					} );
				}
			} );
		} );
	} );

	/**
	 * Test quantity factory
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'smw.dataValue.quantity factory', function ( assert ) {
		assert.expect( 2 );

		// Testing indirect via the smw.Api otherwise the whole JSON parsing
		// needs to be copied
		var smwApi = new smw.Api();
		var result = smwApi.parse( quantityType );
		var expected = { value: 891.85, unit: 'km²' };

		Object.values( result.query.result.results ).forEach( function ( result ) {
			Object.values( result.printouts ).forEach( function ( property ) {
				if ( property instanceof smw.dataItem.property ){
					$.map ( property, function( value ) {
						if ( value instanceof smw.dataValue.quantity ){
							assert.equal( value.getValue(), expected.value , pass + 'getValue() returned ' + expected.value );
							assert.equal( value.getUnit(), expected.unit , pass + 'getUnit() returned ' + expected.unit );
						}
					} );
				}
			} );
		} );
	} );


	/**
	 * Test unlisted factory
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'smw.dataItem.unknown factory', function ( assert ) {
		assert.expect( 4 );

		// Testing indirect via the smw.Api otherwise the whole JSON parsing
		// needs to be copied
		var smwApi = new smw.Api();
		var result = smwApi.parse( unknownType );

		Object.values( result.query.result.results ).forEach( function ( result ) {
			Object.values( result.printouts ).forEach( function ( property ) {
				if ( property instanceof smw.dataItem.property ){
					$.map ( property, function( value ) {
						if ( value instanceof smw.dataItem.unknown ){
							assert.ok( value instanceof smw.dataItem.unknown, pass + 'the parser returned a smw.dataItem.unknown object' );
							assert.equal( value.getDIType(), '_foo' , pass + 'getDIType() returned an unknown type _foo' );
						}
					} );
				}
			} );
		} );
	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );
