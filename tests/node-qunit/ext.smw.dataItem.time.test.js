/**
 * Ported from tests/qunit/smw/data/ext.smw.dataItem.time.test.js (#7045).
 *
 * @licence GNU GPL v2 or later
 */
( function () {
	'use strict';

	QUnit.module( 'ext.smw.dataItem.time', QUnit.newMwEnvironment() );

	QUnit.test( 'instance', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataItem.time( '1362200400' );
		assert.ok( result instanceof Object, 'the smw.dataItem.time instance was accessible' );
	} );

	QUnit.test( 'getDIType', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataItem.time( '1362200400' );
		assert.equal( result.getDIType(), '_dat', 'returned _dat' );
	} );

	QUnit.test( 'getMwTimestamp', function ( assert ) {
		assert.expect( 2 );

		var result;

		result = new smw.dataItem.time( '1362200400' );
		assert.equal( result.getMwTimestamp(), '1362200400', 'returned a MW timestamp' );

		result = new smw.dataItem.time( 1362200400 );
		assert.equal( result.getMwTimestamp(), '1362200400', 'returned a MW timestamp' );
	} );

	QUnit.test( 'getDate', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataItem.time( '1362200400' );
		assert.ok( result.getDate() instanceof Date, 'returned a JavaScript Date instance' );
	} );

	QUnit.test( 'getISO8601Date', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataItem.time( '1362200400' );
		assert.equal( result.getISO8601Date(), '2013-03-02T05:00:00.000Z', 'returned a ISO string date/time' );
	} );

	QUnit.test( 'getTimeString', function ( assert ) {
		assert.expect( 1 );

		var result = new smw.dataItem.time( '1362200400' );
		assert.equal( result.getTimeString(), '05:00:00', 'returned a time string' );
	} );

	QUnit.test( 'getMediaWikiDate', function ( assert ) {
		assert.expect( 1 );

		var monthNames = mw.language.months.names;
		var result = new smw.dataItem.time( '1362200400' );
		assert.equal( result.getMediaWikiDate(), '2 ' + monthNames[ 2 ] + ' 2013 05:00:00', 'returned a MW date and time formatted string' );
	} );

}() );
