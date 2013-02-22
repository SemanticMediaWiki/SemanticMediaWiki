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
	 *
	 * @return {this}
	 */
	var time = function ( timestamp ) {
		this.timestamp = timestamp !== '' && timestamp !== undefined ? timestamp : null;

		// Returns a normalized timestamp as JS date object
		if ( typeof this.timestamp === 'number' ) {
			this.date = new Date( this.timestamp * 1000 );
		}
		if ( typeof this.timestamp === 'string' ) {
			if ( this.timestamp.match(/^\d+(\.\d+)?$/) ) {
				this.date = new Date( parseFloat( this.timestamp ) * 1000 );
			}
		}

		return this;
	};

	/**
	 * Map wgMonthNames and create an indexed array
	 *
	 */
	var monthNames = [];
	$.map ( mw.config.get( 'wgMonthNames' ), function( index ) {
		if( index !== '' ){
			monthNames.push( index );
		}
	} );

	/**
	 * Constructor
	 *
	 * @type object
	 */
	smw.dataItem.time = function( timestamp ) {
		if ( $.type( timestamp ) === 'string' || 'number' ) {
			this.constructor( timestamp );
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