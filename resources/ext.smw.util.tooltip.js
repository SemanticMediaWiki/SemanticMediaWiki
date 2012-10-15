/**
 * JavaScript for SMW tooltip functions
 * @see http://www.semantic-mediawiki.org/wiki/Help:Tooltip
 *
 * @since 1.8
 * @release 0.3.1
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2 or later
 * @author mwjames
 */
( function( $, mw, smw ) {

	"use strict";

	/*global mediaWiki:true semanticMediaWiki:true*/

	////////////////////////// PRIVATE METHODS ////////////////////////

	// Ensure global object is instantiate
	smw.util = smw.util || {};

	// Helper variable
	var h = mw.html;

	/**
	 * Default options
	 *
	 * viewport => $(window) keeps the tooltip on-screen at all times
	 * 'top center' + 'bottom center' => Position the tooltip above the link
	 * solo => true shows only one tooltip at a time
	 *
	 */
	var defaults = {
			qtip: {
				position: {
					viewport: $(window),
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
					classes: 'ui-tooltip-shadow ui-tooltip-bootstrap'
				}
			},
			classes: {
				targetClass: 'smwtticon',
				contentClass: 'smwttcontent',
				contextClass: 'smwttpersist'
			}
	};

	/**
	 * Build a html element
	 *
	 * @var object
	 * @return string
	 */
	function _getHTMLElement( options ){
		return h.element( 'span', { 'class' : options.contextClass, 'data-type': options.type },
			new h.Raw(
				h.element( 'span', { 'class' : options.targetClass }, null ) +
				h.element( 'span', { 'class' : options.contentClass }, new h.Raw( options.content ) ) )
		);
	}

	/**
	 * Get title message
	 *
	 * @var type
	 * @return string
	 */
	function _getTitle( type ){
		switch( type ){
			case 'quantity': return 'smw-ui-tooltip-title-quantity';
			case 'property': return 'smw-ui-tooltip-title-property';
			case 'service' : return 'smw-ui-tooltip-title-service';
			case 'warning' : return 'smw-ui-tooltip-title-warning';
		default: return 'smw-ui-tooltip-title-info';
		}
	}

	////////////////////////// PUBLIC METHODS ////////////////////////

	/**
	 * Constructor
	 * @var Object
	 */
	smw.util.tooltip = function( settings ) {
		$.extend( this, defaults, settings );
	};

	smw.util.tooltip.prototype = {
		/**
		 * Init method initializes the qtip2 instance
		 *
		 * Example
		 * tooltip = new smw.util.tooltip();
		 * tooltip.show ( { title: ..., type: ..., content: ..., button: ..., event: ... } );
		 *
		 * @since 1.8
		 */
		show: function( options ) {

			// Check context
			if ( options.context === undefined ){
				return $.error( 'smw.util.tooltip show method is missing a context object' );
			}

			return options.context.each( function() {
				$( this ).qtip( $.extend( {}, defaults.qtip, {
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
		 */
		add : function( options ) {
			// Defaults
			var option = $.extend( true, defaults.classes, options );

			// Check context
			if ( option.context === undefined ){
				return $.error( 'smw.util.tooltip add method is missing a context object' );
			}

			// Assign context
			var $this = option.context;

			// Append element
			$this.prepend( _getHTMLElement( option ) );

			// Ensure that the right context is used as hoover/click element
			// The class [] selector is not the fastest but the safest otherwise if
			// spaces are used in the class definition it will break the selection
			this.show.call( this,
				$.extend( true, options, {
					context: $this.find( "[class='" + option.targetClass + "']" ),
					content: $this.find( "[class='" + option.contentClass + "']" )
				} )
			);
		}
	};

	/////////////////////////////// DOM //////////////////////////////

	$( document ).ready( function() {

		// Class reference
		var tooltip = new smw.util.tooltip();

		// Inline mostly used for special properties and quantity conversions
		// Persistent extends interactions for service links, info, and error messages
		$( '.smwttpersist,.smwttinline' ).each( function() {

			// Standard configuration
			var $this = $( this ),
				event   = mw.user.options.get( 'smw-prefs-tooltip-option-click' ) ? 'click' : undefined,
				context = $this.data( 'context' ),
				type    = $this.data( 'type' );

			// Call instance
			tooltip.show( {
				context: $this,
				content: $this.find( '.smwttcontent' ),
				title  : mw.msg( _getTitle( type ) ),
				event  : event,
				button : type === 'warning' || context === 'inline' ? false /* No close button */ : true
			} );

		} );
	} );
} )( jQuery, mediaWiki, semanticMediaWiki );