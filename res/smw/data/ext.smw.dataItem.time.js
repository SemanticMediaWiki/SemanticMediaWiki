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

		// SMW serialization format with:
		if ( this.raw !== null ) {
			var date = this.raw.split( '/' );

			// [0] contains the calendar model
			this.calendarModel = date[0];
			this.precision = FLAG_YEAR;

			if ( typeof date[2] !== 'undefined' ) {
				// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Date
				// Note: January is 0, February is 1, and so on
				date[2] = date[2] - 1;
				this.precision = this.precision | FLAG_MONTH;
			} else {
				date[2] = 0;
			}

			if ( typeof date[3] !== 'undefined' ) {
				this.precision = this.precision | FLAG_DAY;
			} else {
				date[3] = 0;
			}

			if ( typeof date[4] !== 'undefined' ) {
				this.precision = this.precision | FLAG_TIME;
			} else {
				date[4] = 0;
			}

			if ( typeof date[5] === 'undefined' ) {
				date[5] = 0;
			}

			if ( typeof date[6] === 'undefined' ) {
				date[6] = 0;
			}

			// Date is called as a constructor with more than one argument, the
			// specifed arguments represent local time. If UTC is desired, use
			// new Date(Date.UTC(...))
			this.date = new Date( Date.UTC( date[1], date[2], date[3], date[4], date[5], date[6] ) );
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
			if ( d.getUTCHours() + d.getUTCMinutes() + d.getUTCSeconds() === 0 ){
				return '00:00:00';
			}
			return ( d.getUTCHours() < 10 ? '0' + d.getUTCHours() : d.getUTCHours() ) +
				':' + ( d.getUTCMinutes() < 10 ? '0' + d.getUTCMinutes() : d.getUTCMinutes() ) +
				':' + ( d.getUTCSeconds() < 10 ? '0' + d.getUTCSeconds() : d.getUTCSeconds() );
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
					( ( this.precision & FLAG_DAY ) ? ( this.date.getUTCDate() ) + ' ' + ( monthNames[(this.date.getUTCMonth())] ) + ' ' : '' ) +
					( ( this.precision & FLAG_YEAR ) ? ( this.date.getUTCFullYear() ) + ' ' : '' ) +
					( ( this.precision & FLAG_TIME ) && this.getTimeString() !== '00:00:00' ? ( this.getTimeString() ) : '' ) + calendarModel;
			};

			return this.date.getUTCDate() + ' ' +
				monthNames[this.date.getUTCMonth()] + ' ' +
				this.date.getUTCFullYear() +
				( this.getTimeString() !== '00:00:00' ? ' ' + this.getTimeString() : '' );
		}
	};

	// Alias
	fn.getValue = fn.getMwTimestamp;

	// Assign methods
	smw.dataItem.time.prototype = fn;

} )( jQuery, mediaWiki, semanticMediaWiki );
