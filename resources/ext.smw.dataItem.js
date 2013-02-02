/**
 * SMW DataItem JavaScript representation
 *
 * Empty shell as base class
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
	 * The base class (does nothing)
	 *
	 * @since 1.9
	 * @type Object
	 */
	smw.dataItem = smw.dataItem || {};

	/**
	 * Constructor
	 *
	 * @var Object
	 */
	smw.dataItem = function() {};

	/**
	 * Public methods
	 *
	 * @since  1.9
	 *
	 * @type object
	 */
	smw.dataItem.prototype = {};

} )( jQuery, mediaWiki, semanticMediaWiki );