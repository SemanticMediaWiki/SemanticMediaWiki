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
		} ).text( mw.msg( 'smw-ask-delete' ) ); // TODO: i18n, def && pass message

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

	function split(val) {
		return val.split('\\n');
	}
	function extractLast(term) {
		return split(term).pop();
	}
	function escapeQuestion(term){
		if (term.substring(0, 1) == "?") {
			return term.substring(1);
		} else {
			return term;
		}
	}

	/* extending jQuery functions for custom highligting */
	$.ui.autocomplete.prototype._renderItem = function( ul, item) {
		var term_without_q = escapeQuestion(extractLast(this.term));
		var re = new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + term_without_q.replace("/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi", "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi");
		var loc = item.label.search(re);
		if (loc >= 0) {
			var t = item.label.substr(0, loc) + '<strong>' + item.label.substr(loc, term_without_q.length) + '</strong>' + item.label.substr(loc + term_without_q.length);
		} else {
			var t = item.label;
		}
		$( "<li></li>" )
			.data( "item.autocomplete", item )
			.append( " <a>" + t + "</a>" )
			.appendTo( ul );
	};

	///* extending jquery functions for custom autocomplete matching */
	$.extend( $.ui.autocomplete, {
		filter: function(array, term) {
			var matcher = new RegExp("\\\b" + $.ui.autocomplete.escapeRegex(term), "i" );
			return $.grep( array, function(value) {
				return matcher.test( value.label || value.value || value );
			});
		}
	});

	$( document ).ready( function() {

		$("#add_property").autocomplete({
			minLength: 2,
			source: function(request, response) {
				request.term=request.term.substr(request.term.lastIndexOf("\\n")+1);
				url=wgScriptPath+'/api.php?action=opensearch&limit=10&namespace='+wgNamespaceIds['property']+'&format=jsonfm&search=';

				$.getJSON(url+request.term, function(data){
					//remove the namespace prefix 'Property:' from returned data and add prefix '?'
					for(i=0;i<data[1].length;i++) data[1][i]="?"+data[1][i].substr(data[1][i].indexOf(':')+1);
					response($.ui.autocomplete.filter(data[1], escapeQuestion(extractLast(request.term))));
				});
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function(event, ui) {
				var terms = split( this.value );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.value );
				// add placeholder to get the comma-and-space at the end
				terms.push("");
				this.value = terms.join("\\n");
				return false;
			}
		});

		$( '.smw-ask-delete').click( function() {
			removeInstance( $( this).attr( 'data-target' ) );
		} );

		$( '.smw-ask-add').click( function() {
			addInstance( 'sorting_starter', 'sorting_main' );
		} );

		$( '#formatSelector' ).change( function() {
			console.log($( this).attr( 'data-url' ).replace( 'this.value', $( this ).val() ));
			$.ajax( {
				// Evil hack to get more evil Spcial:Ask stuff to work with less evil JS.
				'url': $( this).attr( 'data-url' ).replace( 'this.value', $( this ).val() ),
				'context': document.body,
				'success': function( data ) {
					$( "#other_options" ).html( data );
				}
			} );
		} );

	});

	function updateOtherOptions(strURL) {
		debugger;

	}

})( window.jQuery );