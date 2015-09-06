/*!
 * This file is part of the Semantic MediaWiki Extension
 * @see https://semantic-mediawiki.org/
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
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * Helper method
	 * @ignore
	 */
	var html = mw.html;

	/**
	 * Inheritance class for the smw.dataItem constructor
	 *
	 * @since 1.9
	 *
	 * @class smw.dataItem
	 * @abstract
	 */
	smw.dataItem = smw.dataItem || {};

	/**
	 * Initializes the constructor
	 *
	 * @param {string} fulltext
	 * @param {string} fullurl
	 * @param {number} ns
	 * @param {boolean} exists
	 *
	 * @return {smw.dataItem.wikiPage} this
	 */
	var wikiPage = function ( fulltext, fullurl, ns, exists ) {
		this.fulltext  = fulltext !== ''&& fulltext !==  undefined ? fulltext : null;
		this.fullurl   = fullurl !== '' && fullurl !==  undefined ? fullurl : null;
		this.ns        = ns !==  undefined ? ns : 0;
		this.exists    = exists !==  undefined ? exists : true;

		// Get mw.Title inheritance
		if ( this.fulltext !== null ){
			this.title = new mw.Title( this.fulltext );
		}

		return this;
	};

	/**
	 * A class that includes methods to create a wikiPage dataItem representation
	 * in JavaScript that resembles the SMW\DIWikiPage object in PHP
	 *
	 * @since 1.9
	 *
	 * @class
	 * @constructor
	 */
	smw.dataItem.wikiPage = function( fulltext, fullurl, ns, exists ) {
		if ( $.type( fulltext ) === 'string' && $.type( fullurl ) === 'string' ) {
			this.constructor( fulltext, fullurl, ns, exists );
		} else {
			throw new Error( 'smw.dataItem.wikiPage: fulltext, fullurl must be a string' );
		}
	};

	/* Public methods */

	var fn = {

		constructor: wikiPage,

		/**
		 * Returns type
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getDIType: function() {
			return '_wpg';
		},

		/**
		 * Returns wikiPage text title as full name like "File:Foo bar.jpg"
		 * due to fact that the name is serialized in fulltext
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getFullText: function() {
			return this.fulltext;
		},

		/**
		 * Returns main part of the title without any fragment
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getText: function() {
			return this.fulltext && this.fulltext.split( '#' )[0];
		},

		/**
		 * Returns wikiPage uri
		 *
		 * @since  1.9
		 *
		 * @return {string}
		 */
		getUri: function() {
			return this.fullurl;
		},

		/**
		 * Returns mw.Title object
		 *
		 * @since  1.9
		 *
		 * @return {mw.Title}
		 */
		getTitle: function() {
			return this.title;
		},

		/**
		 * Returns if the wikiPage is a known entity or not
		 *
		 * @since  1.9
		 *
		 * @return {boolean}
		 */
		isKnown: function(){
			return this.exists;
		},

		/**
		 * Returns namespace id
		 *
		 * @since  1.9
		 *
		 * @return {number}
		 */
		getNamespaceId: function() {
			return this.ns;
		},

		/**
		 * Returns html representation
		 *
		 * @since  1.9
		 *
		 * @param {boolean}
		 *
		 * @return {string}
		 */
		getHtml: function( linker ) {
			if ( linker && this.fullurl !== null ){
				var attributes = this.exists ? { 'href': this.fullurl } : { 'href': this.fullurl, 'class': 'new' };
				return html.element( 'a', attributes , this.getText() );
			}
			return this.getText();
		}
	};

	// Alias
	fn.exists = fn.isKnown;
	fn.getPrefixedText = fn.getFullText;
	fn.getName = fn.getFullText;
	fn.getValue = fn.getFullText;

	// Assign methods
	smw.dataItem.wikiPage.prototype = fn;

	// For additional methods use
	// $.extend( smw.dataItem.wikiPage.prototype, { method: function (){ ... } } );

} )( jQuery, mediaWiki, semanticMediaWiki );