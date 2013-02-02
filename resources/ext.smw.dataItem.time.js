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

	var html = mw.html;

	/**
	 * Date constructor
	 *
	 * @since  1.9
	 *
	 * @param {String|Integer}
	 * @return this
	 */
	var time = function ( timestamp ) {
		this.timestamp = timestamp !== '' && timestamp !== undefined ? timestamp : null;

console.log( timestamp );

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
	$.map ( mw.config.get( wgMonthNames ), function( index, value ) {
		if( value !== '' ){
			monthNames.push( value );
		}
	} );

	/**
	 * Inheritance class
	 *
	 * @type Object
	 */
	smw.dataItem = smw.dataItem || {};

	/**
	 * Constructor
	 *
	 * @var Object
	 */
	smw.dataItem.time = function( timestamp ) {
		if ( $.type( timestamp ) === 'string' || 'number' ) {
			this.constructor( timestamp );
		} else {
			throw new Error( 'smw.dataItem.time: timestamp must be a string or number' );
		}
	};

	/**
	 * Public methods
	 *
	 * Invoke methods on the constructor
	 *
	 * @since  1.9
	 *
	 * @type object
	 */
	smw.dataItem.time.prototype = {

		constructor: time,

		/**
		 * Returns type
		 *
		 * @see SMW\DITime::getDIType()
		 *
		 * @since  1.9
		 *
		 * @return {String}
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
		 * @return {String}
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
		 * @return {String}
		 */
		getISO8601Date: function() {
			return this.date.toISOString();
		},

		/**
		 * Returns a formatted time (HH:MM:SS)
		 *
		 * @since 1.9
		 *
		 * @return {String}
		 */
		getTimeString: function() {
			var d = this.date;
			return ( d.getHours() < 10 ? '0' + d.getHours() : d.getHours() ) +
				':' + ( d.getMinutes() < 10 ? '0' + d.getMinutes() : d.getMinutes() ) +
				':' + ( d.getSeconds() < 10 ? '0' + d.getSeconds() : d.getSeconds() );
		},

		/**
		 * Returns MediaWiki's date and time formatting
		 *
		 * @since 1.9
		 *
		 * @return {String}
		 */
		getMediaWikiDate: function() {
			return this.date.getDate() + ' ' +
				monthNames[this.date.getMonth()] + ' ' +
				this.date.getFullYear() + ' ' +
				this.getTimeString();
		},

		/**
		 * Returns html representation
		 *
		 * @since  1.9
		 *
		 * @param linker
		 * @return string
		 */
		getHtml: function() {
			return this.getMediaWikiDate();
		}
	};

	// Alias

} )( jQuery, mediaWiki, semanticMediaWiki );