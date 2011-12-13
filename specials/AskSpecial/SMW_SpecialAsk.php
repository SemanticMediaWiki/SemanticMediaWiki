<?php

/**
 * This special page for MediaWiki implements a customisable form for
 * executing queries outside of articles.
 *
 * @file SMW_SpecialAsk.php
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * @author Markus Krötzsch
 * @author Yaron Koren
 * @author Sanyam Goyal
 * @author Jeroen De Dauw
 *
 * TODO: Split up the megamoths into sane methods.
 */
class SMWAskPage extends SpecialPage {

	protected $m_querystring = '';
	protected $m_params = array();
	protected $m_printouts = array();
	protected $m_editquery = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( 'Ask' );
	}

	/**
	 * Main entrypoint for the special page.
	 *
	 * @param string $p
	 */
	public function execute( $p ) {
		global $wgOut, $wgRequest, $smwgQEnabled;

		$this->setHeaders();
		wfProfileIn( 'doSpecialAsk (SMW)' );

		if ( !$smwgQEnabled ) {
			$wgOut->addHTML( '<br />' . wfMsg( 'smw_iq_disabled' ) );
		} else {
			if ( $wgRequest->getCheck( 'showformatoptions' ) ) {
				// handle Ajax action
				$format = $wgRequest->getVal( 'showformatoptions' );
				$params = $wgRequest->getArray( 'params' );
				$wgOut->disable();
				echo $this->showFormatOptions( $format, $params );
			} else {
				$this->extractQueryParameters( $p );
				$this->makeHTMLResult();
			}
		}

		SMWOutputs::commitToOutputPage( $wgOut ); // make sure locally collected output data is pushed to the output!
		wfProfileOut( 'doSpecialAsk (SMW)' );
	}

	/**
	 * This code rather hacky since there are many ways to call that special page, the most involved of
	 * which is the way that this page calls itself when data is submitted via the form (since the shape
	 * of the parameters then is governed by the UI structure, as opposed to being governed by reason).
	 *
	 * @param string $p
	 */
	protected function extractQueryParameters( $p ) {
		global $wgRequest, $smwgQMaxInlineLimit;

		// First make all inputs into a simple parameter list that can again be parsed into components later.
		if ( $wgRequest->getCheck( 'q' ) ) { // called by own Special, ignore full param string in that case
			$query_val = $wgRequest->getVal( 'p' );

			if ( !empty( $query_val ) )
				// p is used for any additional parameters in certain links.
				$rawparams = SMWInfolink::decodeParameters( $query_val, false );
			else {
				$query_values = $wgRequest->getArray( 'p' );

				if ( is_array( $query_values ) ) {
					foreach ( $query_values as $key => $val ) {
						if ( empty( $val ) ) unset( $query_values[$key] );
					}
				}

				// p is used for any additional parameters in certain links.
				$rawparams = SMWInfolink::decodeParameters( $query_values, false );
			}
		} else { // called from wiki, get all parameters
			$rawparams = SMWInfolink::decodeParameters( $p, true );
		}

		// Check for q= query string, used whenever this special page calls itself (via submit or plain link):
		$this->m_querystring = $wgRequest->getText( 'q' );
		if ( $this->m_querystring !== '' ) {
			$rawparams[] = $this->m_querystring;
		}

		// Check for param strings in po (printouts), appears in some links and in submits:
		$paramstring = $wgRequest->getText( 'po' );

		if ( $paramstring !== '' ) { // parameters from HTML input fields
			$ps = explode( "\n", $paramstring ); // params separated by newlines here (compatible with text-input for printouts)

			foreach ( $ps as $param ) { // add initial ? if omitted (all params considered as printouts)
				$param = trim( $param );

				if ( ( $param !== '' ) && ( $param { 0 } != '?' ) ) {
					$param = '?' . $param;
				}

				$rawparams[] = $param;
			}
		}

		// Now parse parameters and rebuilt the param strings for URLs.
		SMWQueryProcessor::processFunctionParams( $rawparams, $this->m_querystring, $this->m_params, $this->m_printouts );

		// Try to complete undefined parameter values from dedicated URL params.
		if ( !array_key_exists( 'format', $this->m_params ) ) {
			$this->m_params['format'] = 'broadtable';
		}

		if ( !array_key_exists( 'order', $this->m_params ) ) {
			$order_values = $wgRequest->getArray( 'order' );

			if ( is_array( $order_values ) ) {
				$this->m_params['order'] = '';

				foreach ( $order_values as $order_value ) {
					if ( $order_value === '' ) $order_value = 'ASC';
					$this->m_params['order'] .= ( $this->m_params['order'] !== '' ? ',' : '' ) . $order_value;
				}
			}
		}

		$this->m_num_sort_values = 0;

		if  ( !array_key_exists( 'sort', $this->m_params ) ) {
			$sort_values = $wgRequest->getArray( 'sort' );
			if ( is_array( $sort_values ) ) {
				$this->m_params['sort'] = implode( ',', $sort_values );
				$this->m_num_sort_values = count( $sort_values );
			}
		}

		if ( !array_key_exists( 'offset', $this->m_params ) ) {
			$this->m_params['offset'] = $wgRequest->getVal( 'offset' );
			if ( $this->m_params['offset'] === '' )  $this->m_params['offset'] = 0;
		}

		if ( !array_key_exists( 'limit', $this->m_params ) ) {
			$this->m_params['limit'] = $wgRequest->getVal( 'limit' );

			if ( $this->m_params['limit'] === '' ) {
				 $this->m_params['limit'] = ( $this->m_params['format'] == 'rss' ) ? 10 : 20; // Standard limit for RSS.
			}
		}

		$this->m_params['limit'] = min( $this->m_params['limit'], $smwgQMaxInlineLimit );

		$this->m_editquery = ( $wgRequest->getVal( 'eq' ) == 'yes' ) || ( $this->m_querystring === '' );
	}

	/**
	 * Creates and adds the JavaScript and JS needed for autocompletion to $wgOut.
	 * Uses the MW API to get suggestions for properties.
	 * @TODO Test for non-english wikis, add caching on client to improve performance
	 * @since 1.5.2
	 */
	protected static function addAutocompletionJavascriptAndCSS() {
		global $wgOut, $smwgScriptPath, $smwgJQueryIncluded, $smwgJQueryUIIncluded;

		// Add CSS and JavaScript for jQuery and jQuery UI.
		$wgOut->addExtensionStyle( "$smwgScriptPath/skins/jquery-ui/base/jquery.ui.all.css" );

		$scripts = array();

		if ( !$smwgJQueryIncluded ) {
			$realFunction = array( $wgOut, 'includeJQuery' );
			if ( is_callable( $realFunction ) ) {
				$wgOut->includeJQuery();
			} else {
				$scripts[] = "$smwgScriptPath/libs/jquery-1.4.2.min.js";
			}

			$smwgJQueryIncluded = true;
		}

		if ( !$smwgJQueryUIIncluded ) {
			$scripts[] = "$smwgScriptPath/libs/jquery-ui/jquery.ui.core.min.js";
			$scripts[] = "$smwgScriptPath/libs/jquery-ui/jquery.ui.widget.min.js";
			$scripts[] = "$smwgScriptPath/libs/jquery-ui/jquery.ui.position.min.js";
			$scripts[] = "$smwgScriptPath/libs/jquery-ui/jquery.ui.autocomplete.min.js";
			$smwgJQueryUIIncluded = true;
		}

		foreach ( $scripts as $js ) {
			$wgOut->addScriptFile( $js );
		}

		$javascript_autocomplete_text = <<<END
<script type="text/javascript">
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
</script>

END;

		$wgOut->addScript( $javascript_autocomplete_text );
	}

	/**
	 * TODO: document
	 */
	protected function makeHTMLResult() {
		global $wgOut, $smwgAutocompleteInSpecialAsk;

		$delete_msg = wfMsg( 'delete' );

		// Javascript code for the dynamic parts of the page
		$javascript_text = <<<END
<script type="text/javascript">
function updateOtherOptions(strURL) {
	jQuery.ajax({ url: strURL, context: document.body, success: function(data){
		jQuery("#other_options").html(data);
	}});
}

// code for handling adding and removing the "sort" inputs
var num_elements = {$this->m_num_sort_values};

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
	remove_button.innerHTML = '[<a href="javascript:removeInstance(\'sort_div_' + num_elements + '\')">{$delete_msg}</a>]';
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
</script>

END;

		$wgOut->addScript( $javascript_text );

		if ( $smwgAutocompleteInSpecialAsk ) {
			self::addAutocompletionJavascriptAndCSS();
		}

		$result = '';
		$result_mime = false; // output in MW Special page as usual

		// build parameter strings for URLs, based on current settings
		$urlArgs['q'] = $this->m_querystring;

		$tmp_parray = array();
		foreach ( $this->m_params as $key => $value ) {
			if ( !in_array( $key, array( 'sort', 'order', 'limit', 'offset', 'title' ) ) ) {
				$tmp_parray[$key] = $value;
			}
		}

		$urlArgs['p'] = SMWInfolink::encodeParameters( $tmp_parray );
		$printoutstring = '';

		foreach ( $this->m_printouts as /* SMWPrintRequest */ $printout ) {
			$printoutstring .= $printout->getSerialisation() . "\n";
		}

		if ( $printoutstring !== '' ) $urlArgs['po'] = $printoutstring;
		if ( array_key_exists( 'sort', $this->m_params ) )  $urlArgs['sort'] = $this->m_params['sort'];
		if ( array_key_exists( 'order', $this->m_params ) ) $urlArgs['order'] = $this->m_params['order'];

		if ( $this->m_querystring !== '' ) {
			// FIXME: this is a hack
			SMWQueryProcessor::addThisPrintout( $this->m_printouts, $this->m_params );
			$params = SMWQueryProcessor::getProcessedParams( $this->m_params, $this->m_printouts );
			$this->m_params['format'] = $params['format'];

			$queryobj = SMWQueryProcessor::createQuery(
				$this->m_querystring,
				$params,
				SMWQueryProcessor::SPECIAL_PAGE ,
				$this->m_params['format'],
				$this->m_printouts
			);

			$res = smwfGetStore()->getQueryResult( $queryobj );

			// Try to be smart for rss/ical if no description/title is given and we have a concept query:
			if ( $this->m_params['format'] == 'rss' ) {
				$desckey = 'rssdescription';
				$titlekey = 'rsstitle';
			} elseif ( $this->m_params['format'] == 'icalendar' ) {
				$desckey = 'icalendardescription';
				$titlekey = 'icalendartitle';
			} else { $desckey = false; }

			if ( ( $desckey ) && ( $queryobj->getDescription() instanceof SMWConceptDescription ) &&
			     ( !isset( $this->m_params[$desckey] ) || !isset( $this->m_params[$titlekey] ) ) ) {
				$concept = $queryobj->getDescription()->getConcept();

				if ( !isset( $this->m_params[$titlekey] ) ) {
					$this->m_params[$titlekey] = $concept->getText();
				}

				if ( !isset( $this->m_params[$desckey] ) ) {
					// / @bug The current SMWStore will never return SMWConceptValue (an SMWDataValue) here; it might return SMWDIConcept (an SMWDataItem)
					$dv = end( smwfGetStore()->getPropertyValues( SMWWikiPageValue::makePageFromTitle( $concept ), new SMWDIProperty( '_CONC' ) ) );
					if ( $dv instanceof SMWConceptValue ) {
						$this->m_params[$desckey] = $dv->getDocu();
					}
				}
			}

			$printer = SMWQueryProcessor::getResultPrinter( $this->m_params['format'], SMWQueryProcessor::SPECIAL_PAGE );
			$result_mime = $printer->getMimeType( $res );

			global $wgRequest;

			$hidequery = $wgRequest->getVal( 'eq' ) == 'no';

			// if it's an export format (like CSV, JSON, etc.),
			// don't actually export the data if 'eq' is set to
			// either 'yes' or 'no' in the query string - just
			// show the link instead
			if ( $this->m_editquery || $hidequery ) $result_mime = false;

			if ( $result_mime == false ) {
				if ( $res->getCount() > 0 ) {
					if ( $this->m_editquery ) {
						$urlArgs['eq'] = 'yes';
					}
					elseif ( $hidequery ) {
						$urlArgs['eq'] = 'no';
					}

					$navigation = $this->getNavigationBar( $res, $urlArgs );
					$result .= '<div style="text-align: center;">' . "\n" . $navigation . "\n</div>\n";
					$query_result = $printer->getResult( $res, $params, SMW_OUTPUT_HTML );

					if ( is_array( $query_result ) ) {
						$result .= $query_result[0];
					} else {
						$result .= $query_result;
					}

					$result .= '<div style="text-align: center;">' . "\n" . $navigation . "\n</div>\n";
				} else {
					$result = '<div style="text-align: center;">' . wfMsgHtml( 'smw_result_noresults' ) . '</div>';
				}
			} else { // make a stand-alone file
				$result = $printer->getResult( $res, $params, SMW_OUTPUT_FILE );
				$result_name = $printer->getFileName( $res ); // only fetch that after initialising the parameters
			}
		}

		if ( $result_mime == false ) {
			if ( $this->m_querystring ) {
				$wgOut->setHTMLtitle( $this->m_querystring );
			} else {
				$wgOut->setHTMLtitle( wfMsg( 'ask' ) );
			}

			$urlArgs['offset'] = $this->m_params['offset'];
			$urlArgs['limit'] = $this->m_params['limit'];

			$result = $this->getInputForm(
				$printoutstring,
				wfArrayToCGI( $urlArgs )
			) . $result;

			$wgOut->addHTML( $result );
		} else {
			$wgOut->disable();

			header( "Content-type: $result_mime; charset=UTF-8" );

			if ( $result_name !== false ) {
				header( "content-disposition: attachment; filename=$result_name" );
			}

			echo $result;
		}
	}

	/**
	 * Generates the Search Box UI
	 *
	 * @param string $printoutstring
	 * @param string $urltail
	 *
	 * @return string
	 */
	protected function getInputForm( $printoutstring, $urltail ) {
		global $smwgQSortingSupport, $smwgResultFormats;

		$result = '';

		if ( $this->m_editquery ) {
			$spectitle = $this->getTitleFor( 'Ask' );
			$result .= '<form name="ask" action="' . htmlspecialchars( $spectitle->getLocalURL() ) . '" method="get">' . "\n" .
				'<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>';

			// Table for main query and printouts.
			$result .= '<table style="width: 100%; "><tr><th>' . wfMsg( 'smw_ask_queryhead' ) . "</th>\n<th>" . wfMsg( 'smw_ask_printhead' ) . "<br />\n" .
				'<span style="font-weight: normal;">' . wfMsg( 'smw_ask_printdesc' ) . '</span>' . "</th></tr>\n" .
				'<tr><td style="padding-right: 7px;"><textarea name="q" cols="20" rows="6">' . htmlspecialchars( $this->m_querystring ) . "</textarea></td>\n" .
				'<td style="padding-left: 7px;"><textarea id = "add_property" name="po" cols="20" rows="6">' . htmlspecialchars( $printoutstring ) . '</textarea></td></tr></table>' . "\n";
// @TODO
			// sorting inputs
			if ( $smwgQSortingSupport ) {
				if ( ! array_key_exists( 'sort', $this->m_params ) || ! array_key_exists( 'order', $this->m_params ) ) {
					$orders = array(); // do not even show one sort input here
				} else {
					$sorts = explode( ',', $this->m_params['sort'] );
					$orders = explode( ',', $this->m_params['order'] );
					reset( $sorts );
				}

				foreach ( $orders as $i => $order ) {
					$result .=  "<div id=\"sort_div_$i\">" . wfMsg( 'smw_ask_sortby' ) . ' <input type="text" name="sort[' . $i . ']" value="' .
						    htmlspecialchars( $sorts[$i] ) . "\" size=\"35\"/>\n" . '<select name="order[' . $i . ']"><option ';

					if ( $order == 'ASC' ) $result .= 'selected="selected" ';
					$result .=  'value="ASC">' . wfMsg( 'smw_ask_ascorder' ) . '</option><option ';
					if ( $order == 'DESC' ) $result .= 'selected="selected" ';

					$result .=  'value="DESC">' . wfMsg( 'smw_ask_descorder' ) . "</option></select>\n";
					$result .= '[<a href="javascript:removeInstance(\'sort_div_' . $i . '\')">' . wfMsg( 'delete' ) . '</a>]' . "\n";
					$result .= "</div>\n";
				}

				$result .=  '<div id="sorting_starter" style="display: none">' . wfMsg( 'smw_ask_sortby' ) . ' <input type="text" name="sort_num" size="35" />' . "\n";
				$result .= ' <select name="order_num">' . "\n";
				$result .= '	<option value="ASC">' . wfMsg( 'smw_ask_ascorder' ) . "</option>\n";
				$result .= '	<option value="DESC">' . wfMsg( 'smw_ask_descorder' ) . "</option>\n</select>\n";
				$result .= "</div>\n";
				$result .= '<div id="sorting_main"></div>' . "\n";
				$result .= '<a href="javascript:addInstance(\'sorting_starter\', \'sorting_main\')">' . wfMsg( 'smw_add_sortcondition' ) . '</a>' . "\n";
			}

			$printer = SMWQueryProcessor::getResultPrinter( 'broadtable', SMWQueryProcessor::SPECIAL_PAGE );
			$url = SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( "showformatoptions=' + this.value + '" );

			foreach ( $this->m_params as $param => $value ) {
				if ( $param !== 'format' ) {
					$url .= '&params[' . Xml::escapeJsString( $param ) . ']=' . Xml::escapeJsString( $value );
				}
			}

			$result .= "<br /><br />\n<p>" . wfMsg( 'smw_ask_format_as' ) . ' <input type="hidden" name="eq" value="yes"/>' . "\n" .
				'<select id="formatSelector" name="p[format]" onChange="JavaScript:updateOtherOptions(\'' . $url . '\')">' . "\n" .
				'	<option value="broadtable"' . ( $this->m_params['format'] == 'broadtable' ? ' selected' : '' ) . '>' .
				$printer->getName() . ' (' . wfMsg( 'smw_ask_defaultformat' ) . ')</option>' . "\n";

			$formats = array();

			foreach ( array_keys( $smwgResultFormats ) as $format ) {
				// Special formats "count" and "debug" currently not supported.
				if ( $format != 'broadtable' && $format != 'count' && $format != 'debug' ) {
					$printer = SMWQueryProcessor::getResultPrinter( $format, SMWQueryProcessor::SPECIAL_PAGE );
					$formats[$format] = $printer->getName();
				}
			}

			natcasesort( $formats );

			foreach ( $formats as $format => $name ) {
				$result .= '	<option value="' . $format . '"' . ( $this->m_params['format'] == $format ? ' selected' : '' ) . '>' . $name . "</option>\n";
			}

			$result .= "</select></p>\n";
			$result .= '<fieldset><legend>' . wfMsg( 'smw_ask_otheroptions' ) . "</legend>\n";
			$result .= "<div id=\"other_options\">" . $this->showFormatOptions( $this->m_params['format'], $this->m_params ) . "</div>";
			$result .= "</fieldset>\n";
			$urltail = str_replace( '&eq=yes', '', $urltail ) . '&eq=no'; // FIXME: doing it wrong, srysly

			$result .= '<br /><input type="submit" value="' . wfMsg( 'smw_ask_submit' ) . '"/>' .
				'<input type="hidden" name="eq" value="yes"/>' .
					Html::element(
						'a',
						array(
							'href' => SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( $urltail ),
							'rel' => 'nofollow'
						),
						wfMsg( 'smw_ask_hidequery' )
					) .
					'| ' . SMWAskPage::getEmbedToggle() .
					'| <a href="' . htmlspecialchars( wfMsg( 'smw_ask_doculink' ) ) . '">' . wfMsg( 'smw_ask_help' ) . '</a>' .
				"\n</form>";
		} else { // if $this->m_editquery == false
			$urltail = str_replace( '&eq=no', '', $urltail ) . '&eq=yes';
			$result .= '<p>' .
				Html::element(
					'a',
					array(
						'href' => SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( $urltail ),
						'rel' => 'nofollow'
					),
					wfMsg( 'smw_ask_editquery' )
				) .
				'| ' . SMWAskPage::getEmbedToggle() .
				'</p>';
		}
	//show|hide inline embed code
		$result .= '<div id="inlinequeryembed" style="display: none"><div id="inlinequeryembedinstruct">' . wfMsg( 'smw_ask_embed_instr' ) . '</div><textarea id="inlinequeryembedarea" readonly="yes" cols="20" rows="6" onclick="this.select()">' .
			'{{#ask:' . htmlspecialchars( $this->m_querystring ) . "\n";

		foreach ( $this->m_printouts as $printout ) {
			$result .= '|' . $printout->getSerialisation() . "\n";
		}

		foreach ( $this->m_params as $param_name => $param_value ) {
			$result .= '|' . htmlspecialchars( $param_name ) . '=' . htmlspecialchars( $param_value ) . "\n";
		}

		$result .= '}}</textarea></div><br />';

		return $result;
	}

	/**
	 * TODO: document
	 *
	 * @return string
	 */
	protected static function getEmbedToggle() {
		return '<span id="embed_show"><a href="#" rel="nofollow" onclick="' .
			"document.getElementById('inlinequeryembed').style.display='block';" .
			"document.getElementById('embed_hide').style.display='inline';" .
			"document.getElementById('embed_show').style.display='none';" .
			"document.getElementById('inlinequeryembedarea').select();" .
			'">' . wfMsg( 'smw_ask_show_embed' ) . '</a></span>' .
			'<span id="embed_hide" style="display: none"><a href="#" rel="nofollow" onclick="' .
			"document.getElementById('inlinequeryembed').style.display='none';" .
			"document.getElementById('embed_show').style.display='inline';" .
			"document.getElementById('embed_hide').style.display='none';" .
			'">' . wfMsg( 'smw_ask_hide_embed' ) . '</a></span>';
	}

	/**
	 * Build the navigation for some given query result, reuse url-tail parameters.
	 *
	 * @param SMWQueryResult $res
	 * @param array $urlArgs
	 *
	 * @return string
	 */
	protected function getNavigationBar( SMWQueryResult $res, array $urlArgs ) {
		global $smwgQMaxInlineLimit;

		$offset = $this->m_params['offset'];
		$limit  = $this->m_params['limit'];

		// Prepare navigation bar.
		if ( $offset > 0 ) {
			$navigation = Html::element(
				'a',
				array(
					'href' => SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( array(
						'offset' => max( 0, $offset - $limit ),
						'limit' => $limit
					) + $urlArgs ),
					'rel' => 'nofollow'
				),
				wfMsg( 'smw_result_prev' )
			);

		} else {
			$navigation = wfMsg( 'smw_result_prev' );
		}

		$navigation .=
			'&#160;&#160;&#160;&#160; <b>' .
				wfMsg( 'smw_result_results' ) . ' ' . ( $offset + 1 ) .
			'&#150; ' .
				( $offset + $res->getCount() ) .
			'</b>&#160;&#160;&#160;&#160;';

		if ( $res->hasFurtherResults() ) {
			$navigation .= Html::element(
				'a',
				array(
					'href' => SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( array(
						'offset' => ( $offset + $limit ),
						'limit' => $limit
					)  + $urlArgs ),
					'rel' => 'nofollow'
				),
				wfMsg( 'smw_result_next' )
			);
		} else {
			$navigation .= wfMsg( 'smw_result_next' );
		}

		$first = true;

		foreach ( array( 20, 50, 100, 250, 500 ) as $l ) {
			if ( $l > $smwgQMaxInlineLimit ) break;

			if ( $first ) {
				$navigation .= '&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;(';
				$first = false;
			} else {
				$navigation .= ' | ';
			}

			if ( $limit != $l ) {
				$navigation .= Html::element(
					'a',
					array(
						'href' => SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( array(
							'offset' => $offset,
							'limit' => $l
						) + $urlArgs ),
						'rel' => 'nofollow'
					),
					$l
				);
			} else {
				$navigation .= '<b>' . $l . '</b>';
			}
		}

		$navigation .= ')';

		return $navigation;
	}

	/**
	 * Display a form section showing the options for a given format,
	 * based on the getParameters() value for that format's query printer.
	 *
	 * @param string $format
	 * @param array $paramValues The current values for the parameters (name => value)
	 *
	 * @return string
	 */
	protected function showFormatOptions( $format, array $paramValues ) {
		$printer = SMWQueryProcessor::getResultPrinter( $format, SMWQueryProcessor::SPECIAL_PAGE );

		$params = SMWQueryProcessor::getParameters();

		if ( method_exists( $printer, 'getValidatorParameters' ) ) {
			$params = array_merge( $params, $printer->getValidatorParameters() );
		}

		$optionsHtml = array();

		foreach ( $params as $param ) {
			// Ignore the format parameter, as we got a special control in the GUI for it already.
			if ( $param->getName() == 'format' ) {
				continue;
			}

			$currentValue = array_key_exists( $param->getName(), $paramValues ) ? $paramValues[$param->getName()] : false;

			$optionsHtml[] =
				Html::rawElement(
					'div',
					array(
						'style' => 'width: 30%; padding: 5px; float: left;'
					),
					htmlspecialchars( $param->getName() ) . ': ' .
					$this->showFormatOption( $param, $currentValue ) .
					'<br />' .
					Html::element( 'em', array(), $param->getDescription() )
				);
		}

		for ( $i = 0, $n = count( $optionsHtml ); $i < $n; $i++ ) {
			if ( $i % 3 == 2 || $i == $n - 1 ) {
				$optionsHtml[$i] .= "<div style=\"clear: both\";></div>\n";
			}
		}

		$i = 0;
		$rowHtml = '';
		$resultHtml = '';

		while ( $option = array_shift( $optionsHtml ) ) {
			$rowHtml .= $option;
			$i++;

			$resultHtml .= Html::rawElement(
				'div',
				array(
					'style' => 'background: ' . ( $i % 6 == 0 ? 'white' : '#dddddd' ) . ';'
				),
				$rowHtml
			);

			$rowHtml = '';
		}

		return $resultHtml;
	}



	/**
	 * Get the HTML for a single parameter input.
	 *
	 * @since 1.6
	 *
	 * @param Parameter $parameter
	 * @param mixed $currentValue
	 *
	 * @return string
	 */
	protected function showFormatOption( Parameter $parameter, $currentValue ) {
		$input = new ParameterInput( $parameter );
		$input->setInputName( 'p[' . $parameter->getName() . ']' );

		if ( $currentValue !== false ) {
			$input->setCurrentValue( $currentValue );
		}

		return $input->getHtml();
	}

}
