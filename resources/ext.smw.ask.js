/**
 * JavaScript for the Semantic MediaWiki extension.
 * @see https://www.mediawiki.org/wiki/Extension:Semantic_MediaWiki
 *
 * @licence GNU GPL v2+
 */

(function( $ ) {

	// code for handling adding and removing the "sort" inputs
	var num_elements = 1; // FIXME: this no longer works, and the code is beyond insane

	function addInstance(starter_div_id, main_div_id) {
		var starter_div = document.getElementById(starter_div_id);
		var main_div = document.getElementById(main_div_id);

		//Create the new instance
		var new_div = starter_div.cloneNode(true);
		var div_id = 'sort_div_' + num_elements;
		new_div.className = 'multipleTemplate';
		new_div.id = div_id;
		new_div.style.display = 'block';

		var children = new_div.getElementsByTagName('*');
		var x;
		for (x = 0; x < children.length; x++) {
			if (children[x].name)
				children[x].name = children[x].name.replace(/_num/, '[' + num_elements + ']');
		}

		//Create 'delete' link
		var remove_button = document.createElement('span');
		remove_button.innerHTML = '[<a href="javascript:removeInstance(\'sort_div_' + num_elements + '\')">Delete</a>]'; // FIXME: i18n
		new_div.appendChild(remove_button);

		//Add the new instance
		main_div.appendChild(new_div);
		num_elements++;
	}

	function removeInstance(div_id) {
		var olddiv = document.getElementById(div_id);
		var parent = olddiv.parentNode;
		parent.removeChild(olddiv);
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
	jQuery.ui.autocomplete.prototype._renderItem = function( ul, item) {
		var term_without_q = escapeQuestion(extractLast(this.term));
		var re = new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + term_without_q.replace("/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi", "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi");
		var loc = item.label.search(re);
		if (loc >= 0) {
			var t = item.label.substr(0, loc) + '<strong>' + item.label.substr(loc, term_without_q.length) + '</strong>' + item.label.substr(loc + term_without_q.length);
		} else {
			var t = item.label;
		}
		jQuery( "<li></li>" )
			.data( "item.autocomplete", item )
			.append( " <a>" + t + "</a>" )
			.appendTo( ul );
	};

	///* extending jquery functions for custom autocomplete matching */
	jQuery.extend( jQuery.ui.autocomplete, {
		filter: function(array, term) {
			var matcher = new RegExp("\\\b" + jQuery.ui.autocomplete.escapeRegex(term), "i" );
			return jQuery.grep( array, function(value) {
				return matcher.test( value.label || value.value || value );
			});
		}
	});

	jQuery(document).ready(function(){
		jQuery("#add_property").autocomplete({
			minLength: 2,
			source: function(request, response) {
				request.term=request.term.substr(request.term.lastIndexOf("\\n")+1);
				url=wgScriptPath+'/api.php?action=opensearch&limit=10&namespace='+wgNamespaceIds['property']+'&format=jsonfm&search=';

				jQuery.getJSON(url+request.term, function(data){
					//remove the namespace prefix 'Property:' from returned data and add prefix '?'
					for(i=0;i<data[1].length;i++) data[1][i]="?"+data[1][i].substr(data[1][i].indexOf(':')+1);
					response(jQuery.ui.autocomplete.filter(data[1], escapeQuestion(extractLast(request.term))));
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
	});

	function updateOtherOptions(strURL) {
		jQuery.ajax({ url: strURL, context: document.body, success: function(data){
			jQuery("#other_options").html(data);
		}});
	}

})( window.jQuery );