/**
 * JavaScript for tooltip related functions
 *
 * @see https://www.mediawiki.org/wiki/Extension:Semantic_MediaWiki
 * @licence: GNU GPL v2 or later
 *
 * @since: 1.8
 * @release: 0.1
 *
 * @author: mwjames
 */

( function( $, mw ) {
	"use strict";
	/*global mediaWiki:true*/

	/**
	 * Default options
	 *
	 */
	var defaults = {
			position: {
				viewport: $(window), // Keep the tooltip on-screen at all times
				at: 'top center',  // Position the tooltip above the link
				my: 'bottom center'
			},
			show: {
				solo: true // Only show one tooltip at a time
			},
			style: {
				classes: 'ui-tooltip-shadow ui-tooltip-bootstrap'
			}
	};

	/**
	 * Add icon image
	 *
	 * @return object
	 */
	var addIcon = function( options ){
		var h = mw.html,
			icon = h.element( 'span', { 'class' : options.className, 'style': 'display:inline;' },
			new h.Raw( h.element( 'img', {
					src: mw.config.get( 'wgExtensionAssetsPath' ) + '/SemanticMediaWiki/resources/images/' + options.image,
					title: options.title
				}
			) )
		);
		return icon;
	};

	/**
	 * Handle qtip2 instances
	 *
	 * @var options
	 *
	 * @since: 1.8
	 */
	$.fn.smwQTooltip = function( options ){
		this.each( function() {
			var content = $( this ).find( '.smwttcontent' );
			$( this ).qtip( $.extend( {}, defaults, {
			hide: options.focus,
			content: {
				text: content,
				title: {
					text: options.title,
					button: options.button
				}
			}
		} ) );
	} );
	};

	/**
	 * Handle DOM instances
	 *
	 */
	$( document ).ready( function() {

		// Hover like behaviour mainly for property links
		$( '.smwttinline' ).smwQTooltip( {
			title: mw.msg( 'smw-ui-tooltip-title-property' ),
			button: false
		} );

		// Allow user interactions for service links, info, and error messages
		$( '.smwttpersist' ).each( function() {

			// Standard configuration
			var $this = $( this ),
				title = mw.msg( 'smw-ui-tooltip-title-info' ),
				button = true,  // Display close button
				focus = 'unfocus'; // Stay open until it is closed

			// Find icon instance where it exists
			$this.find( '.smwtticon' ).each( function() {

				// Change title in accordance with its type
				var type = $( this ).data( 'type' );
				if ( type === 'service' ){
					title = mw.msg( 'smw-ui-tooltip-title-service' );
				} else if ( type === 'warning' ) {
					title = mw.msg( 'smw-ui-tooltip-title-warning' );
					button = false; // No close button
					focus = undefined; // Hover like behaviour
				} else {
					title = mw.msg( 'smw-ui-tooltip-title-info' );
				}

			} );

			// Tooltip instance
			$( this ).smwQTooltip( { title: title, button: button, focus: focus } );
		} );

	} );
} )( jQuery, mediaWiki );