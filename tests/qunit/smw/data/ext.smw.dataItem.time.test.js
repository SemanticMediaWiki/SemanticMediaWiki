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

	QUnit.module( 'ext.smw.dataItem.time', QUnit.newMwEnvironment() );

	var pass = 'Passes because ';

	/**
	 * Test accessibility
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'instance', 1, function ( assert ) {

		var result = new smw.dataItem.time( '1362200400' );
		assert.ok( result instanceof Object, pass + 'the smw.dataItem.time instance was accessible' );

	} );

	/**
	 * Test type
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getDIType', 1, function ( assert ) {

		var result = new smw.dataItem.time( '1362200400' );
		assert.equal( result.getDIType(), '_dat', pass + 'returned _dat' );

	} );

	/**
	 * Test getUri
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getMwTimestamp', 2, function ( assert ) {
		var result;

		result = new smw.dataItem.time( '1362200400' );
		assert.equal( result.getMwTimestamp(), '1362200400', pass + 'returned a MW timestamp' );

		result = new smw.dataItem.time( 1362200400 );
		assert.equal( result.getMwTimestamp(), '1362200400', pass + 'returned a MW timestamp' );

	} );

	/**
	 * Test getDate
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getDate', 1, function ( assert ) {
		var result;

		result = new smw.dataItem.time( '1362200400' );
		assert.ok( result.getDate() instanceof Date, pass + 'returned a JavaScript Date instance' );

	} );

	/**
	 * Test getISO8601Date
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getISO8601Date', 1, function ( assert ) {
		var result;

		result = new smw.dataItem.time( '1362200400' );
		assert.equal( result.getISO8601Date(), '2013-03-02T05:00:00.000Z', pass + 'returned a ISO string date/time' );

	} );

	/**
	 * Test getTimeString
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getTimeString', 1, function ( assert ) {
		var result;

		result = new smw.dataItem.time( '1362200400' );
		assert.equal( result.getTimeString(), '05:00:00', pass + 'returned a time string' );

	} );

	/**
	 * Test getMediaWikiDate
	 *
	 * @since: 1.9
	 */
	QUnit.test( 'getMediaWikiDate', 1, function ( assert ) {
		var result;

		// Use as helper to fetch language dep. month name
		var monthNames = [];
		$.map ( mw.config.get( 'wgMonthNames' ), function( index ) {
			if( index !== '' ){
				monthNames.push( index );
			}
		} );

		result = new smw.dataItem.time( '1362200400' );
		assert.equal( result.getMediaWikiDate(), '2 ' + monthNames[2] + ' 2013 05:00:00', pass + 'returned a MW date and time formatted string' );

	} );

}( jQuery, mediaWiki, semanticMediaWiki ) );