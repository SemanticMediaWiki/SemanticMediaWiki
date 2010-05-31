<?php
/**
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */

/**
 * @author Markus Krötzsch
 * @author Yaron Koren
 *
 * This special page for MediaWiki implements a customisable form for
 * executing queries outside of articles.
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */
class SMWAskPage extends SpecialPage {

	protected $m_querystring = '';
	protected $m_params = array();
	protected $m_printouts = array();
	protected $m_editquery = false;

	// MW 1.13 compatibilty
	private static $pipeseparator = '|';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'Ask' );
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
	}

	function execute( $p ) {
		global $wgOut, $wgRequest, $smwgQEnabled, $smwgRSSEnabled, $smwgMW_1_14;
		$this->setHeaders();
		wfProfileIn( 'doSpecialAsk (SMW)' );
		if ( $smwgMW_1_14 ) { // since MW 1.14.0 this is governed by a message
			SMWAskPage::$pipeseparator = wfMsgExt( 'pipe-separator' , 'escapenoentities' );
		}
		if ( !$smwgQEnabled ) {
			$wgOut->addHTML( '<br />' . wfMsg( 'smw_iq_disabled' ) );
		} else {
			if ( $wgRequest->getCheck( 'showformatoptions' ) ) {
				// handle Ajax action
				$format = $wgRequest->getVal( 'showformatoptions' );
				$params = $wgRequest->getArray( 'params' );
				$wgOut->disable();
				print self::showFormatOptions( $format, $params );
			} else {
				$this->extractQueryParameters( $p );
				$this->makeHTMLResult();
			}
		}
		SMWOutputs::commitToOutputPage( $wgOut ); // make sure locally collected output data is pushed to the output!
		wfProfileOut( 'doSpecialAsk (SMW)' );
	}

	protected function extractQueryParameters( $p ) {
		// This code rather hacky since there are many ways to call that special page, the most involved of
		// which is the way that this page calls itself when data is submitted via the form (since the shape
		// of the parameters then is governed by the UI structure, as opposed to being governed by reason).
		global $wgRequest, $smwgQMaxInlineLimit;

		// First make all inputs into a simple parameter list that can again be parsed into components later.

		if ( $wgRequest->getCheck( 'q' ) ) { // called by own Special, ignore full param string in that case
			$query_values = $wgRequest->getArray( 'p' );
			$query_val = $wgRequest->getVal( 'p' );
			if ( ! empty( $query_val ) )
				$rawparams = SMWInfolink::decodeParameters( $query_val, false ); // p is used for any additional parameters in certain links
			else {
				$query_values = $wgRequest->getArray( 'p' );
				foreach ( $query_values as $key => $val ) {
					if ( empty( $val ) ) unset( $query_values[$key] );
				}
				$rawparams = SMWInfolink::decodeParameters( $query_values, false ); // p is used for any additional parameters in certain links
			}
		} else { // called from wiki, get all parameters
			$rawparams = SMWInfolink::decodeParameters( $p, true );
		}
		// Check for q= query string, used whenever this special page calls itself (via submit or plain link):
		$this->m_querystring = $wgRequest->getText( 'q' );
		if ( $this->m_querystring != '' ) {
			$rawparams[] = $this->m_querystring;
		}
		// Check for param strings in po (printouts), appears in some links and in submits:
		$paramstring = $wgRequest->getText( 'po' );
		if ( $paramstring != '' ) { // parameters from HTML input fields
			$ps = explode( "\n", $paramstring ); // params separated by newlines here (compatible with text-input for printouts)
			foreach ( $ps as $param ) { // add initial ? if omitted (all params considered as printouts)
				$param = trim( $param );
				if ( ( $param != '' ) && ( $param { 0 } != '?' ) ) {
					$param = '?' . $param;
				}
				$rawparams[] = $param;
			}
		}

		// Now parse parameters and rebuilt the param strings for URLs
		SMWQueryProcessor::processFunctionParams( $rawparams, $this->m_querystring, $this->m_params, $this->m_printouts );
		// Try to complete undefined parameter values from dedicated URL params
		if ( !array_key_exists( 'format', $this->m_params ) ) {
			if ( array_key_exists( 'rss', $this->m_params ) ) { // backwards compatibility (SMW<=1.1 used this)
				$this->m_params['format'] = 'rss';
			} else { // default
				$this->m_params['format'] = 'broadtable';
			}
		}
		if ( !array_key_exists( 'order', $this->m_params ) ) {
			$order_values = $wgRequest->getArray( 'order' );
			if ( is_array( $order_values ) ) {
				$this->m_params['order'] = '';
				foreach ( $order_values as $order_value ) {
					if ( $order_value == '' ) $order_value = 'ASC';
					$this->m_params['order'] .= ( $this->m_params['order'] != '' ? ',' : '' ) . $order_value;
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
		// Find implicit ordering for RSS -- needed for downwards compatibility with SMW <=1.1
		/*
		if ( ($this->m_params['format'] == 'rss') && ($this->m_params['sort'] == '') && ($sortcount==0)) {
			foreach ($this->m_printouts as $printout) {
				if ((strtolower($printout->getLabel()) == "date") && ($printout->getTypeID() == "_dat")) {
					$this->m_params['sort'] = $printout->getTitle()->getText();
					$this->m_params['order'] = 'DESC';
				}
			}
		}
		*/
		if ( !array_key_exists( 'offset', $this->m_params ) ) {
			$this->m_params['offset'] = $wgRequest->getVal( 'offset' );
			if ( $this->m_params['offset'] == '' )  $this->m_params['offset'] = 0;
		}
		if ( !array_key_exists( 'limit', $this->m_params ) ) {
			$this->m_params['limit'] = $wgRequest->getVal( 'limit' );
			if ( $this->m_params['limit'] == '' ) {
				 $this->m_params['limit'] = ( $this->m_params['format'] == 'rss' ) ? 10:20; // standard limit for RSS
			}
		}
		$this->m_params['limit'] = min( $this->m_params['limit'], $smwgQMaxInlineLimit );

		$this->m_editquery = ( $wgRequest->getVal( 'eq' ) == 'yes' ) || ( $this->m_querystring == '' );
	}

	protected function makeHTMLResult() {
		global $wgOut;
		$delete_msg = wfMsg( 'delete' );

		// Javascript code for the dynamic parts of the page
		$javascript_text = <<<END
<script type="text/javascript">
// a very small implementation of Ajax - copied from:
// http://www.degraeve.com/reference/simple-ajax-example.php
// - this can hopefully get replaced by jQuery at some point
function xmlhttpPost(strURL) {
    var xmlHttpReq = false;
    var self = this;
    // Mozilla/Safari
    if (window.XMLHttpRequest) {
        self.xmlHttpReq = new XMLHttpRequest();
    }
    // IE
    else if (window.ActiveXObject) {
        self.xmlHttpReq = new ActiveXObject("Microsoft.XMLHTTP");
    }
    self.xmlHttpReq.open('POST', strURL, true);
    self.xmlHttpReq.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    self.xmlHttpReq.onreadystatechange = function() {
        if (self.xmlHttpReq.readyState == 4) {
            updatepage(self.xmlHttpReq.responseText);
        }
    }
    self.xmlHttpReq.send(getquerystring());
}

function getquerystring() {
    var format_selector = document.getElementById('formatSelector');
    return format_selector.value;
}

function updatepage(str){
    document.getElementById("other_options").innerHTML = str;
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
		$result = '';
		$result_mime = false; // output in MW Special page as usual

		// build parameter strings for URLs, based on current settings
		$urltail = '&q=' . urlencode( $this->m_querystring );

		$tmp_parray = array();
		foreach ( $this->m_params as $key => $value ) {
			if ( !in_array( $key, array( 'sort', 'order', 'limit', 'offset', 'title' ) ) ) {
				$tmp_parray[$key] = $value;
			}
		}
		$urltail .= '&p=' . urlencode( SMWInfolink::encodeParameters( $tmp_parray ) );
		$printoutstring = '';
		foreach ( $this->m_printouts as $printout ) {
			$printoutstring .= $printout->getSerialisation() . "\n";
		}
		if ( $printoutstring != '' ) $urltail .= '&po=' . urlencode( $printoutstring );
		if ( array_key_exists( 'sort', $this->m_params ) )  $urltail .= '&sort=' . $this->m_params['sort'];
		if ( array_key_exists( 'order', $this->m_params ) ) $urltail .= '&order=' . $this->m_params['order'];

		if ( $this->m_querystring != '' ) {
			$queryobj = SMWQueryProcessor::createQuery( $this->m_querystring, $this->m_params, SMWQueryProcessor::SPECIAL_PAGE , $this->m_params['format'], $this->m_printouts );
			$res = smwfGetStore()->getQueryResult( $queryobj );
			// try to be smart for rss/ical if no description/title is given and we have a concept query:
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
					$dv = end( smwfGetStore()->getPropertyValues( SMWWikiPageValue::makePageFromTitle( $concept ), SMWPropertyValue::makeProperty( '_CONC' ) ) );
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
			if ( $this->m_editquery || $hidequery )
				$result_mime = false;
			if ( $result_mime == false ) {
				if ( $res->getCount() > 0 ) {
					if ( $this->m_editquery )
						$urltail .= '&eq=yes';
					if ( $hidequery )
						$urltail .= '&eq=no';
					$navigation = $this->getNavigationBar( $res, $urltail );
					$result .= '<div style="text-align: center;">' . "\n" . $navigation . "\n</div>\n";
					$query_result = $printer->getResult( $res, $this->m_params, SMW_OUTPUT_HTML );
					if ( is_array( $query_result ) ) {
						$result .= $query_result[0];
					} else {
						$result .= $query_result;
					}
					$result .= '<div style="text-align: center;">' . "\n" . $navigation . "\n</div>\n";
				} else {
					$result = '<div style="text-align: center;">' . wfMsg( 'smw_result_noresults' ) . '</div>';
				}
			} else { // make a stand-alone file
				$result = $printer->getResult( $res, $this->m_params, SMW_OUTPUT_FILE );
				$result_name = $printer->getFileName( $res ); // only fetch that after initialising the parameters
			}
		}

		if ( $result_mime == false ) {
			if ( $this->m_querystring ) {
				$wgOut->setHTMLtitle( $this->m_querystring );
			} else {
				$wgOut->setHTMLtitle( wfMsg( 'ask' ) );
			}
			$result = $this->getInputForm( $printoutstring, 'offset=' . $this->m_params['offset'] . '&limit=' . $this->m_params['limit'] . $urltail ) . $result;
			$wgOut->addHTML( $result );
		} else {
			$wgOut->disable();
			header( "Content-type: $result_mime; charset=UTF-8" );
			if ( $result_name !== false ) {
				header( "content-disposition: attachment; filename=$result_name" );
			}
			print $result;
		}
	}


	protected function getInputForm( $printoutstring, $urltail ) {
		global $wgUser, $smwgQSortingSupport, $wgLang, $smwgResultFormats;
		$skin = $wgUser->getSkin();
		$result = '';

		if ( $this->m_editquery ) {
			$spectitle = $this->getTitleFor( 'Ask' );
			$result .= '<form name="ask" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
			           '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>';

			// table for main query and printouts
			$result .= '<table style="width: 100%; "><tr><th>' . wfMsg( 'smw_ask_queryhead' ) . "</th>\n<th>" . wfMsg( 'smw_ask_printhead' ) . "<br />\n" .
				'<span style="font-weight: normal;">' . wfMsg( 'smw_ask_printdesc' ) . '</span>' . "</th></tr>\n" .
			         '<tr><td style="padding-right: 7px;"><textarea name="q" cols="20" rows="6">' . htmlspecialchars( $this->m_querystring ) . "</textarea></td>\n" .
			         '<td style="padding-left: 7px;"><textarea name="po" cols="20" rows="6">' . htmlspecialchars( $printoutstring ) . '</textarea></td></tr></table>' . "\n";

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
			$url = htmlspecialchars( $skin->makeSpecialUrl( 'Ask', "showformatoptions=\" + this.value + \"" ) );
			foreach ( $this->m_params as $param => $value ) {
				if ( $param !== 'format' )
					$url .= "&params[$param]=$value";
			}
			$result .= "<br /><br />\n<p>" . wfMsg( 'smw_ask_format_as' ) . ' <input type="hidden" name="eq" value="yes"/>' . "\n" .
				'<select id="formatSelector" name="p[format]" onChange=\'JavaScript:xmlhttpPost("' . $url . "\")'>\n" .
				'	<option value="broadtable"' . ( $this->m_params['format'] == 'broadtable' ? ' selected' : '' ) . '>' .
				$printer->getName() . ' (' . wfMsg( 'smw_ask_defaultformat' ) . ')</option>' . "\n";

			$formats = array();
			foreach ( array_keys( $smwgResultFormats ) as $format ) {
				if ( ( $format != 'broadtable' ) && ( $format != 'count' ) && ( $format != 'debug' ) ) { // special formats "count" and "debug" currently not supported
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
			$result .= "<div id=\"other_options\">" . self::showFormatOptions( $this->m_params['format'], $this->m_params ) . "</div>";
			$result .= "</fieldset>\n";
			$urltail = str_replace( '&eq=yes', '', $urltail ) . '&eq=no';

			$result .= '<br /><input type="submit" value="' . wfMsg( 'smw_ask_submit' ) . '"/>' .
				'<input type="hidden" name="eq" value="yes"/>' .
					' <a href="' . htmlspecialchars( $skin->makeSpecialUrl( 'Ask', $urltail ) ) . '" rel="nofollow">' . wfMsg( 'smw_ask_hidequery' ) . '</a> ' .
					SMWAskPage::$pipeseparator . ' ' . SMWAskPage::getEmbedToggle() .
					SMWAskPage::$pipeseparator .
					' <a href="' . htmlspecialchars( wfMsg( 'smw_ask_doculink' ) ) . '">' . wfMsg( 'smw_ask_help' ) . '</a>' .
				"\n</form>";
		} else { // if $this->m_editquery == false
			$urltail = str_replace( '&eq=no', '', $urltail ) . '&eq=yes';
			$result .= '<p><a href="' . htmlspecialchars( $skin->makeSpecialUrl( 'Ask', $urltail ) ) . '" rel="nofollow">' . wfMsg( 'smw_ask_editquery' ) . '</a> ' .
				SMWAskPage::$pipeseparator . ' ' . SMWAskPage::getEmbedToggle() . '</p>';
				'<input type="hidden" name="eq" value="yes"/>' .
					' <a href="' . htmlspecialchars( $skin->makeSpecialUrl( 'Ask', $urltail ) ) . '" rel="nofollow">' . wfMsg( 'smw_ask_hidequery' ) . '</a> ' .
					SMWAskPage::$pipeseparator . ' ' . SMWAskPage::getEmbedToggle() .
					SMWAskPage::$pipeseparator .
					' <a href="' . htmlspecialchars( wfMsg( 'smw_ask_doculink' ) ) . '">' . wfMsg( 'smw_ask_help' ) . '</a>' .
				"\n</form>";
		}

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

	private static function getEmbedToggle()
	{
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
	 * Build the navigation for some given query result, reuse url-tail parameters
	 */
	protected function getNavigationBar( $res, $urltail ) {
		global $wgUser, $smwgQMaxInlineLimit;
		$skin = $wgUser->getSkin();
		$offset = $this->m_params['offset'];
		$limit  = $this->m_params['limit'];
		// prepare navigation bar
		if ( $offset > 0 ) {
			$navigation = '<a href="' . htmlspecialchars( $skin->makeSpecialUrl( 'Ask', 'offset=' . max( 0, $offset - $limit ) . '&limit=' . $limit . $urltail ) ) . '" rel="nofollow">' . wfMsg( 'smw_result_prev' ) . '</a>';
		} else {
			$navigation = wfMsg( 'smw_result_prev' );
		}

		$navigation .= '&#160;&#160;&#160;&#160; <b>' . wfMsg( 'smw_result_results' ) . ' ' . ( $offset + 1 ) . '– ' . ( $offset + $res->getCount() ) . '</b>&#160;&#160;&#160;&#160;';

		if ( $res->hasFurtherResults() )
			$navigation .= ' <a href="' . htmlspecialchars( $skin->makeSpecialUrl( 'Ask', 'offset=' . ( $offset + $limit ) . '&limit=' . $limit . $urltail ) ) . '" rel="nofollow">' . wfMsg( 'smw_result_next' ) . '</a>';
		else $navigation .= wfMsg( 'smw_result_next' );

		$first = true;
		foreach ( array( 20, 50, 100, 250, 500 ) as $l ) {
			if ( $l > $smwgQMaxInlineLimit ) break;
			if ( $first ) {
				$navigation .= '&#160;&#160;&#160;&#160;&#160;&#160;&#160;&#160;(';
				$first = false;
			} else $navigation .= ' ' . SMWAskPage::$pipeseparator . ' ';
			if ( $limit != $l ) {
				$navigation .= '<a href="' . htmlspecialchars( $skin->makeSpecialUrl( 'Ask', 'offset=' . $offset . '&limit=' . $l . $urltail ) ) . '" rel="nofollow">' . $l . '</a>';
			} else {
				$navigation .= '<b>' . $l . '</b>';
			}
		}
		$navigation .= ')';
		return $navigation;
	}

	/**
	 * Display a form section showing the options for a given format,
	 * based on the getParameters() value for that format's query printer
	 */
	function showFormatOptions( $format, $param_values ) {
		$text = "";
		$printer = SMWQueryProcessor::getResultPrinter( $format, SMWQueryProcessor::SPECIAL_PAGE );
		if ( method_exists( $printer, 'getParameters' ) )
			$params = $printer->getParameters();
		else
			$params = array();
		foreach ( $params as $i => $param ) {
			$param_name = $param['name'];
			$type = $param['type'];
			$desc = $param['description'];
			$cur_value = ( array_key_exists( $param_name, $param_values ) ) ? $param_values[$param_name] : "";
			// 3 values per row, with alternating colors for rows
			if ( $i % 3 == 0 ) {
				$bgcolor = ( $i % 6 ) == 0 ? '#dddddd' : 'white';
				$text .= "<div style=\"background: $bgcolor;\">";
			}
			$text .= "<div style=\"width: 30%; padding: 5px; float: left;\">$param_name:\n";
			switch ( $type ) {
			case 'int':
				$text .= "<input type=\"text\" name=\"p[$param_name]\" size=\"6\" value=\"$cur_value\" />";
				break;
			case 'string':
				$text .= "<input type=\"text\" name=\"p[$param_name]\" size=\"32\" value=\"$cur_value\" />";
				break;
			case 'enumeration':
				$values = $param['values'];
				$text .= "<select name=\"p[$param_name]\">\n";
				$text .= "	<option value=''></option>\n";
				foreach ( $values as $val ) {
					if ( $cur_value == $val )
						$selected_str = 'selected';
					else
						$selected_str = '';
					$text .= "	<option value='$val' $selected_str>$val</option>\n";
				}
				$text .= "</select>";
				break;
			case 'enum-list':
				$all_values = $param['values'];
				$cur_values = explode( ',', $cur_value );
				foreach ( $all_values as $val ) {
					$checked_str = ( in_array( $val, $cur_values ) ) ? "checked" : "";
					$text .= "<span style=\"white-space: nowrap; padding-right: 5px;\"><input type=\"checkbox\" name=\"p[$param_name][$val]\" value=\"true\" $checked_str /> <tt>$val</tt></span>\n";
				}
				break;
			case 'boolean':
				$checked_str = ( array_key_exists( $param_name, $param_values ) ) ? 'checked' : '';
				$text .= "<input type=\"checkbox\" name=\"p[$param_name]\" value=\"true\" $checked_str />";
				break;
			}
			$text .= "\n	<br /><em>$desc</em>\n</div>\n";
			if ( $i % 3 == 2 || $i == count( $params ) - 1 ) {
				$text .= "<div style=\"clear: both\";></div></div>\n";
			}
		}
		return $text;
	}

}

