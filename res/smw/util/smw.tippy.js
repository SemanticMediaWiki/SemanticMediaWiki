/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */

/*global jQuery, mediaWiki, mw */
( function ( $, mw, smw ) {

	'use strict';

	const state = {
		isFetching: false,
		isShowing: false,
		canFetch: true
	}

	var container = function( title, content, tip ) {

		var cancel = '', head = '', top = '', bottom = '', theme = '', hint = '';
		var theme = tip.smw.theme;

		if ( tip.smw.isPersistent ) {
			cancel = '<span class="tippy-cancel"></span>';
		} else if ( tip.reference.getAttribute( "data-type" ) ) {
			if ( tip.reference.getAttribute( "data-type" ) === '4' ) {
				hint = '<span class="tippy-hint-warning"></span>';
			};

			if ( tip.reference.getAttribute( "data-type" ) === '5' ) {
				hint = '<span class="tippy-hint-error"></span>';
			};
		};

		head = '<div class="tippy-header ' + theme + '">' + cancel + title + hint + '</div>';

		if ( tip.reference.getAttribute( "data-top" ) ) {
			top = '<div class="tippy-top ' + theme + '">' + tip.reference.getAttribute( "data-top" ) + '</div>';
		};

		if ( tip.reference.getAttribute( "data-bottom" ) ) {
			bottom = '<div class="tippy-bottom ' + theme + '">' + tip.reference.getAttribute( "data-bottom" ) + '</div>';
		};

		return head + top + '<div class="tippy-content-container ' + theme + '">' + content + '</div>' + bottom;
	}

	var options = {
		target: '.smw-highlighter',
		arrow: true,
		interactive: true,
		placement: "top",
		flipOnUpdate: true,
		theme: 'light-border',
		animation: 'scale',
		hideOnClick: false,
		ignoreAttributes: true,
		maxWidth:260,

		/**
		 * Function invoked when the tippy begins to transition in
		 */
		onShow: function ( tip ) {

			var isRTL = document.documentElement.dir === "rtl";

			// Move away from the `bodyContent` border
			if ( tip.reference.offsetWidth < tip.props.maxWidth ) {

				if ( isRTL ) {
					var distance = $( '#bodyContent' ).width() - $( tip.reference ).offset().left;
				} else {
					var distance = $( tip.reference ).offset().left - $( '#bodyContent' ).offset().left;
				}

				var width = tip.reference.offsetWidth <= 16 ? tip.props.maxWidth : tip.reference.offsetWidth;
				var center = ( width / 2 ) - 20;

				if ( distance == 0 ) {
					tip.set( { offset: center } );
				} else if ( distance < center && isRTL ) {
					tip.set( { offset: -center + distance } );
				} else if ( distance < center ) {
					tip.set( { offset: center - distance } );
				}
			}

			// Initialized? Track state information per instance in order
			// to mimic known behaviour of the SMW qTip2 tooltip
			// `persistent` means:
			// - `hide` only happens on the close button and remains visible
			if ( tip.hasOwnProperty( 'smw' ) === false ) {
				tip.smw = {
					isPersistent: false,
					isDeferred: false,
					hasContent: false,
					wasClicked: false,
					theme: '',
					title: ''
				};

				if ( tip.reference.getAttribute( "data-state" ) ) {
					tip.smw.isPersistent = tip.reference.getAttribute( "data-state" ) === 'persistent';
				}

				if ( tip.reference.getAttribute( "data-maxwidth" ) ) {
					tip.set( { maxWidth: parseInt( tip.reference.getAttribute( "data-maxwidth" ) ) } );
				}

				if ( tip.reference.getAttribute( "data-theme" ) ) {
					tip.smw.theme = tip.reference.getAttribute( "data-theme" );
				}

				tip.reference.setAttribute( "title", '' );

				if ( tip.reference.getAttribute( "data-title" ) ) {
					tip.smw.title = tip.reference.getAttribute( "data-title" );
				} else if ( tip.reference.getAttribute( "data-type" ) ) {
					tip.smw.title = mw.msg( 'smw-ui-tooltip-title-' + tip.reference.getAttribute( "data-type" ) );
				} else {
					tip.smw.title = mw.msg( 'smw-ui-tooltip-title-info' );
				}
			};

			// #260
			// Close any open tooltips
			document.querySelectorAll( '.tippy-popper' ).forEach(popper => {
				if ( popper !== tip.popper ) {
					popper._tippy.smw.wasClicked = true;
					popper._tippy.hide()
				}
			} );

			// Content already present?
			if ( tip.smw.hasContent ) {
				return;
			}

			if ( tip.smw.isDeferred === false ) {

				var content = '',
					title = tip.smw.title;

				if ( tip.reference.getAttribute( "data-content" ) !== '' ) {
					content = tip.reference.getAttribute( "data-content" );
				};

				if ( content === null ) {
					content = tip.reference.getElementsByClassName( "smwttcontent" )[0].innerHTML;
					content = content.replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">");
				};

				if ( content === '' ) {
					title = mw.msg( 'smw-ui-tooltip-title-error' );
					content = 'Missing a content object';
				} else {
					tip.smw.hasContent = true;
				}

				tip.setContent(
					container( title, content, tip )
				);
			}

			if ( tip.smw.isPersistent ) {
				tip.popper.querySelector( '.tippy-cancel' ).addEventListener( "click", function() {
					tip.smw.wasClicked = true;
					tip.hide();
				} );
			};

			state.isFetching = false;
			state.canFetch = true;
		},

		/**
		 * Function invoked when the tippy begins to transition out
		 */
		onHide: function ( tip ) {

			var wasClicked = tip.smw.wasClicked;
			tip.smw.wasClicked = false;

			if ( tip.smw.isPersistent === false || ( tip.smw.isPersistent && wasClicked ) ) {
				return true;
			};

			return false;
		},

		/**
		 * Function invoked when the tippy has fully transitioned in
		 */
		onShown: function ( tip ) {
			state.isShowing = true;
		},

		/**
		 * Function invoked when the tippy has fully transitioned out
		 */
		onHidden: function ( tip ) {
			state.canFetch = true;
			state.isShowing = false;
		}
	};

	/**
	 * @since 1.9
	 * @class
	 */
	smw.util = smw.util || {};

	smw.util.tippy = function( settings ) {
		$.extend( this, this.defaults, settings );
	};

	smw.util.tippy.prototype = {
		defaults: {}
	}

	/**
	 * @since 3.1
	 * @method
	 *
	 * @param {Object} context
	 */
	smw.util.tippy.prototype.show = function( opt ) {
		this.initFromContext( opt.context, { target: '', title: opt.title, content: opt.content } );
	}

	/**
	 * @since 3.1
	 * @method
	 *
	 * @param {Object} context
	 */
	smw.util.tippy.prototype.initFromContext = function( context, opt ) {

		var target = '.smw-highlighter';

		if ( context instanceof jQuery ) {
			context = context[0];
		};

		if ( opt !== undefined && opt.hasOwnProperty( 'target' ) ) {
			target = opt.target;
		};

		if ( opt !== undefined && opt.hasOwnProperty( 'title' ) ) {
			context.setAttribute( 'data-title', opt.title );
		};

		if ( opt !== undefined && opt.hasOwnProperty( 'content' ) ) {
			context.setAttribute( 'data-content', opt.content );
		};

		options.target = target;

		tippy( context, options );
	};

	/**
	 * @since 3.1
	 * @method
	 */
	smw.util.tippy.prototype.addDefaultEventListeners = function() {

		var self = this;

		// Listen to the Special:Browse event
		mw.hook( 'smw.browse.apiparsecomplete' ).add( function( context ) {
			 self.initFromContext( context );
		} );

		// Listen to the Special:Browse event
		mw.hook( 'smw.tooltip' ).add( function( context ) {
			self.initFromContext( context );
		} );

		// Listen to the smw.deferred.query event
		mw.hook( 'smw.deferred.query' ).add( function( context ) {
			self.initFromContext( context );
		} );

		// SemanticForms/PageForms instance trigger
		mw.hook( 'sf.addTemplateInstance' ).add( function( context ) {
			self.initFromContext( context );
		} );

		mw.hook( 'pf.addTemplateInstance' ).add( function( context ) {
			self.initFromContext( context );
		} );

		return self;
	};

	if ( document.getElementsByClassName( "smw-highlighter.is-disabled" ).length > 0 ) {
		document.getElementsByClassName( "smw-highlighter.is-disabled" )[0].classList.remove( 'is-disabled' );
	};

	// Running in default mode which would be on
	// $( document ).ready( function() { ... } ); when relying on jQuery
	tippy( '#bodyContent', options )
	tippy( '.mw-indicators', options )

	/**
	 * Factory
	 * @since 3.1
	 */
	var Factory = {
		newTooltip: function() {
			return new smw.util.tippy();
		}
	}

	// Register default listeners
	Factory.newTooltip().addDefaultEventListeners();

	// Legacy
	smw.util.tooltip = function( settings ) {
		return Factory.newTooltip( settings );
	};

	smw.Factory = smw.Factory || {};
	smw.Factory = $.extend( smw.Factory, Factory );

}( jQuery, mediaWiki, semanticMediaWiki ) );
