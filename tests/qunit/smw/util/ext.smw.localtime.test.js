( function () {
	'use strict';

	QUnit.module( 'ext.smw.localtime' );

	QUnit.test( 'parseTimeCorrection variants', function ( assert ) {
		var lt = mw.smw.localtime;

		assert.deepEqual( lt.parseTimeCorrection( 'Offset|120' ), { minutes: 120 } );
		assert.deepEqual( lt.parseTimeCorrection( 'ZoneInfo|120|Europe/Berlin' ), { zone: 'Europe/Berlin' } );
		assert.strictEqual( lt.parseTimeCorrection( 'System' ), null );
		assert.strictEqual( lt.parseTimeCorrection( '' ), null );
	} );

	QUnit.test( 'convert applies a fixed offset', function ( assert ) {
		var el = document.createElement( 'time' );
		el.setAttribute( 'datetime', '2024-06-01T14:00:00Z' );
		el.textContent = '1 June 2024 14:00';

		mw.smw.localtime.convert( el, { minutes: 120 } );

		// 14:00 UTC + 120 min = 16:00
		assert.ok( /16:00/.test( el.textContent ), 'shows shifted time' );
	} );

	QUnit.test( 'convert applies a named zone', function ( assert ) {
		var el = document.createElement( 'time' );
		el.setAttribute( 'datetime', '2024-06-01T14:00:00Z' );
		el.textContent = '1 June 2024 14:00';

		mw.smw.localtime.convert( el, { zone: 'Europe/Berlin' } );

		// 14:00 UTC in CEST (UTC+2 on 2024-06-01) = 16:00
		assert.ok( /16:00/.test( el.textContent ), 'shows zone-adjusted time' );
	} );

	QUnit.test( 'convert leaves an invalid anchor untouched', function ( assert ) {
		var el = document.createElement( 'time' );
		el.setAttribute( 'datetime', 'not-a-date' );
		el.textContent = 'unchanged';

		mw.smw.localtime.convert( el, { minutes: 120 } );

		assert.strictEqual( el.textContent, 'unchanged' );
	} );
}() );
