/**
 * SMW Property DataItem JavaScript representation
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
	 * Inheritance class
	 *
	 * @type Object
	 */
	smw.dataItem = smw.dataItem || {};

	/**
	 * Property constructor
	 *
	 * @since  1.9
	 *
	 * @param {string}
	 *
	 * @return {this}
	 */
	var property = function ( property ) {
		this.property = property !== '' && property !== undefined ? property : null;
		return this;
	};

	/**
	 * Constructor
	 *
	 * @var Object
	 */
	smw.dataItem.property = function( property ) {
		if ( $.type( property ) === 'string' ) {
			this.constructor( property );
		} else {
			throw new Error( 'smw.dataItem.property: invoked property must be a string but is of type ' + $.type( property ) );
		}
	};

	/* Public methods */

	smw.dataItem.property.prototype = {

		constructor: property,
		namespace: 102, // SMW_NS_PROPERTY needs a better way to fill this value

		/**
		 * Returns type
		 *
		 * @see SMW\DIProperty::getDIType()
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getDIType: function() {
			return '_IDONTKNOW'; // what is the type here
		},

		/**
		 * Returns a label
		 *
		 * @see SMW\DIProperty::getLabel()
		 *
		 * @since 1.9
		 *
		 * @return {string}
		 */
		getLabel: function() {
			return this.property;
		},

		/**
		 * Returns wikiPage representation
		 *
		 * @see SMW\DIProperty::getDiWikiPage()
		 *
		 * @since 1.9
		 *
		 * @return {smw.dataItem.wikiPage}
		 */
		getDiWikiPage: function() {
			return null; //new smw.dataItem.wikiPage( this.property );
		},

		/**
		 * Returns html representation
		 *
		 * @since  1.9
		 *
		 * @param {boolean}
		 * @return {string}
		 */
		getHtml: function( linker ) {
			if( linker ){
				return html.element( 'a', { href: mw.util.wikiGetlink( 'Property:' + this.property ) }, this.property );
			}
			return this.property;
		}
	};

	// Alias

} )( jQuery, mediaWiki, semanticMediaWiki );