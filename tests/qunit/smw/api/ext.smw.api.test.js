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
 * Tests methods provided by ext.smw.api.js
 * @ignore
 */
( function ( $, mw, smw ) {
	'use strict';

	QUnit.module( 'ext.smw.Api', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 * @ignore
	 */
	QUnit.test( 'instance', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.api();
		assert.ok( result instanceof Object, pass + 'the api instance was accessible' );

	} );

	/**
	 * Test fetch
	 *
	 * @since: 1.9
	 * @ignore
	 */
	QUnit.test( 'fetch()', function ( assert ) {
		assert.expect( 4 );

		var done = assert.async();

		var smwApi = new smw.api();

		assert.throws( function() { smwApi.fetch( '' , '' ); }, pass + 'an error was raised (query was empty)' );
		assert.throws( function() { smwApi.fetch( {'foo': 'bar' } , '' ); }, pass + 'an error was raised (query was an object)' );

		// Ajax
		var query = '[[Modification date::+]]|?Modification date|limit=10|offset=0';

		smwApi.fetch( query )
		.done( function ( results ) {

			assert.ok( true, pass + 'of an positive server response' );
			assert.ok( results instanceof Object, pass + 'an object was returned' );

			done();
		} );

	} );

	/**
	 * Test caching
	 *
	 * @since: 1.9
	 * @ignore
	 */
	QUnit.test( 'fetch() cache test', function ( assert ) {
		assert.expect( 4 );

		var smwApi = new smw.api();

		// Ajax
		var queryString = '[[Modification date::+]]|?Modification date|?Modification date|?Modification date|limit=100';
		var undefCacheDone = assert.async();

		smwApi.fetch( queryString )
		.done( function ( results ) {
			assert.equal( results.isCached, false , pass + ' caching is set "undefined" and results are not cached' );
			undefCacheDone();
		} );

		var falseCacheDone = assert.async();
		smwApi.fetch( queryString, false )
		.done( function ( results ) {
			assert.equal( results.isCached , false , pass + ' caching is set "false" and results are not cached' );
			falseCacheDone();
		} );

		// Make sure the cache is initialized otherwise the asserts will fail
		// for the first test run
		var cacheDone = assert.async();
		smwApi.fetch( queryString, true )
		.done( function () {
			var firstCacheDone = assert.async();

			smwApi.fetch( queryString, 60 * 1000 )
			.done( function ( results ) {
				assert.equal( results.isCached , true , pass + ' caching is set to "60 * 1000" and results are cached' );
				firstCacheDone();
			} );

			var otherCacheDone = assert.async();
			smwApi.fetch( queryString, true )
			.done( function ( results ) {
				assert.equal( results.isCached , true , pass + ' caching is set "true" and results are cached' );
				otherCacheDone();
			} );

			cacheDone();
		} );

	} );

	/**
	 * Test fetch vs. a normal $.ajax call
	 *
	 * @since: 1.9
	 * @ignore since 2013 - should this test be removed?
	 */
	QUnit.test( 'fetch() vs. $.ajax', function ( assert ) {
		assert.expect( 3 );

		var ajaxDone = assert.async();
		var fetchDone = assert.async();
		var cachedFetchDone = assert.async();

		var smwApi = new smw.api();
		var startDate;

		// Ajax
		var queryString = '[[Modification date::+]]|?Modification date|?Modification date|?Modification date|limit=100';

		startDate = new Date();

		$.ajax( {
			url: mw.util.wikiScript( 'api' ),
			dataType: 'json',
			data: {
				'action': 'ask',
				'format': 'json',
				'query' : queryString
				}
			} )
			.done( function ( results ) {
				assert.ok( results, 'Fetch ' + results.query.meta.count + ' items using $.ajax which took: ' + ( new Date().getTime() - startDate.getTime() ) + ' ms' );
				startDate = new Date();
				ajaxDone();
			} );

		smwApi.fetch( queryString )
		.done( function ( results ) {
			assert.ok( results, 'Fetch ' + results.query.meta.count + ' items using smw.Api.fetch() which took: ' + ( new Date().getTime() - startDate.getTime() ) + ' ms' );
			fetchDone();
		} );

		smwApi.fetch( queryString, true )
		.done( function ( results ) {
			assert.ok( results, 'Fetch ' + results.query.meta.count + ' items using smw.Api.fetch() which were cached and took: ' + ( new Date().getTime() - startDate.getTime() ) + ' ms' );
			cachedFetchDone();
		} );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );
