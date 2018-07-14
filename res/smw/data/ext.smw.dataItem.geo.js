/**
 * SMW Text DataItem JavaScript representation
 *
 * @see  SMW\DIGeoCoord
 *
 * Implementation of dataitems that are geographic coordinates.
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2 or later
 * @author Peter Grassberger < petertheone@gmail.com >
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * Inheritance class for the smw.dataItem constructor
	 *
	 * @class
	 * @abstract
	 */
	smw.dataItem = smw.dataItem || {};

	/**
	 * Number constructor
	 *
	 * @param {string}
	 * @param {string}
	 * @return {this}
	 */
	var geo = function ( geo, type ) {
		this.geo = geo !== {} ? geo : null;

		// If the type is not specified we assume it has to be '_geo'
		this.type = type !== '' && type !== undefined ? type : '_geo';

		return this;
	};

	/**
	 * Class constructor
	 *
	 * @class
	 * @constructor
	 * @extends smw.dataItem
	 */
	smw.dataItem.geo = function( geo, type ) {
		if ( $.type( geo ) === 'object' ) {
			this.constructor( geo, type );
		} else {
			throw new Error( 'smw.dataItem.geo: invoked text must be a string but is of type ' + $.type( geo ) );
		}
	};

	/* Public methods */

	var fn = {

		constructor: geo,

		/**
		 * Returns type
		 *
		 * @return {string}
		 */
		getDIType: function() {
			return this.type;
		},

		/**
		 * Returns a plain geo representation
		 *
		 * @return {string}
		 */
		getGeo: function() {
			return this.geo;
		}
	};

	// Alias
	fn.getValue = fn.getGeo;
	fn.getString = fn.getGeo;

	// Assign methods
	smw.dataItem.geo.prototype = fn;

} )( jQuery, mediaWiki, semanticMediaWiki );
