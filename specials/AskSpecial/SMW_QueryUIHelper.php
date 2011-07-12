<?php

/**
 * A base class for Semantic Search UIs. All Semantic Search UI's may subclass
 * from this.
 *
 * @author Markus Krötzsch
 * @author Yaron Koren
 * @author Sanyam Goyal
 * @author Jeroen De Dauw
 * @author Devayon Das
 */
abstract class SMWQueryUI extends SpecialPage {
	protected $m_ui_helper;
	private $autocompleteenabled = false;
	const ENABLE_AUTO_SUGGEST = true;
	const DISABLE_AUTO_SUGGEST = false;

	protected function addAutocompletionJavascriptAndCSS() {
		global $wgOut, $smwgScriptPath, $smwgJQueryIncluded, $smwgJQueryUIIncluded;
		if ( $this->autocompleteenabled == false ) {
			$wgOut->addExtensionStyle( "$smwgScriptPath/skins/jquery-ui/base/jquery.ui.all.css" );

			$scripts = array();

			if ( !$smwgJQueryIncluded ) {
				$realFunction = array( 'OutputPage', 'includeJQuery' );
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

jQuery.noConflict();
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
</script>
END;

			$wgOut->addScript( $javascript_autocomplete_text );
			$this->autocompleteenabled = true;
		}
	}

	protected function makeRes( $p ) {
		/*
		 * TODO: extract parameters from $p and decide:
		 * (1) if form elements need to be displayed
		 * (2) if any results need to be displayed
		 * (3) which factory method of UIhelper should be called
		 * Most of the code here in this method will anyway be removed later
		 */
		global $wgOut, $wgRequest;
		$htmloutput = "";
		$htmloutput .= $this->getForm();
		$param = array();

		$this->m_ui_helper = $helper = new SMWQueryUIHelper; // or some factory method
		// here come some driver lines for testing; this is very temporary

		//     form parameters                               default values
		$helper->setQueryString(
			$wgRequest->getVal( 'q',                    '[[Located in:: Germany]]' ) );
		$helper->setParams( array(
			'format'  =>  $wgRequest->getVal( 'format',	'ol' ),
			'offset'  =>  $wgRequest->getVal( 'offset',  '0'  ),
			'limit'   =>  $wgRequest->getVal( 'limit',   '20' )
			) );
		$helper->setPrintOuts( array( '?Population' ) );
		$helper->extractParameters( $p );

		$helper->execute();

		if ( $this->usesNavigationBar() ) {
			$htmloutput .= $this->getNavigationBar ( $helper->getLimit(), $helper->getOffset(), $helper->hasFurtherResults() ); // ? can we preload offset and limit?
		}

		$htmloutput .= $helper->getHTMLResult();

		if ( $this->usesNavigationBar() ) {
			$htmloutput .= $this->getNavigationBar ( $helper->getLimit(), $helper->getOffset(), $helper->hasFurtherResults() ); // ? can we preload offset and limit?
		}
		$wgOut->addHTML( $htmloutput );
	}

	/**
	 * Build the navigation bar for some given query result.
	 *
	 * UI may overload this for a different layout. The navigation bar
	 * can be hidden by overloading usesNavigationBar(). To change the url format,
	 * one may overload getUrlTail();
	 *
	 * @global int $smwgQMaxInlineLimit
	 * @param int $limit
	 * @param int $offset
	 * @param boolean $has_further_results
	 *
	 * @return string
	 */
	public function getNavigationBar( $limit, $offset, $has_further_results ) {
		global $smwgQMaxInlineLimit;
		$urltail = $this->getUrlTail();
		// Prepare navigation bar.
		if ( $offset > 0 ) {
			$navigation = Html::element(
				'a',
				array(
					'href' => $this->getTitle()->getLocalURL(
						'offset=' . max( 0, $offset - $limit ) .
						'&limit=' . $limit . $urltail
					),
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
				( $offset + $this->m_ui_helper->getResultCount() ) .
			'</b>&#160;&#160;&#160;&#160;';

		if ( $has_further_results ) {
			$navigation .= Html::element(
				'a',
				array(
					'href' => $this->getTitle()->getLocalURL(
						'offset=' . ( $offset + $limit ) .
						'&limit=' . $limit . $urltail
					),
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
						'href' => $this->getTitle()->getLocalURL(
							'offset=' . $offset .
							'&limit=' . $l . $urltail
						),
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
	 * Creates the form elements and populates them with parameters.
	 * UI implementations need to overload this if a different layout and form
	 * elements are desired
	 *
	 * @return string Form elements in HTML
	 */
	protected function getForm() {
		/*
		 * Although the following methods will retuen form elements, which can
		 * then be placed in wOut as pleased, they will
		 * also write javascript (if relevant) directly to wgOut.
		 */

		// $result="";
		// $result.= getQueryFormBox($contents, $errors);
		// $result.= getPOFormBox($content, $enableAutoComplete);
		// $result.= getParamBox($content); //avoid ajax, load form elements in the UI by default
		$result = "<br>Stub: The Form elements come here<br><br>";
		return $result;
	}
	protected function makeHtmlResult() {
		// STUB
	}

	/**
	 * A method which generates the form element(s) for the Query-string.  Use its
	 * complement processQueryFormBox() to decode data sent through these elements.
	 * UI's may overload both to change form parameters.
	 *
	 * @param string $contents
	 * @param string $errors
	 * @return string
	 */
	protected function getQueryFormBox( $content, $errors = "" ) {
		$result = "";
		$result = Html::element( 'textarea', array( 'name' => 'q', 'id' => 'querybox', 'rows' => '6' ), $content );
		// TODO:enable/disable on checking for errors; perhaps show error messages right below the box
		return $result;
	}

	/**
	 * A method which decodes form data sent through form-elements generated by
	 * its complement, getQueryFormBox. UIs may overload both to change form parameters.
	 *
	 * @param WebRequest $wgRequest
	 * @return string
	 */
	protected function processQueryFormBox( WebRequest $wgRequest ) {
		$query = "";
		if ( $wgRequest->getCheck( 'q' ) ) $query = $wgRequest->getVal( 'q' );
		return $query;
	}

	/**
	 * A method which generates the form element(s) for PrintOuts.
	 * Use its complement processPOFormBox() to decode data sent through these
	 * form elements. UIs may overload both to change the form parameter or the html elements.
	 *
	 * @param string $content The content expected to appear in the box
	 * @param boolean $enableAutocomplete If set to true, adds the relevant JS and CSS to the page
	 * @return string The HTML code
	 */
	protected function getPOFormBox( $content, $enableAutocomplete = SMWQueryUI::ENABLE_AUTO_SUGGEST ) {
		if ( $enableAutocomplete ) {
			global $wgOut;

			$this->addAutocompletionJavascriptAndCSS();
			$javascript_autocomplete_text = <<<EOT
<script type="text/javascript">
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
EOT;

			$wgOut->addScript( $javascript_autocomplete_text );

		}
		$result = "";
		$result = Html::element( 'textarea', array( 'id' => 'add_property', 'name' => 'po', 'cols' => '20', 'rows' => '6' ), $content );
		return $result;
	}

	/**
	 * A method which decodes form data sent through form-elements generated by
	 * its complement, getPOFormBox(). UIs may overload both to change form parameters.
	 *
	 * @param WebRequest $wgRequest
	 * @return array
	 */
	protected function processPOFormBox( WebRequest $wgRequest ) {
		$postring = $wgRequest->getText( 'po' );
		$poarray = array();

		if ( $postring != '' ) { // parameters from HTML input fields
			$ps = explode( "\n", $postring ); // params separated by newlines here (compatible with text-input for printouts)

			foreach ( $ps as $param ) { // add initial ? if omitted (all params considered as printouts)
				$param = trim( $param );

				if ( ( $param != '' ) && ( $param[0] != '?' ) ) {
					$param = '?' . $param;
				}

				$poarray[] = $param;
			}
		}
		return $poarray;
	}

	/**
	 * A method which generates the url parameters based on passed parameters.
	 * UI implementations need to overload this if they use different form parameters.
	 *
	 * @return string An url-encoded string.
	 */
	protected function getUrlTail() {
		$urltail = '&q=' . urlencode( $this->m_ui_helper->getQuerystring() );
		$tmp_parray = array();
		$params = $this->m_ui_helper->getParams();
		foreach ( $params as $key => $value ) {
			if ( !in_array( $key, array( 'sort', 'order', 'limit', 'offset', 'title' ) ) ) {
				$tmp_parray[$key] = $value;
			}
		}

		$urltail .= '&p=' . urlencode( SMWInfolink::encodeParameters( $tmp_parray ) );
		$printoutstring = '';
		foreach ( $this->m_ui_helper->getPrintOuts() as $printout ) {
			$printoutstring .= $printout->getSerialisation() . "\n";
		}

		if ( $printoutstring != '' ) $urltail .= '&po=' . urlencode( $printoutstring );
		if ( array_key_exists( 'sort', $params ) )  $urltail .= '&sort=' . $params['sort'];
		if ( array_key_exists( 'order', $params ) ) $urltail .= '&order=' . $params['order'];
		return $urltail;
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
		$text = '';

		$printer = SMWQueryProcessor::getResultPrinter( $format, SMWQueryProcessor::SPECIAL_PAGE );

		$params = method_exists( $printer, 'getParameters' ) ? $printer->getParameters() : array();

		// Ignore the format parameter, as we got a special control in the GUI for it already.
		unset( $params['format'] );

		$optionsHtml = array();

		foreach ( $params as $param ) {
			$param = $this->toValidatorParam( $param );
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

			if ( $i % 3 == 0 ) {
				$resultHtml .= Html::rawElement(
					'div',
					array(
						'style' => 'background: ' . ( $i % 6 == 0 ? 'white' : '#dddddd' ) . ';'
					),
					$rowHtml
				);
				$rowHtml = '';
			}
		}

		return $resultHtml;
	}

	/**
	 * Returns a Validator style Parameter definition.
	 * SMW 1.5.x style definitions are converted.
	 *
	 * @param mixed $param
	 *
	 * @return Parameter
	 */
	private function toValidatorParam( $param ) {
		static $typeMap = array(
			'int' => Parameter::TYPE_INTEGER
		);

		if ( !( $param instanceof Parameter ) ) {
			if ( !array_key_exists( 'type', $param ) ) {
				$param['type'] = 'string';
			}

			$paramClass = $param['type'] == 'enum-list' ? 'ListParameter' : 'Parameter';
			$paramType = array_key_exists( $param['type'], $typeMap ) ? $typeMap[$param['type']] : Parameter::TYPE_STRING;

			$parameter = new $paramClass( $param['name'], $paramType );

			if ( array_key_exists( 'description', $param ) ) {
				$parameter->setDescription( $param['description'] );
			}

			if ( array_key_exists( 'values', $param ) && is_array( $param['values'] ) ) {
				$parameter->addCriteria( new CriterionInArray( $param['values'] ) );
			}

			return $parameter;
		}
		else {
			return $param;
		}
	}

	/**
	 * Get the HTML for a single parameter input.
	 *
	 * @param Parameter $parameter
	 * @param mixed $currentValue
	 *
	 * @return string
	 */
	private function showFormatOption( Parameter $parameter, $currentValue ) {
		$input = new ParameterInput( $parameter );
		$input->setInputName( 'p[' . $parameter->getName() . ']' );

		if ( $currentValue !== false ) {
			$input->setCurrentValue( $currentValue );
		}

		return $input->getHtml();
	}

	/**
	 * Creates form elements for choosing the result-format and their associated
	 * format. Use in conjunction with processFormatOptions() to supply formats
	 * options using ajax. Also, use its complement processFormatSelectBox() to
	 * decode form data sent by these elements. UI's may overload these methods
	 * to change behaviour or form parameters.
	 *
	 * @param string $defaultformat The default format which remains selected in the form
	 * @return string
	 */
	protected function getFormatSelectBox( $defaultformat = 'broadtable' ) {

		global $smwgResultFormats, $smwgJQueryIncluded, $wgOut;

		if ( !$smwgJQueryIncluded ) {
				$realFunction = array( 'OutputPage', 'includeJQuery' );
				if ( is_callable( $realFunction ) ) {
					$wgOut->includeJQuery();
				} else {
					$scripts[] = "$smwgScriptPath/libs/jquery-1.4.2.min.js";
				}
				$smwgJQueryIncluded = true;
		}

		// checking argument
		$default_format = 'broadtable';
		if ( array_key_exists( $defaultformat, $smwgResultFormats ) ) {
			$default_format = $defaultformat;
		}

		$result = "";
		$printer = SMWQueryProcessor::getResultPrinter( $default_format, SMWQueryProcessor::SPECIAL_PAGE );
		$url = $this->getTitle()->getLocalURL( "showformatoptions=' + this.value + '" );

		foreach ( $this->m_ui_helper->getParams() as $param => $value ) {
			if ( $param !== 'format' ) {
				$url .= '&params[' . Xml::escapeJsString( $param ) . ']=' . Xml::escapeJsString( $value );
			}
		}

		$result .= "\n<p>" . wfMsg( 'smw_ask_format_as' ) . "\n" .
				'<select id="formatSelector" name="p[format]" onChange="JavaScript:updateOtherOptions(\'' . $url . '\')">' . "\n" .
				'	<option value="' . $default_format . '">' . $printer->getName() . ' (' . wfMsg( 'smw_ask_defaultformat' ) . ')</option>' . "\n";

		$formats = array();

		foreach ( array_keys( $smwgResultFormats ) as $format ) {
			// Special formats "count" and "debug" currently not supported.
			if ( $format != $default_format && $format != 'count' && $format != 'debug' ) {
				$printer = SMWQueryProcessor::getResultPrinter( $format, SMWQueryProcessor::SPECIAL_PAGE );
				$formats[$format] = $printer->getName();
			}
		}

		natcasesort( $formats );
		$params = $this->m_ui_helper->getParams();
		foreach ( $formats as $format => $name ) {
			$result .= '	<option value="' . $format . '"' . ( $params['format'] == $format ? ' selected' : '' ) . '>' . $name . "</option>\n";
		}
		$result .= "</select>";
		$result .= "</p>\n";
		$result .= '<fieldset><legend>' . wfMsg( 'smw_ask_otheroptions' ) . "</legend>\n";
		$result .= "<div id=\"other_options\">" . $this->showFormatOptions( $params['format'], $params ) . " </div>";
		$result .= "</fieldset>\n";

		// BEGIN: add javascript for updating formating options by ajax
		global $wgOut;
		$javascript = <<<END
<script type="text/javascript">
function updateOtherOptions(strURL) {
	jQuery.ajax({ url: strURL, context: document.body, success: function(data){
		jQuery("#other_options").html(data);
	}});
}
</script>
END;

		$wgOut->addScript( $javascript );
		// END: add javascript for updating formating options by ajax

		return $result;
	}

	/**
	 * A method which decodes form data sent through form-elements generated by
	 * its complement, getFormatSelectBox(). UIs may overload both to change form parameters.
	 *
	 * @param WebRequest $wgRequest
	 * @return array
	 */
	protected function processFormatSelectBox( WebRequest $wgRequest ) {
		$query_val = $wgRequest->getVal( 'p' );
		if ( !empty( $query_val ) )
			$params = SMWInfolink::decodeParameters( $query_val, false );
		else {
			$query_values = $wgRequest->getArray( 'p' );

			if ( is_array( $query_values ) ) {
				foreach ( $query_values as $key => $val ) {
					if ( empty( $val ) ) unset( $query_values[$key] );
				}
			}

				// p is used for any additional parameters in certain links.
				$params = SMWInfolink::decodeParameters( $query_values, false );
		}
		return $params;
	}

	/**
	 * Generates form elements for a (web)requested format.
	 *
	 * Required by getFormatSelectBox() to recieve form elements from the web.
	 * UIs may need to overload processFormatOptions(), getgetFormatSelectBox()
	 * and getFormatSelectBox() to change behavior.
	 *
	 * @param WebRequest $wgRequest
	 * @return boolean Returns true if format options were requested and returned, else false
	 */
	protected function processFormatOptions( $wgRequest ) {
		global $wgOut;
		if ( $wgRequest->getCheck( 'showformatoptions' ) ) {
				// handle Ajax action
				$format = $wgRequest->getVal( 'showformatoptions' );
				$params = $wgRequest->getArray( 'params' );
				$wgOut->disable();
				echo $this->showFormatOptions( $format, $params );
				return true;
			} else {
				return false;
			}
	}

	/**
	 * Returns the additional printouts as a string.
	 *
	 * UIs may overload this to change how this string should be displayed.
	 *
	 * @return string
	 */
	public function getPOStrings() {
		$string = "";
		$printouts = $this->m_ui_helper->getPrintOuts();
		if (  !empty( $printouts ) ) {
			foreach ( $printouts as $value ) {
				$string .= $value->getSerialisation() . "\n";
			}
		}
		return $string;
	}

	/**
	 * Returns true if this page shows the navigationBar. Overload to change behavior.
	 *
	 * @return boolean
	 */
	protected function usesNavigationBar() {
		//hide if no results are found
		if($this->m_ui_helper->getResultCount()==0) return false;
		else return true;
	}

}

/**
 * This class captures the core activities of what a semantic search page should do:
 *  (take parameters, validate them and generate results, or errors, if any).
 *
 * Query UIs may use this class to create a customised UI interface. In most cases,
 * one is likely to extend the SMWQueryUI class to build a Search Special page.
 * However in order to acces some core featues, one may directly access the methods
 * of this class.
 *
 * This class does not define the format in which data should be passed through
 * the web, except those already defined by SMWInfolink.
 *
 * @author Devayon Das
 *
 */
class SMWQueryUIHelper {

	/**
	 * The query passed by the user.
	 * @var string
	 */
	protected $queryString = '';

	/**
	 * Various parameters passed by the user which control the format, limit, offset.
	 * @var array of strings
	 */
	protected $parameters = array(); 

	/**
	 * The additional columns to be displayed with results
	 * @var array of SMWPrintRequest
	 */
	protected $printOuts = array(); // Properties to be printed along with results

	/**
	 * The The additional columns to be displayed with results in '?property' form
	 * 
	 * @var array of strings
	 */
	protected $printOutStrings = array();

	/**
	 * Have errors occured so far?
	 * @var boolean
	 */
	private $errorsOccured = false;

	/**
	 * Has the query come from a special page, or from an InfoLink?
	 *
	 * @var mixed SMWQueryUIHelper::SPECIAL_PAGE | SMWQueryUIHelper::WIKI_LINK
	 */
	private $context;

	/**
	 * Error messages if any
	 *
	 * @var array of string
	 */
	private $errors = array();

	/**
	 * The Query Result, if it has been fetched.
	 *
	 * @var SMWQueryResult
	 */
	private $queryResult = null;

	/*
	 * Constants define whether the parameters was passed from the ui form (SPECIAL_PAGE)
	 * or from the further results infolink (WIKI_LINK)
	 */
	const SPECIAL_PAGE = 0;// parameters passed from special page
	const WIKI_LINK = 1;// parameters passed from 'further links' in the wiki.

	/**
	 * A list of Query UIs
	 * 
	 * @var array of SpecialPage
	 */
	protected static $uiPages = array(); // A list of Query UIs

	/**
	 * Although this constructor is publicly accessible, its use is discouraged.
	 * Instantiation can instead be done by the makeForInfoLink() to handle infolink
	 * requests or makeForUI() to handle requests from a Query form.
	 * 
	 * @param mixed $context SMWQueryUIHelper::SPECIAL_PAGE | SMWQueryUIHelper::WIKI_LINK
	 */
	public function __construct( $context = SMWQueryUIHelper::SPECIAL_PAGE ) {
		$this->context = $context;
	}

	/**
	 * Returns true if any errors have occured
	 *
	 * @return boolean
	 */
	public function hasError() {
		return $this->errorsOccured;
	}

	/**
	 * Returns the limit of results defined. If not set, it returns 0.
	 *
	 * @return int
	 */
	public function getLimit() {
		if ( array_key_exists( 'limit', $this->parameters ) ) {
			return $this->parameters['limit'];
		}
		else {
			return 0;
		}
	}

	/**
	 * Returns the offset of results. If it isnt defined, returns a default value of 20.
	 *
	 * @return int
	 */
	public function getOffset() {
		if ( array_key_exists( 'offset', $this->parameters ) ) {
			return $this->parameters['offset'];
		}
		else {
			return 20;
		}
	}

	/**
	 * Would there be more query results that were not shown due to a limit?
	 * 
	 * @return boolean
	 */
	public function hasFurtherResults() {
		if ( is_a( $this->queryResult, 'SMWQueryResult' ) ) { //The queryResult may not be set
			return $this->queryResult->hasFurtherResults();
		}
		else {
			return false;
		}
	}

	/**
	 * Returns a handle to the underlying Result object.
	 *
	 * @return SMWQueryResult
	 */
	public function getResultObject() {
		return $this->queryResult;
		//TODO: see if this method can be removed.
	}

	/**
	 * Returns an array of errors, if any have occured.
	 * 
	 * @return array of strings
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Register a Semantic Search Special Page.
	 *
	 * This method can be used by any new Query UI to register itself.
	 * The corresponding method getUiList() would return the names of all lists
	 * Query UIs.
	 *
	 * @see getUiList()
	 * @param SpecialPage $page
	 */
	public static function addUI( SpecialPage &$page ) {
	/*
	 * This way of registering, instead of using a global variable will cause
	 * SMWQueryUIHelper to AutoLoad, but the alternate would break encapsulation.
	 */
		self::$uiPages[] = $page;
	}

	/**
	 * Returns an array of Semantic Search Special Pages
	 *
	 * @see addUI()
	 * @return array of SpecialPage
	 */
	public static function getUiList() {
		return self::$uiPages;
	}

	/**
	 * Sets up a query. If validation is enabled, then the query string is checked
	 * for errors.
	 *
	 * @param string $query_string The query
	 * @return array array of errors, if any.
	 */
	public function setQueryString( $query_string = "", $enable_validation = false ) {
		$this -> queryString = $query_string;

		$errors = array();
		if ( $enable_validation ) {
			if ( $query_string == '' ) {
				$errors[] = "No query has been specified"; // TODO i18n
			}
			else
			{
				$query = SMWQueryProcessor::createQuery( $query_string, array() );
				$errors = $query ->getErrors();
			}
			if ( !empty ( $errors ) ) {
				$this->errorsOccured = true;
			}
			$this->errors = array_merge( $errors, $this->errors );
		}

		return $errors;
	}

	/**
	 *
	 * Sets up any extra properties which need to be displayed with results. Each
	 * string in printouts should be of the form "?property" or "property"
	 *
	 * When validation is enabled, the values in $print_outs are checked against
	 * properties which exist in the wiki, and a warning string (for each
	 * property) is returned. Returns an empty array otherwise.
	 *
	 * @param array $print_outs Array of strings
	 * @param boolean $enable_validation
	 * @return array Array of errors messages (strings), if any.
	 */
	public function setPrintOuts( array $print_outs = array(), $enable_validation = false ) {
		/*
		 * Note: property validation is not very clearly defined yet, so validation is disabled by default
		 */
		
		$errors = array();
		if ( $enable_validation ) {
			foreach ( $print_outs as $key => $prop ) {
				if ( $prop[0] != '?' ) {
					$print_outs[$key] = "?" . $print_outs[$key];
				}
				if ( !$this->validateProperty( $prop ) ) {
					$errors[] = "$prop may not be a valid property"; // TODO: add i18n
					$this->errorsOccured = true;
				}
			}
		}
		$this -> printOutStrings = $print_outs;
		$this->errors = array_merge( $errors, $this->errors );
		return $errors;
	}

	/**
	 * Sets the parameters for the query.
	 *
	 * The structure of $params is defined partly by #ask
	 * and also by the Result Printer used. When validation is enabled, $params are checked
	 * for conformance, and error messages, if any, are returned.
	 *
	 * Although it is not mandatory for any params to be set while calling this method,
	 * this method must be called so that default parameters are used.
	 *
	 * @global int $smwgQMaxInlineLimit
	 * @global array $smwgResultFormats
	 * @param array $params
	 * @param boolean $enable_validation
	 * @return array of strings
	 */
	public function setParams( array $params = array(), $enable_validation = false ) {
		global $smwgQMaxInlineLimit, $smwgResultFormats;
		$errors = array();

		 // checking for missing parameters and adding them
		if ( !array_key_exists( 'format', $params ) )
				$params[ 'format' ] = $this->getDefaultResultPrinter ();
		if ( !array_key_exists( 'order', $params ) )
				$params[ 'order' ] = '';
		if ( !array_key_exists( 'limit', $params ) )
				$params[ 'limit' ] = 20;
		$params[ 'limit' ] = min( $params[ 'limit' ], $smwgQMaxInlineLimit );
		if ( !array_key_exists( 'offset', $params ) )
				$params['offset'] = 0;

		if ( $enable_validation ) {
			// validating the format
			if ( !array_key_exists( $params['format'], $smwgResultFormats ) ) {
				$errors[] = "The chosen format " + $params['format'] + " does not exist for this wiki"; // TODO i18n
				$this->errorsOccured = true;
			}
			else
			{	// validating parameters for result printer
				$printer = SMWQueryProcessor::getResultPrinter( $params[ 'format' ] );
				$para_meters = $printer->getParameters();
				if ( is_array( $para_meters ) ) {
						$validator = new Validator();
						$validator -> setParameters( $params, $para_meters );
						$validator->validateParameters();
						$validator_has_error = $validator->hasFatalError();
						if ( $validator_has_error ) {
							array_merge ( $errors, $validator->getErrorMessages () );
							$this->errorsOccured = true;
						}
				}
			}
		}

		$this -> parameters = $params;
		$this -> errors = array_merge( $errors, $this->errors );
		return $errors;
	}

	/**
	 * Processes the QueryString, Params, and PrintOuts.
	 *
	 * @todo Combine this method with execute() or remove it altogether.
	 *
	 */
	public function extractParameters( $p ) {
		if ( $this->context == self::SPECIAL_PAGE ) {
			// assume setParams(), setPintouts and setQueryString have been called
			$rawparams = array_merge( $this->parameters, array( $this->queryString ), $this->printOutStrings );
		}
		else // context is WIKI_LINK
		{
			$rawparams = SMWInfolink::decodeParameters( $p, true );
			// calling setParams to fill in missing parameters
			$this->setParams( $rawparams );
			$rawparams = array_merge( $this->parameters, $rawparams );
		}

		SMWQueryProcessor::processFunctionParams( $rawparams, $this->queryString, $this->parameters, $this->printOuts );
	}

	/**
	 * Executes the query.
	 *
	 * This method can be called once $queryString, $parameters, $printOuts are set
	 * either by using the setQueryString(), setParams() and setPrintOuts() followed by extractParameters(),
	 * or one of the static factory methods such as makeForInfoLink() or makeForUI().
	 *
	 * Errors, if any can be accessed from hasError() and getErrors().
	 */
	public function execute() {
		$errors = array();
		$query = SMWQueryProcessor::createQuery( $this->queryString, $this->parameters, SMWQueryProcessor::SPECIAL_PAGE , $this->parameters['format'], $this->printOuts );
		$res = smwfGetStore()->getQueryResult( $query );
		$this->queryResult = $res;
		$errors = array_merge( $errors, $res->getErrors() );
		if ( !empty( $errors ) ) {
			$this->errorsOccured = true;
			$this->errors = array_merge( $errors, $this->errors );
		}

		// BEGIN: Try to be smart for rss/ical if no description/title is given and we have a concept query
		if ( $this->parameters['format'] == 'rss' ) {
			$desckey = 'rssdescription';
			$titlekey = 'rsstitle';
		} elseif ( $this->parameters['format'] == 'icalendar' ) {
			$desckey = 'icalendardescription';
			$titlekey = 'icalendartitle';
		} else {
			$desckey = false;
		}

		if ( ( $desckey ) && ( $query->getDescription() instanceof SMWConceptDescription ) &&
			 ( !isset( $this->parameters[$desckey] ) || !isset( $this->parameters[$titlekey] ) ) ) {
			$concept = $query->getDescription()->getConcept();

			if ( !isset( $this->parameters[$titlekey] ) ) {
				$this->parameters[$titlekey] = $concept->getText();
			}

			if ( !isset( $this->parameters[$desckey] ) ) {
				$dv = end( smwfGetStore()->getPropertyValues( SMWWikiPageValue::makePageFromTitle( $concept ), new SMWDIProperty( '_CONC' ) ) );
				if ( $dv instanceof SMWConceptValue ) {
					$this->parameters[$desckey] = $dv->getDocu();
				}
			}
		}
		// END: Try to be smart for rss/ical if no description/title is given and we have a concept query
	}

	/**
	 * Returns the results in HTML, or in case of exports, a link to the result.
	 *
	 * This method can only be called after execute() has been called.
	 *
	 * @return string of all the html generated
	 */
	public function getHTMLResult() {
		$result = '';
		$res = $this->queryResult;
		$printer = SMWQueryProcessor::getResultPrinter( $this->parameters['format'], SMWQueryProcessor::SPECIAL_PAGE );
		$result_mime = $printer->getMimeType( $res );

			if ( $res->getCount() > 0 ) {

				$query_result = $printer->getResult( $res, $this->parameters, SMW_OUTPUT_HTML );

				if ( is_array( $query_result ) ) {
					$result .= $query_result[0];
				} else {
					$result .= $query_result;
				}

			} else {
				$result = wfMsg( 'smw_result_noresults' );
			}
			return $result;
	}
	
	/**
	 *  $queryString, $parameters, $printOuts are set, returns the relevant #ask query
	 */

	/**
	 * Returns the query in the #ask format
	 * 
	 * @return string
	 */
	public function getAsk() {
		$result = '{{#ask:' . htmlspecialchars( $this->queryString ) . "\n";
		foreach ( $this->printOuts as $printout ) {
			$result .= '|' . $printout->getSerialisation() . "\n";
		}
		foreach ( $this->parameters as $param_name => $param_value ) {
			$result .= '|' . htmlspecialchars( $param_name ) . '=' . htmlspecialchars( $param_value ) . "\n";
		}
		$result .= '}}';
		return $result;
	}

	/**
	 * Returns the query.
	 *
	 * @return string
	 */
	public function getQueryString() {
		return $this->queryString;
	}

	/**
	 * Returns number of available results.
	 * 
	 * @return int
	 */
	public function getResultCount() {
		if ( is_a( $this->queryResult, 'SMWQueryResult' ) ) {
			return $this->queryResult->getCount();
		}
		else return 0;

	}

	/**
	 * Retuens the param array
	 * 
	 * @return array
	 */
	public function getParams() {
		return $this->parameters;
	}

	/**
	 * Returns additional prinouts as an array of SMWPrintRequests
	 *
	 * @return array SMWPrintRequest or an empty array
	 */
	public function getPrintOuts() {
		if ( !empty( $this->printOuts ) ) {
			if ( is_a( $this->printOuts[0], 'SMWPrintRequest' ) ) {
				return $this->printOuts;
			}
		}
		return array();
	}

	/**
	 * Constructs a new SMWQueryUIHelper when parameters are passed in the InfoLink style
	 *
	 * Errors, if any can be accessed from hasError() and getErrors()
	 *
	 * @param string $p parametrs
	 * @param boolean $enable_validation
	 * @return SMWQueryUIHelper
	 */
	public static function makeForInfoLink( $p, $enable_validation = true ) {
		// TODO handle validation for infolink parameters
		$result = new SMWQueryUIHelper( self::WIKI_LINK );
		$result->extractParameters( $p );
		$result->execute();
		return $result;
	}
	/**
	 * Constructs a new SMWQueryUIHelper when arguments are extracted from the UI
	 *
	 * Errors, if any can be accessed from hasError() and getErrors()
	 *
	 * @param string $query
	 * @param array $params of key=>value pairs
	 * @param array $printouts array of '?property' strings
	 * @param boolean $enable_validation
	 * @return SMWQueryUIHelper
	 */
	public static function makeForUI( $query, array $params, array $printouts, $enable_validation = true ) {
		$result = new SMWQueryUIHelper( self::SPECIAL_PAGE );
		$result->setParams( $params, $enable_validation );
		$result->setPrintOuts( $printouts, $enable_validation );
		$result->setQueryString( $query, $enable_validation );
		$result->extractParameters( "" );
		// $result->execute();
		return $result;
	}
	/**
	 * Checks if $property exists in the wiki or not
	 * 
	 * @return bool
	 */
	protected static function validateProperty( $property ) {
		/*
		 * Curently there isn't a simple, back-end agnost way of searching for properties from
		 * SMWStore. We hence we check if $property has a corresponding page describing it.
		 */
		$prop = substr ( $property, 1 );// removing the leading '?' while checking.
		$propertypage = Title::newFromText( $prop, SMW_NS_PROPERTY );
		if ( is_a( $propertypage, 'Title' ) ) {
			return( $propertypage->exists() );
		} else {
			return false;
		}
	}

	/**
	 * Returns the result printer which should be used if not specified by the user.
	 * 
	 * Overload if necessary.
	 *
	 * @return string
	 */
	protected static function getDefaultResultPrinter() {
		return 'broadtable';
	}

}
