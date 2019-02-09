/*!
 * @license GNU GPL v2+
 * @since  3.0
 *
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * Support extended form in Special:Search
	 *
	 * @since 3.0
	 */
	var form = function() {

		var namespaces = [];
		var ui = new smw.ui();

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

		$( document ).on( "click", "#smw-search-sort", function(){

			var that = $( this );
			var opts = {
				eSelect: function( data ) {
					if( data && data.length ) {
						that.text( data[0].name );
						that.prop( 'value', data[0].id );
						that.parent().find( 'input[name^=sort]' ).prop( 'value', data[0].id );
						that.trigger( 'change' );
					};
				}
			}

			ui.selectMenu( that, opts );
		} );

		$( document ).on( "click", "#smw-search-forms", function(){

			var that = $( this );
			var opts = {
				label: 'Form',
				search: true,
				orderBy: 'name',
				eSelect: function( data ) {
					if( data && data.length ) {
						that.text( data[0].name );
						that.prop( 'value', data[0].id );
						that.parent().find( 'input[name^=smw-form]' ).prop( 'value', data[0].id );
						that.trigger( 'change' );
					};
				}
			}

			ui.selectMenu( that, opts );
		} );

		$( document ).ready( function() {

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

			/**
			 * Act on changes to the form select
			 */
			$( this ).on( "change", ".smw-select-field", function( event ) {
				var context = $( this ).closest( '.smw-input-group' );
				var length = $( '#smw-form-open .smw-input-group' ) ? $( '#smw-form-open .smw-input-group' ).length : 0;

				// Property input empty, make the value field appear to be disabled
				if ( context.find( '.smw-property-input' ).val() === '' ) {
					context.find( '.smw-propertyvalue-input' ).val( '' );
					context.find( '.smw-propertyvalue-input' ).addClass( 'is-disabled' );
				}

				// Act on the `del` request but only for as long as one group remains
				if ( length > 1 && context.find( '.smw-select-field' ).val() === 'del' ) {
					context.remove();
				}

				// Disable `del` when there are not enough groups available
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

				// A property was "really" selected! enable the value field
				if ( opts.suggestion && opts.suggestion.value !== '' ) {
					input.removeClass( 'is-disabled' );
					input.data( 'property', opts.suggestion.value );

					// Trigger event to initialize the instance
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
					$( "#smw-form-open > .smw-input-group > .smw-select-field option[value='del']" ).prop( "disabled", false );
					clone.find( '.smw-propertyvalue-input' ).addClass( 'is-disabled' );
					context.trigger( 'smw.autocomplete.property', { context: clone } );
				}
			} );

			var initialForm = $( '#smw-searchoptions' ).find( 'input[name^=smw-form]' ).prop( 'value' );

			/**
			 *  Listing to the form select field change
			 */
			$( this ).on( "change", "#smw-search-forms", function( event ) {

				var that = $( this );
				var type = $( '#smw-searchoptions' ).find( 'input[name^=smw-form]' ).prop( 'value' );
				var nsList = $( "#smw-search-forms" ).data( 'nslist' );

				if ( type !== '' ) {
					$( '#smw-searchoptions .divider' ).show();

					// Handle the hide/show of the NS section
					if ( $( '#ns-list' ).css( 'display' ) === 'block' && initialForm === '' ) {
						$( '#ns-list' ).css( 'display', 'none' );
						$( 'input[name=ns-list]' ).attr( 'value', 1 );
						initialForm = $( "#smw-search-forms select" ).val();
					}
				} else {
					$( '#smw-searchoptions .divider' ).hide();
				}

				// On any change, hide all fields by default and only make fields
				// visible that belong the selected form.
				$( '.smw-input-field' ).hide();
				$( '.smw-select-field' ).hide();
				$( '.smw-button-field' ).hide();
				$( '.smw-form-description' ).hide();

				// Important! The browser will complain about require fields that
				// have been made invisible.
				$( '.smw-input-field' ).find( 'input' ).prop( 'required', false );

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

	// Only load when it is Special:Search and the search type supports
	// https://www.semantic-mediawiki.org/wiki/Help:SMWSearch
	if ( mw.config.get( 'wgCanonicalSpecialPageName' ) == 'Search' && mw.config.get( 'wgSearchType' ) == 'SMWSearch' ) {
		smw.load( form );
	};

} )( jQuery, mediaWiki, semanticMediaWiki );
