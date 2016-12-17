/**
 * SMW Time DataItem JavaScript representation
 *
 * @see SMW\DITime
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2 or later
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * Inheritance class
	 *
	 * @type Object
	 */
	smw.dataItem = smw.dataItem || {};

	/**
	 * Date constructor
	 *
	 * @since  1.9
	 *
	 * @param {string|number}
	 * @param {string|number}
	 *
	 * @return {this}
	 */
	var time = function ( timestamp, raw ) {
		var FLAG_YEAR  = 1;
		var FLAG_MONTH = 2;
		var FLAG_DAY   = 4;
		var FLAG_TIME  = 8;

		this.timestamp = timestamp !== '' && timestamp !== undefined ? timestamp : null;
		this.raw = raw !== '' && raw !== undefined ? raw : null;
		this.precision = 0;

		// Returns a normalized timestamp as JS date object
		if ( typeof this.timestamp === 'number' ) {
			this.date = new Date( this.timestamp * 1000 );
		}

		if ( typeof this.timestamp === 'string' ) {
			if ( this.timestamp.match(/^\d+(\.\d+)?$/) ) {
				this.date = new Date( parseFloat( this.timestamp ) * 1000 );
			}
		}

		// SMW seralization format with:
		if ( this.raw !== null ) {
			var date = this.raw.split( '/' );

			// [0] contains the calendar model
			this.calendarModel = date[0];

			this.date = new Date( date[1] );
			this.precision = FLAG_YEAR;

			// Note: January is 0, February is 1, and so on
			if ( typeof date[2] !== 'undefined' ) {
				this.date.setMonth( ( date[2] - 1 ) );
				this.precision = this.precision | FLAG_MONTH;
			};

			if ( typeof date[3] !== 'undefined' ) {
				this.date.setDate( date[3] );
				this.precision = this.precision | FLAG_DAY;
			};

			if ( typeof date[4] !== 'undefined' ) {
				this.date.setHours( date[4] );
				this.precision = this.precision | FLAG_TIME;
			};

			if ( typeof date[5] !== 'undefined' ) {
				this.date.setMinutes( date[5] );
			};

			if ( typeof date[6] !== 'undefined' ) {
				this.date.setSeconds( date[6] );
			};
		};

		return this;
	};

	/**
	 * Map wgMonthNames and create an indexed array
	 *
	 */
	var monthNames = [];
	$.map ( mw.config.get( 'wgMonthNames' ), function( index ) {
		if( index !== '' ) {
			monthNames.push( index );
		}
	} );

	/**
	 * Constructor
	 *
	 * @type object
	 */
	smw.dataItem.time = function( timestamp, raw ) {
		if ( $.type( timestamp ) === 'string' || 'number' ) {
			this.constructor( timestamp, raw );
		} else {
			throw new Error( 'smw.dataItem.time: timestamp must be a string or number' );
		}
	};

	/* Public methods */

	var fn = {

		constructor: time,

		/**
		 * Returns type
		 *
		 * @see SMW\DITime::getDIType()
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getDIType: function() {
			return '_dat';
		},

		/**
		 * Returns a MW timestamp representation of the value
		 *
		 * @see SMW\DITime::getMwTimestamp()
		 *
		 * @since 1.9
		 *
		 * @return {string}
		 */
		getMwTimestamp: function() {
			return this.timestamp;
		},

		/**
		 * Returns Date object
		 *
		 * @since 1.9
		 *
		 * @return {Date}
		 */
		getDate: function() {
			return this.date;
		},

		/**
		 * Returns an ISO string
		 *
		 * @see SMWTimeValue::getISO8601Date()
		 *
		 * @since 1.9
		 *
		 * @return {string}
		 */
		getISO8601Date: function() {
			return this.date.toISOString();
		},

		/**
		 * Returns a formatted time (HH:MM:SS)
		 *
		 * In case of no time '00:00:00' is returned
		 *
		 * @since 1.9
		 *
		 * @return {string}
		 */
		getTimeString: function() {
			var d = this.date;
			if ( d.getHours() + d.getMinutes() + d.getSeconds() === 0 ){
				return '00:00:00';
			}
			return ( d.getHours() < 10 ? '0' + d.getHours() : d.getHours() ) +
				':' + ( d.getMinutes() < 10 ? '0' + d.getMinutes() : d.getMinutes() ) +
				':' + ( d.getSeconds() < 10 ? '0' + d.getSeconds() : d.getSeconds() );
		},

		/**
		 * Returns MediaWiki's date and time formatting
		 *
		 * @since 1.9
		 *
		 * @return {string}
		 */
		getMediaWikiDate: function() {

			var FLAG_YEAR  = 1;
			var FLAG_MONTH = 2;
			var FLAG_DAY   = 4;
			var FLAG_TIME  = 8;

			var CM_GREGORIAN  = 1;
			var CM_JULIAN  = 2;

			// Fallback
			if ( this.date === undefined ) {
				return '';
			};

			// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Date
			// Use the precision from the raw date
			if ( this.precision > 0 ) {
				var calendarModel = this.calendarModel !== undefined && this.calendarModel == CM_JULIAN ? ' <sup>JL</sup>' : '';

				return '' +
					( ( this.precision & FLAG_DAY ) ? ( this.date.getDate() ) + ' ' + ( monthNames[(this.date.getMonth())] ) + ' ' : '' ) +
					( ( this.precision & FLAG_YEAR ) ? ( this.date.getFullYear() ) + ' ' : '' ) +
					( ( this.precision & FLAG_TIME ) && this.getTimeString() !== '00:00:00' ? ( this.getTimeString() ) : '' ) + calendarModel;
			};

			return this.date.getDate() + ' ' +
				monthNames[this.date.getMonth()] + ' ' +
				this.date.getFullYear() +
				( this.getTimeString() !== '00:00:00' ? ' ' + this.getTimeString() : '' );
		}
	};

	// Alias
	fn.getValue = fn.getMwTimestamp;

	// Assign methods
	smw.dataItem.time.prototype = fn;

} )( jQuery, mediaWiki, semanticMediaWiki );
