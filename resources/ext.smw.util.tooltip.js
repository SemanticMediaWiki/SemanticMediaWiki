/**
 * JavaScript for SMW tooltip functions
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:Tooltip
 *
 * @since 1.8
 * @release 0.2
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2 or later
 * @author mwjames
 */
( function( $, mw ) {
	"use strict";
	/*global mediaWiki:true*/

	////////////////////////// PRIVATE METHODS ////////////////////////

	/**
	 * Default options
	 *
	 * viewport => $(window) keeps the tooltip on-screen at all times
	 * 'top center' + 'bottom center' => Position the tooltip above the link
	 * solo => true shows only one tooltip at a time
	 *
	 */
	var defaults = {
			position: {
				viewport: $(window),
				at: 'top center',
				my: 'bottom center'
			},
			show: {
				solo: true
			},
			style: {
				classes: 'ui-tooltip-shadow ui-tooltip-bootstrap'
			}
	};

	////////////////////////// PUBLIC METHODS ////////////////////////

	/**
	 * The SMW qtip2 instance
	 *
	 * If the button (true) is displayed it means the tooltip focus is persitent and
	 * in all other cases the tooltip is being closed by crossing the tooltip
	 *
	 * Event = 'click' means that a click event will trigger the tooltip to open in
	 * all other cases it opens via hoovering
	 *
	 * @var options
	 *
	 * @since: 1.8
	 */
	$.fn.smwTooltip = function( options ){
		this.each( function() {
			$( this ).qtip( $.extend( {}, defaults, {
				hide: options.button ? 'unfocus' : undefined,
				show: { event: options.event, solo: true },
				content: {
					text: options.content,
					title: {
						text: options.title,
						button: options.button
					}
				}
			} ) );
		} );
	};

	/////////////////////////////// DOM //////////////////////////////

	$( document ).ready( function() {

		// Mostly used for special property display
		$( '.smwttinline' ).each( function() {
			var $this = $( this );

			// Tooltip instance
			$this.smwTooltip( {
				content: $this.find( '.smwttcontent' ),
				title: mw.msg( 'smw-ui-tooltip-title-property' ),
				button: false
			} );
		} );

		// Tooltip with extended interactions for service links, info, and error messages
		$( '.smwttpersist' ).each( function() {

			// Using a click event instead to trigger the tooltip
			var click = mw.user.options.get( 'smw-prefs-tooltip-option-click' ) ? 'click' : undefined;

			// Standard configuration
			var $this = $( this ),
				content = $this.find( '.smwttcontent' ),
				title = mw.msg( 'smw-ui-tooltip-title-info' ),
				button = true; // Display close button

			// Find icon reference where it exists
			$this.find( '.smwtticon' ).each( function() {

				// Change title in accordance with its type
				var type = $( this ).data( 'type' );
				if ( type === 'service' ){
					title = mw.msg( 'smw-ui-tooltip-title-service' );
				} else if ( type === 'warning' ) {
					title = mw.msg( 'smw-ui-tooltip-title-warning' );
					button = false; // No close button
				} else {
					title = mw.msg( 'smw-ui-tooltip-title-info' );
				}
			} );

			// Tooltip instance
			$( this ).smwTooltip( { content: content, title: title, button: button, event: click } );
		} );
	} );
} )( jQuery, mediaWiki );