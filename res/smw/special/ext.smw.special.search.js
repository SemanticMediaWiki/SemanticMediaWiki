/*!
 * This file is part of the Semantic MediaWiki
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
 * @license GNU GPL v2+
 * @since  3.0
 *
 * @author mwjames
 */
( function( $, mw ) {
	'use strict';

	/**
	 * Support text input on Special:Search
	 *
	 * @since 3.0
	 */
	var search = function() {

		var context = $( '#searchText > input' ),
			isHidden = false;

		if ( context.length ) {

			// Disable the standard autocompleter as no meaningfull help can be
			// expected on a [[ ... ]] input
			context.on( 'keyup keypres focus', function( e ) {
				var highlighter = context.parent().find( '.oo-ui-widget' ),
					style = '';

				if ( context.val().indexOf( '[' ) > -1 ) {
					style = highlighter.attr( 'style' );
					highlighter.hide();
					isHidden = true;
				} else if( isHidden ) {
					highlighter.attr( 'style', style );
					highlighter.show();
					isHidden = false;
				};
			} );
		}
	};

	/**
	 * Support extended form in Special:Search
	 *
	 * @since 3.0
	 */
	var form = function() {

		// Empty value, inject a hidden non visible char to allow to trigger
		// the search without an input text
		if ( $( '#searchText > input' ).val() === '' ) {
			$('#search').append('<input type="hidden" name="search" value=" " id="search-hidden" />' );
		};

		// If a users enters some data, remove the hidden field when it is not
		// empty.
		$( document ).on( "change keyup", "#searchText > input", function() {
			if ( $( '#searchText > input' ).val() !== '' ) {
				$( "#search-hidden" ).remove();
			} else {
				$('#search').append('<input type="hidden" name="search" value=" " id="search-hidden" />' );
			}
		} );

		$( document ).ready( function() {

			/**
			 * Copied from mediawiki.special.search.js in order to have the NS
			 * button to work without #powersearch
			 */
			var $checkboxes = $( '#search input[id^=mw-search-ns]' );
			var namespaces = [];

			// JS loaded enable all fields
			$( ".is-disabled" ).removeClass( 'is-disabled' );

			$( this ).on( "click", "#mw-search-toggleall", function(){
				$checkboxes.prop( 'checked', true );
			} );

			$( this ).on( "click", "#mw-search-toggleall", function(){
				$checkboxes.prop( 'checked', true );
			} );

			$( this ).on( "click", "#mw-search-togglenone", function(){
				$checkboxes.prop( 'checked', false );
			} );

			// When saving settings, use the proper request method (POST instead of GET).
			$( this ).on( "change", "#mw-search-powersearch-remember", function() {
				this.form.method = this.checked ? 'post' : 'get';
			} ).trigger( 'change' );

			/**
			 * Open form ...
			 */
			$( '#smw-form-open > .smw-input-group' ).each( function() {
				var context = $( this );

				if ( $( "#smw-search-forms select" ).val() === 'open' ) {
					context.find( '.smw-input-field' ).show();
					context.find( '.smw-select-field' ).show();
					context.find( '.smw-button-field' ).show();
				};

				if ( context.find( '.smw-property-input' ).val() === '' ) {
					context.find( '.smw-propertyvalue-input' ).addClass( 'is-disabled' );
				} else {
					context.find( '.smw-propertyvalue-input' ).removeClass( 'is-disabled' );
				};

				if ( context.find( '.smw-propertyvalue-input' ).val() === '' ) {
					context.find( '.smw-propertyvalue-input' ).addClass( 'is-disabled' );
				}

			} )

			$( this ).on( "change", ".smw-select-field", function( event ) {
				var context = $( this ).closest( '.smw-input-group' );
				var length = $( '#smw-form-open .smw-input-group' ) ? $( '#smw-form-open .smw-input-group' ).length : 0;

				if ( context.find( '.smw-property-input' ).val() === '' ) {
					context.find( '.smw-propertyvalue-input' ).val( '' );
					context.find( '.smw-propertyvalue-input' ).addClass( 'is-disabled' );
				}

				// Act on the `del` request but only for as long as one group remains
				if ( length > 1 && context.find( '.smw-select-field' ).val() === 'del' ) {
					context.remove();
				}

				// Disable del when there are not enough groups available
				if ( length <= 2 ) {
					$( "#smw-form-open > .smw-input-group > .smw-select-field option[value='del']" ).attr( "disabled", "disabled" );
				}
			} );

			/**
			 * Listing to the property select complete event on a field that uses
			 * the autocomplete.
			 */
			$( "#smw-form-open" ).on( "smw.autocomplete.property.select.complete", function( event, opts ) {

				var context = $( this );
				var input = opts.context.closest( '.smw-input-group' ).find( '.smw-propertyvalue-input' );

				if ( opts.suggestion && opts.suggestion.value !== '' ) {
					input.removeClass( 'is-disabled' );
					input.data( 'property', opts.suggestion.value );

					// Trigger event to enable the instance
					context.trigger( 'smw.autocomplete.propertyvalue', { context: opts.context.closest( '.smw-input-group' ) } );
				}
			} );

			/**
			 * Listing to the last property value complete event on a field that
			 * uses the autocomplete and decide whether to clone and create an
			 * empty group.
			 */
			$( "#smw-form-open" ).on( "smw.autocomplete.propertyvalue.select.complete", function( event, opts ) {
				var context = opts.context.closest( '.smw-input-group' );
				var last = $( ".smw-input-group" ).last();

				// Only clone when the autocomplete contains a term and is the last
				// property input value is not empty to avoid cloning empty lines from
				// a repeated confirmation of the same property value autocompletion
				// request
				var hasValues = last.find( '.smw-property-input' ).val() !== '' && last.last().find( '.smw-propertyvalue-input' ).val();

				if ( opts.suggestion && opts.suggestion.value !== '' && hasValues ) {

					// Clone the entire line for a new input
					var clone = context.clone();
					clone.find( "input:text" ).val( "" ).end().appendTo( '#smw-form-open' );

					// We clone which means we have +1 available
					$( "#smw-form-open > .smw-input-group > .smw-select-field option[value='del']" ).removeAttr( "disabled" );
					clone.find( '.smw-propertyvalue-input' ).addClass( 'is-disabled' );
					context.trigger( 'smw.autocomplete.property', { context: clone } );
				}
			} );

			/**
			 *  Listing to the form select field change
			 */
			$( this ).on( "change", "#smw-search-forms", function( event ) {
				var type = $( "#smw-search-forms select" ).val();
				var nsList = $( "#smw-search-forms" ).data( 'nslist' );

				$( '#smw-searchoptions .divider' ).show();

				// On any change, hide all fields by default and only make fields
				// visible that belong the selected form.
				$( '.smw-input-field' ).hide();
				$( '.smw-select-field' ).hide();
				$( '.smw-button-field' ).hide();
				$( '.smw-form-description' ).hide();

				// Important! The browser will complain about require fields that
				// have been made invisible.
				$( '.smw-input-field' ).find( 'input' ).removeAttr( 'required' );

				if ( type === 'open' ) {
					var group = $( '#smw-form-open > .smw-input-group' );

					group.find( '.smw-input-field' ).show();
					group.find( '.smw-select-field' ).show();
					group.find( '.smw-button-field' ).show();
				};

				// Restore the NS settings for the once we have forcibly set
				namespaces.map( function( v, ns ) {

					if ( v === true ) {
						$( '#search input[id^=mw-search-ns' + ns + ']' ).prop( 'checked', false );
					};

					namespaces[ns] = false
				} );

				if ( type !== '' ) {

					var fields = $( '#smw-form-' + type + ' > .smw-input-field' )

					fields.show();
					$( '#smw-form-' + type + ' .smw-form-description' ).show();

					fields.each( function() {
						var required = $( this ).find( 'input' ).data( 'required' );

						if ( required ) {
							$( this ).find( 'input' ).prop( "required", true );
						};
					} );

					// Do we have some namespaces that we want to enforce via the
					// form definition?
					if ( nsList.hasOwnProperty( type ) ) {
						nsList[type].map( function( ns ) {
							var checkbox = $( '#search input[id^=mw-search-ns' + ns + ']' );

							// Already checked by a user? Do nothing!
							if ( checkbox.prop( 'checked' ) === false ) {
								namespaces[ns] = true;
								checkbox.prop( 'checked', true );
							};
						} );
					};
				}
			} );
		} );
	};

	function load( callback ) {
		if ( document.readyState == 'complete' ) {
			callback();
		} else {
			window.addEventListener( 'load', callback );
		}
	}

	// Only load when it is Special:Search and the search type supports
	// https://www.semantic-mediawiki.org/wiki/Help:SMWSearch
	if ( mw.config.get( 'wgCanonicalSpecialPageName' ) == 'Search' && mw.config.get( 'wgSearchType' ) == 'SMWSearch' ) {
		load( search );
		load( form );
	};

} )( jQuery, mediaWiki );
