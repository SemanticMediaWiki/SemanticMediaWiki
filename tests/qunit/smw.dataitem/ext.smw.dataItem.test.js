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

	QUnit.module( 'ext.smw.dataItem', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';
	var testString = '{\"query\":{\"result\":{\"printrequests\":[{\"label\":\"\",\"typeid\":\"_wpg\",\"mode\":2},{\"label\":\"Has test date\",\"typeid\":\"_dat\",\"mode\":1},{\"label\":\"Has test page\",\"typeid\":\"_wpg\",\"mode\":1},{\"label\":\"Has test string\",\"typeid\":\"_str\",\"mode\":1},{\"label\":\"Has test text\",\"typeid\":\"_txt\",\"mode\":1},{\"label\":\"Has test url\",\"typeid\":\"_uri\",\"mode\":1}],\"results\":{\"DataitemFactory\\/1\":{\"printouts\":{\"Has test date\":[\"947548800\"],\"Has test page\":[{\"fulltext\":\"File:FooBarfoo.png\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/File:FooBarfoo.png\",\"namespace\":6},{\"fulltext\":\"Foo page\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/Foo_page\",\"namespace\":0}],\"Has test string\":[\"Foobar string\"],\"Has test text\":[\"foo foo string\"],\"Has test url\":[\"http:\\/\\/localhost\\/mw\\/foobarfoo\"]},\"fulltext\":\"DataitemFactory\\/1\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/DataitemFactory\\/1\",\"namespace\":0},\"DataitemFactory\\/2\":{\"printouts\":{\"Has test date\":[\"1010707200\"],\"Has test page\":[{\"fulltext\":\"File:Foo.png\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/File:Foo.png\",\"namespace\":6},{\"fulltext\":\"Bar page\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/Bar_page\",\"namespace\":0}],\"Has test string\":[\"foo string\"],\"Has test text\":[\"fooBar string\"],\"Has test url\":[\"http:\\/\\/localhost\\/mw\\/foo\"]},\"fulltext\":\"DataitemFactory\\/2\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/DataitemFactory\\/2\",\"namespace\":0},\"DataitemFactory\\/3\":{\"printouts\":{\"Has test date\":[\"1010725200\"],\"Has test page\":[{\"fulltext\":\"Foo page\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/Foo_page\",\"namespace\":0},{\"fulltext\":\"File:FooBar.png\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/File:FooBar.png\",\"namespace\":6}],\"Has test string\":[\"bar string\"],\"Has test text\":[\"fooBar foo string\"],\"Has test url\":[\"http:\\/\\/localhost\\/index.php?title=foo\"]},\"fulltext\":\"DataitemFactory\\/3\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/DataitemFactory\\/3\",\"namespace\":0}},\"meta\":{\"hash\":\"c26caabe31af28817c3f87f26cfd0a3d\",\"count\":3,\"offset\":0}},\"ask\":{\"conditions\":\"[[Has test string::+]]\",\"parameters\":{\"limit\":50,\"offset\":0,\"format\":\"datatables\",\"link\":\"all\",\"headers\":\"show\",\"mainlabel\":\"\",\"intro\":\"\",\"outro\":\"\",\"searchlabel\":\"\\u2026 further results\",\"default\":\"\",\"class\":\"\",\"theme\":\"bootstrap\"},\"printouts\":[\"?Has test date\",\"?Has test page\",\"?Has test string\",\"?Has test text\",\"?Has test url\"]}},\"version\":\"0.1\"}';
	var subjectLessResult = '{\"query\":{\"result\":{\"printrequests\":[{\"label\":\"Modification date\",\"typeid\":\"_dat\",\"mode\":1}],\"results\":{\"Concepttest3\":{\"printouts\":{\"Modification date\":[\"1358906761\"]}},\"Concepttest4\":{\"printouts\":{\"Modification date\":[\"1358905485\"]}},\"Category:Concepts\":{\"printouts\":{\"Modification date\":[\"1358896550\"]}}},\"meta\":{\"hash\":\"8c727bd6aa52f47caa39bd0c1b93ee59\",\"count\":3,\"offset\":0}},\"ask\":{\"conditions\":\"[[Modification date::+]]\",\"parameters\":{\"limit\":3,\"offset\":0,\"format\":\"datatables\",\"link\":\"all\",\"headers\":\"show\",\"mainlabel\":\"-\",\"intro\":\"\",\"outro\":\"\",\"searchlabel\":\"\\u2026 further results\",\"default\":\"\",\"class\":\"\",\"theme\":\"bootstrap\"},\"printouts\":[\"?Modification date\"]}},\"version\":\"0.1\"}';
	var unknownType = '{\"query\":{\"result\":{\"printrequests\":[{\"label\":\"\",\"typeid\":\"_wpg\",\"mode\":2},{\"label\":\"Has description\",\"typeid\":\"_foo\",\"mode\":1}],\"results\":{\"File:IMG0027040123.jpg\":{\"printouts\":{\"Has description\":[\"Mauris pellentesque aliquet leo Nam nibh metus facilisi et sem laoreet. Netus ipsum montes et a neque in pulvinar nibh\",\"Facilisi et sem laoreet. Netus ipsum montes et a neque in pulvinar nibh\"]},\"fulltext\":\"File:IMG0027040123.jpg\",\"fullurl\":\"http:\\/\\/localhost\\/mw\\/index.php\\/File:IMG0027040123.jpg\",\"namespace\":6}},\"meta\":{\"hash\":\"a116c98703f48d231ec4c8eaee4038f8\",\"count\":1,\"offset\":0}},\"ask\":{\"conditions\":\"[[Has description::+]]\",\"parameters\":{\"limit\":1,\"offset\":0,\"link\":\"all\",\"headers\":\"show\",\"mainlabel\":\"\",\"intro\":\"\",\"outro\":\"\",\"searchlabel\":\"\\u2026 further results\",\"default\":\"\"},\"printouts\":[\"?Has description\"]}},\"version\":\"0.1\"}';

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', 1, function ( assert ) {

		var result = new smw.dataItem();
		assert.ok( result instanceof Object, pass + 'the smw.dataItem instance was accessible' );

	} );

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'comparison $.parseJSON() vs. smw.Api.parse()', 2, function ( assert ) {
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
	QUnit.test( 'smw.dataItem.property factory test', 5, function ( assert ) {

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
	QUnit.test( 'smw.dataItem.property subject less factory test', 3, function ( assert ) {

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
	QUnit.test( 'smw.dataItem.wikiPage subject factory test', 3, function ( assert ) {

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
	QUnit.test( 'smw.dataItem.wikiPage multiValue factory test', 4, function ( assert ) {

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
	QUnit.test( 'smw.dataItem.time factory', 4, function ( assert ) {

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
	QUnit.test( 'smw.dataItem.uri factory', 4, function ( assert ) {

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
	 * Test unknownType factory
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'unknown type factory', 3, function ( assert ) {

		// Testing indirect via the smw.Api otherwise the whole JSON parsing
		// needs to be copied
		var smwApi = new smw.Api();
		var result = smwApi.parse( unknownType );

		$.map ( result.query.result.results, function( printouts, head ) {
			$.map ( printouts, function( values ) {
				$.map ( values, function( value, index ) {
					if ( value instanceof smw.dataItem.property ){
						assert.equal( value.getLabel(), index , pass + 'the parser returned ' + index + ' for ' + head );
						$.each ( value, function( key, item ) {
							if(  key >= 0  ){
								assert.ok( $.type( item ) === 'string' , pass + 'the parser returned a string for an unknown type' );
							}
						} );
					}
				} );
			} );
		} );
	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );