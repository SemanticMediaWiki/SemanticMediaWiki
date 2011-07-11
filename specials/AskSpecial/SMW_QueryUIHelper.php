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
 * Query UIs may use this class to create a customised UI interface.
 *
 * @author Devayon Das
 *
 */
class SMWQueryUIHelper {
/*
 * Design note:
 * This class does not define any format for how parameters should be
 * passed from the user to this class, except those already defined by Infolink.
 *
 *
 */

// members
	protected $m_querystring = ''; // The query
	protected $m_params = array(); // Parameters controlling how the results should be displayed
	protected $m_printouts = array(); // Properties to be printed along with results
	protected static $m_UIPages = array(); // A list of Query UIs
	private $fatal_errors = false;
	private $context;
	private $errors = array();
	private $queryresult = null;

	const SPECIAL_PAGE = 0;// parameters passed from special page
	const WIKI_LINK = 1;// parameters passed from 'further links' in the wiki.


// constructor
	public function __construct( $context = self::SPECIAL_PAGE ) {
		$this->context = $context;
	}

	public function hasError() {
		return $this->fatal_errors;
	}

	public function getLimit() {
		if ( array_key_exists( 'limit', $this->m_params ) ) {
			return $this->m_params['limit'];
		}
		else {
			return 0;
		}
	}

	public function getOffset() {
		if ( array_key_exists( 'offset', $this->m_params ) ) {
			return $this->m_params['offset'];
		}
		else {
			return 20;
		}
	}
	public function hasFurtherResults() {
		if ( is_a( $this->queryresult, 'SMWQueryResult' ) ) {
			return $this->queryresult->hasFurtherResults();
		}
		else {
			return false;
		}
	}

	public function getResultObject() {
		return $this->getResultObject();
	}

	/**
	 *
	 * Returns an array of errors, if any have occured.
	 * @return array of strings
	 */
	public function getErrors() {
		return $this->errors;
	}
	/**
	 * Register a Semantic Search Special Page
	 * @param SpecialPage $page
	 */
	public static function addUI( SpecialPage &$page ) {
	/*
	 * This way of registering, instead of using a global variable will cause
	 * SMWQueryUIHelper to AutoLoad, but the alternate would break encapsulation.
	 */
		self::$m_UIPages[] = $page;
	}

	/**
	 * Returns an array of Semantic Search Special Pages
	 * @return array of SpecialPage
	 */
	public static function getUiList() {
		return self::$m_UIPages;
	}

	/**
	 * Initialises the object with a Query. If the Query string is of incorrect syntax,
	 * returns an array of errors.
	 *
	 * @param string $querystring The query
	 * @return array array of errors, if any.
	 */
	public function setQueryString( $querystring = "", $enable_validation = true ) {
		$this -> m_querystring = $querystring;
		$errors = array();
		if ( $enable_validation ) {
			if ( $querystring == '' ) {
				$errors[] = "No query has been specified"; // TODO i18n
			}
			else
			{
				$query = SMWQueryProcessor::createQuery( $querystring, array() );
				$errors = $query ->getErrors();
			}
			if ( !empty ( $errors ) ) {
				$this->fatal_errors = true;
			}
			$this->errors = array_merge( $errors, $this->errors );
			return $errors;
		}
	}

	/**
	 *
	 * If $enable_validation is true, checks if all the values in $printouts are
	 * properties which exist in the wiki and returns a warning string (for each
	 * property). Returns null otherwise.
	 *
	 * @param array $printouts Array of additional properties to be shown in results
	 * @return array array of errors, if any.
	 */
	public function setPrintOuts( array $printouts = array(), $enable_validation = true ) {
		$errors = array();
		if ( $enable_validation ) {
			foreach ( $printouts as $key => $prop ) {
				if ( $prop[0] != '?' ) {
					$printouts[$key] = "?" . $printouts[$key];
				}
				if ( !$this->validateProperty( $prop ) ) {
					$errors[] = "$prop may not be a valid property"; // TODO: add i18n
					$this->fatal_errors = true;
				}
			}
		}
		$this -> m_printouts = $printouts;
		$this->errors = array_merge( $errors, $this->errors );
		return $errors;
	}

	public function setParams( array $params = array(), $enable_validation = true ) {
		/*
		 *Validate, and add missing params.		 *
		 */
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
				$this->fatal_errors = true;
			}
			else
			{	// validating parameters for result printer
				$printer = SMWQueryProcessor::getResultPrinter( $params[ 'format' ] );
				$parameters = $printer->getParameters();
				if ( is_array( $parameters ) ) {
						$validator = new Validator();
						$validator -> setParameters( $params, $parameters );
						$validator->validateParameters();
						$validator_has_error = $validator->hasFatalError();
						if ( $validator_has_error ) {
							array_merge ( $errors, $validator->getErrorMessages () );
							$this->fatal_errors = true;
						}
				}
			}
		}

		$this -> m_params = $params;
		$this -> errors = array_merge( $errors, $this->errors );
		return $errors;
	}

	public function execute() {
		/*
		 * Once $m_querystring, $m_params, $m_printouts are set, generates the
		 * results / or link. The pagination links (or navigation bar) are expected
		 * to be created by the UI designer. (or maybe we can put a method here to
		 * make the nav-bar which also calls makeHTMLResult().
		 */
		$errors = array();
		$query = SMWQueryProcessor::createQuery( $this->m_querystring, $this->m_params, SMWQueryProcessor::SPECIAL_PAGE , $this->m_params['format'], $this->m_printouts );
		$res = smwfGetStore()->getQueryResult( $query );
		$this->queryresult = $res;
		$errors = array_merge( $errors, $res->getErrors() );
		if ( !empty( $errors ) ) {
			$this->fatal_errors = true;
			$this->errors = array_merge( $errors, $this->errors );
		}

		// BEGIN: Try to be smart for rss/ical if no description/title is given and we have a concept query
		if ( $this->m_params['format'] == 'rss' ) {
			$desckey = 'rssdescription';
			$titlekey = 'rsstitle';
		} elseif ( $this->m_params['format'] == 'icalendar' ) {
			$desckey = 'icalendardescription';
			$titlekey = 'icalendartitle';
		} else {
			$desckey = false;
		}

		if ( ( $desckey ) && ( $query->getDescription() instanceof SMWConceptDescription ) &&
			 ( !isset( $this->m_params[$desckey] ) || !isset( $this->m_params[$titlekey] ) ) ) {
			$concept = $query->getDescription()->getConcept();

			if ( !isset( $this->m_params[$titlekey] ) ) {
				$this->m_params[$titlekey] = $concept->getText();
			}

			if ( !isset( $this->m_params[$desckey] ) ) {
				$dv = end( smwfGetStore()->getPropertyValues( SMWWikiPageValue::makePageFromTitle( $concept ), new SMWDIProperty( '_CONC' ) ) );
				if ( $dv instanceof SMWConceptValue ) {
					$this->m_params[$desckey] = $dv->getDocu();
				}
			}
		}
		// END: Try to be smart for rss/ical if no description/title is given and we have a concept query
	}

	public function getHTMLResult() {
		$result = '';
		$res = $this->queryresult;
		$printer = SMWQueryProcessor::getResultPrinter( $this->m_params['format'], SMWQueryProcessor::SPECIAL_PAGE );
		$result_mime = $printer->getMimeType( $res );

			if ( $res->getCount() > 0 ) {

				$query_result = $printer->getResult( $res, $this->m_params, SMW_OUTPUT_HTML );

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

	// next come form-helper methods which may or may not be used by the UI designer

	public function extractParameters( $p ) {
		if ( $this->context == self::SPECIAL_PAGE ) {
			// assume setParams(), setPintouts and setQueryString have been called
			$rawparams = array_merge( $this->m_params, array( $this->m_querystring ), $this->m_printouts );
		}
		else // context is WIKI_LINK
		{
			$rawparams = SMWInfolink::decodeParameters( $p, true );
			// calling setParams to fill in missing parameters
			$this->setParams( $rawparams );
			$rawparams = array_merge( $this->m_params, $rawparams );
		}

		SMWQueryProcessor::processFunctionParams( $rawparams, $this->m_querystring, $this->m_params, $this->m_printouts );
	}
	/**
	 * $m_querystring, $m_params, $m_printouts are set, returns the relevant #ask query
	 */
	public function makeAsk() {
		$result = '{{#ask:' . htmlspecialchars( $this->m_querystring ) . "\n";
		foreach ( $this->m_printouts as $printout ) {
			$result .= '|' . $printout->getSerialisation() . "\n";
		}
		foreach ( $this->m_params as $param_name => $param_value ) {
			$result .= '|' . htmlspecialchars( $param_name ) . '=' . htmlspecialchars( $param_value ) . "\n";
		}
		$result .= '}}';
		return $result;
	}

	public function getQueryString() {
		return $this->m_querystring;
	}

	public function getResultCount() {
		if ( is_a( $this->queryresult, 'SMWQueryResult' ) ) {
			return $this->queryresult->getCount();
		}
		else return 0;

	}

	public function getParams() {
		return $this->m_params;
	}

	/**
	 * Returns additional prinouts as an array of SMWPrintRequests
	 *
	 * @return array SMWPrintRequest or an empty array
	 */
	public function getPrintOuts() {
		if ( !empty( $this->m_printouts ) ) {
			if ( is_a( $this->m_printouts[0], 'SMWPrintRequest' ) ) {
				return $this->m_printouts;
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
	public static function makeFromInfoLink( $p, $enable_validation = true ) {
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
	public static function makeFromUI( $query, array $params, array $printouts, $enable_validation = true ) {
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
	 * Overload if necessary.
	 *
	 * @return string
	 */
	protected static function getDefaultResultPrinter() {
		return 'broadtable';
	}

}
