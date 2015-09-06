/**
 * This file is part of the Semantic MediaWiki Special:Ask module
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
 * @file
 * @ignore
 *
 * @since 1.9
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author: Jeroen De Dauw <jeroendedauw at gmail dot com>
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * Support and helper methods
	 * @ignore
	 */
	var tooltip = new smw.util.tooltip();

	var _init = {

		// Autocomplete
		autocomplete: {
			textarea: function(){
				// Textarea property autocomplete
				// @see ext.smw.autocomplete
				$( '#add_property' ).smwAutocomplete( { separator: '\n' } );
			},
			parameter: function(){
				// Property autocomplete for the single sort field
				$( '.smw-ask-input-sort' ).smwAutocomplete();
			}
		},

		// Tooltip
		tooltip: function(){
			$( '.smw-ask-info' ).each( function(){
				tooltip.show( {
					context: $( this ),
					content: $( this ).data( 'info' ),
					title  : mw.msg( 'smw-ui-tooltip-title-parameter' ),
					button : false
				} );
			} );
		},

		// Format help link
		formatHelp: function( options ){
			// Make sure we don't have a pre existing element, using id as selector
			// as it is faster compared to the class selector
			$( '#formatHelp' ).remove();
			$( options.selector ).after( '<span id="formatHelp" class="smw-ask-format-selection-help">' + mw.msg( 'smw-ask-format-selection-help', addFormatHelpLink( options ) ) + '</span>' );
		}
	};

	/**
	 * Add format help link
	 *
	 * We do not try to be smart here but using a pragmatic approach to generate
	 * the URL by assuming Help:<format> format
	 *
	 * @return object
	 */
	function addFormatHelpLink ( options ){
		var h = mw.html,
			link = h.element( 'a', {
					href: 'http://semantic-mediawiki.org/wiki/Help:' + options.format + ' format',
					title: options.name
				}, options.name
			);
		return link;
	}

	/**
	 * Multiple sorting
	 * Code for handling adding and removing the "sort" inputs
	 *
	 * @TODO Something don't quite work here but it is broken from the beginning therefore ...
	 */
	var num_elements = $( '#sorting_main > div' ).length;

	function addInstance(starter_div_id, main_div_id) {
		num_elements++;

		var starter_div = $( '#' + starter_div_id),
		main_div = $( '#' + main_div_id),
		new_div = starter_div.clone();

		new_div.attr( {
			'class': 'multipleTemplate',
			'id': 'sort_div_' + num_elements
		} );

		new_div.css( 'display', 'block' );

		//Create 'delete' link
		var button = $( '<a>').attr( {
			'href': '#',
			'class': 'smw-ask-delete'
		} ).text( mw.msg( 'smw-ask-delete' ) );

		button.click( function() {
			removeInstance( 'sort_div_' + num_elements );
		} );

		new_div.append(
			$( '<span>' ).html( button )
		);

		//Add the new instance
		main_div.append( new_div );
	}

	function removeInstance(div_id) {
		$( '#' + div_id ).remove();
	}

	/**
	 * Collapsible fieldsets
	 * Based on the 'coolfieldset' jQuery plugin:
	 * http://w3shaman.com/article/jquery-plugin-collapsible-fieldset
	 *
	 */
	function smwHideFieldsetContent(obj, options){
		obj.find( 'div' ).slideUp(options.speed);
		obj.find( '.collapsed-info' ).slideDown(options.speed);
		obj.removeClass( "smwExpandedFieldset" );
		obj.addClass( "smwCollapsedFieldset" );
	}

	function smwShowFieldsetContent(obj, options){
		obj.find( 'div' ).slideDown(options.speed);
		obj.find( '.collapsed-info' ).slideUp(options.speed);
		obj.removeClass( "smwCollapsedFieldset" );
		obj.addClass( "smwExpandedFieldset" );
	}

	////////////////////////// PUBLIC METHODS ////////////////////////

	$.fn.smwMakeCollapsible = function(options){
		var setting = { collapsed: options.collapsed, speed: 'medium' };
		$.extend(setting, options);

		this.each(function(){
			var fieldset = $(this);
			var legend = fieldset.children('legend');
			if ( setting.collapsed ) {
				legend.toggle(
					function(){
						smwShowFieldsetContent(fieldset, setting);
					},
					function(){
						smwHideFieldsetContent(fieldset, setting);
					}
				);

				smwHideFieldsetContent(fieldset, {animation:false});
			} else {
				legend.toggle(
					function(){
						smwHideFieldsetContent(fieldset, setting);
					},
					function(){
						smwShowFieldsetContent(fieldset, setting);
					}
				);
			}
		});
	};

	/**
	 * Implementation of an Special:Ask instance
	 * @since 1.8
	 * @ignore
	 */
	$( document ).ready( function() {

		// Get initial format and language settings
		var selected = $( '#formatSelector option:selected' ),
			options = {
				selector : '#formatSelector',
				format : selected.val(),
				name : selected.text()
			};

		// Init
		_init.autocomplete.textarea();
		_init.autocomplete.parameter();
		_init.tooltip();
		_init.formatHelp( options );

		// Fieldset collapsible
		$( '.smw-ask-options' ).smwMakeCollapsible( {
			'collapsed' : mw.user.options.get( 'smw-prefs-ask-options-collapsed-default' )
		} );

		// Multiple sorting
		$( '.smw-ask-delete').click( function() {
			removeInstance( $( this).attr( 'data-target' ) );
		} );

		$( '.smw-ask-add').click( function() {
			addInstance( 'sorting_starter', 'sorting_main' );
		} );

		// Change format parameter form via ajax
		$( '#formatSelector' ).change( function() {
			var $this = $( this );

			$.ajax( {
				// Evil hack to get more evil Spcial:Ask stuff to work with less evil JS.
				'url': $this.data( 'url' ).replace( 'this.value',  $this.val() ),
				'context': document.body,
				'success': function( data ) {
					$( '#other_options' ).html( data );

					// Reinitialize functions after each ajax request
					_init.autocomplete.parameter();
					_init.tooltip();

					// Update format created by the ajax instance
					_init.formatHelp( $.extend( {}, options, {
						format:  $this.val(),
						name: $this.find( 'option:selected' ).text()
					} ) );
				}
			} );
		} );
	} );
} )( jQuery, mediaWiki, semanticMediaWiki );