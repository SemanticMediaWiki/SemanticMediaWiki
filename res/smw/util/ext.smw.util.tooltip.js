/*!
 * This file is part of the Semantic MediaWiki Tooltip/Highlighter module
 * @see https://semantic-mediawiki.org/wiki/Help:Tooltip
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
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.8
 * @revision 0.3.4
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * Support variable
	 * @ignore
	 */
	var h = mw.html;

	/**
	 * Inheritance class for the smw.util constructor
	 *
	 * @since 1.9
	 * @class
	 */
	smw.util = smw.util || {};

	/**
	 * Class constructor
	 *
	 * @since 1.8
	 *
	 * @class
	 * @constructor
	 */
	smw.util.tooltip = function( settings ) {
		$.extend( this, this.defaults, settings );
	};

	/* Public methods */

	smw.util.tooltip.prototype = {

		/**
		 * Default options
		 *
		 * viewport => $(window) keeps the tooltip on-screen at all times
		 * 'top center' + 'bottom center' => Position the tooltip above the link
		 * solo => true shows only one tooltip at a time
		 *
		 * @since 1.8
		 *
		 * @property
		 */
		defaults: {
			qtip: {
				position: {
					viewport: $( window ),
					at: 'top center',
					my: 'bottom center'
				},
				show: {
					solo: true
				},
				content: {
						title: {
							button: false
						}
				},
				style: {
					classes: 'qtip-shadow qtip-bootstrap'
				}
			},
			classes: {
				targetClass: 'smwtticon',
				contentClass: 'smwttcontent',
				contextClass: 'smwttpersist'
			}
		},

		/**
		 * Get title message
		 *
		 * @since 1.8
		 *
		 * @param {string} key
		 *
		 * @return string
		 */
		getTitleMsg: function( key ){
			switch( key ){
				case 'quantity': return 'smw-ui-tooltip-title-quantity';
				case 'property': return 'smw-ui-tooltip-title-property';
				case 'service' : return 'smw-ui-tooltip-title-service';
				case 'warning' : return 'smw-ui-tooltip-title-warning';
			default: return 'smw-ui-tooltip-title-info';
			}
		},

		/**
		 * Initializes the qtip2 instance
		 *
		 * Example:
		 *        tooltip = new smw.util.tooltip();
		 *        tooltip.show ( {
		 *            title: ...,
		 *            type: ...,
		 *            content: ...,
		 *            button: ...,
		 *            event: ...
		 *        } );
		 *
		 * @since 1.8
		 *
		 * @param {Object} options
		 */
		show: function( options ) {
			var self = this;

			// Check context
			if ( options.context === undefined ){
				return $.error( 'smw.util.tooltip.show() is missing a context object' );
			}

			return options.context.each( function() {
				$( this ).qtip( $.extend( {}, self.defaults.qtip, {
					hide: options.button ? 'unfocus' : undefined,
					show: { event: options.event, solo: true },
					content: {
						text: options.content,
						title: {
							text: options.title,
							button: options.button
						}
					}
				}	) );
			} );
		},

		/**
		 * The add method is a convenience method allowing to create a tooltip element
		 * with immediate instantiation
		 *
		 * @since 1.8
		 *
		 * @param {Object} options
		 */
		add: function( options ) {
			var self = this;

			// Defaults
			var option = $.extend( true, self.defaults.classes, options );

			// Check context
			if ( option.context === undefined ){
				return $.error( 'smw.util.tooltip.add() is missing a context object' );
			}

			// Build a html element
			function buildHtml( options ){
				return h.element( 'span', { 'class' : options.contextClass, 'data-type': options.type },
					new h.Raw(
						h.element( 'span', { 'class' : options.targetClass }, null ) +
						h.element( 'span', { 'class' : options.contentClass }, new h.Raw( options.content ) ) )
				);
			}

			// Assign context
			var $this = option.context;

			// Append element
			$this.prepend( buildHtml( option ) );

			// Ensure that the right context is used as hoover/click element
			// The class [] selector is not the fastest but the safest otherwise if
			// spaces are used in the class definition it will break the selection
			self.show.call( this,
				$.extend( true, options, {
					context: $this.find( "[class='" + option.targetClass + "']" ),
					content: $this.find( "[class='" + option.contentClass + "']" )
				} )
			);
		},

		/**
		 * @since 2.4
		 *
		 * @param {Object} container
		 */
		render: function( container ) {
			var self = this;

			container.each( function() {

				// Get configuration
				var $this = $( this ),
					eventPrefs = mw.user.options.get( 'smw-prefs-tooltip-option-click' ) ? 'click' : undefined,
					state      = $this.data( 'state' ),
					title      = $this.data( 'title' ),
					type       = $this.data( 'type' );

				// Assign sub-class
				// Inline mostly used for special properties and quantity conversions
				// Persistent extends interactions for service links, info, and error messages
				$this.addClass( state === 'inline' ? 'smwttinline' : 'smwttpersist' );

				// Call instance
				self.show( {
					context: $this,
					content: $this.find( '.smwttcontent' ),
					title  : title !== undefined ? title : mw.msg( self.getTitleMsg( type ) ),
					event  : eventPrefs,
					button : type === 'warning' || state === 'inline' ? false /* false = no close button */ : true
				} );
			} );
		}
	};

	/**
	 * Implementation of a tooltip instance
	 * @since 1.8
	 * @ignore
	 */
	$( document ).ready( function() {
		var tooltip = new smw.util.tooltip();

		tooltip.render(
			$( '.smw-highlighter' )
		);
	} );

} )( jQuery, mediaWiki, semanticMediaWiki );
