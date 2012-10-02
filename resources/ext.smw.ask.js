/**
 * JavaScript for the Semantic MediaWiki extension.
 * @see https://www.mediawiki.org/wiki/Extension:Semantic_MediaWiki
 *
 * @licence GNU GPL v2+
 */

(function( $ ) {

	// code for handling adding and removing the "sort" inputs
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

	// Allows for collapsible fieldsets.
	// Based on the 'coolfieldset' jQuery plugin:
	// http://w3shaman.com/article/jquery-plugin-collapsible-fieldset
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

	$.fn.smwMakeCollapsible = function(options){
		var setting = { collapsed: options.collapsed, speed: 'medium' };
		$.extend(setting, options);

		this.each(function(){
			var fieldset = $(this);
			var legend = fieldset.children('legend');
			if ( setting.collapsed == true ) {
				legend.toggle(
					function(){
						smwShowFieldsetContent(fieldset, setting);
					},
					function(){
						smwHideFieldsetContent(fieldset, setting);
					}
				)
	
				smwHideFieldsetContent(fieldset, {animation:false});
			} else {
				legend.toggle(
					function(){
						smwHideFieldsetContent(fieldset, setting);
					},
					function(){
						smwShowFieldsetContent(fieldset, setting);
					}
				)
			}
		});
	}

	// Show tipsy tooltip with info retrieved from the data-info attribute
	$.fn.smwShowTooltip = function(){
		this.each(function(){
			$( this ).tipsy( {
					gravity: 'sw',
					//gravity: $.fn.tipsy.autoNS,
					//offset: 2,
					html: true,
					title: function() { return this.getAttribute( "data-info" ) ; }
				} );
		} );
	}

	$( document ).ready( function() {

		// Textarea property autocomplete
		// @see ext.smw.autocomplete
		$( '#add_property' ).smwAutocomplete( { separator: '\n' } );

		// Property autocomplete for the single sort field
		$( '.smw-ask-input-sort' ).smwAutocomplete();

		$( '.smw-ask-delete').click( function() {
			removeInstance( $( this).attr( 'data-target' ) );
		} );

		$( '.smw-ask-add').click( function() {
			addInstance( 'sorting_starter', 'sorting_main' );
		} );

		$( '#formatSelector' ).change( function() {
			// console.log($( this).attr( 'data-url' ).replace( 'this.value', $( this ).val() ));
			$.ajax( {
				// Evil hack to get more evil Spcial:Ask stuff to work with less evil JS.
				'url': $( this).attr( 'data-url' ).replace( 'this.value', $( this ).val() ),
				'context': document.body,
				'success': function( data ) {
					$( "#other_options" ).html( data );

					// Every change needs to re-initialize the tooltip
					$( ".smw-ask-info" ).smwShowTooltip();

					// Init new instance after format change
					$( '.smw-ask-input-sort' ).smwAutocomplete();

				}
			} );
		} );

		// Fieldset collapsible
		$( '.smw-ask-options' ).smwMakeCollapsible( {
			'collapsed' : mw.user.options.get( 'smw-ask-options-collapsed-default' )
		} );

		// Initialize tooltip for the default selection
		$( ".smw-ask-info" ).smwShowTooltip();

	});

	function updateOtherOptions(strURL) {
		debugger;

	}

})( window.jQuery );