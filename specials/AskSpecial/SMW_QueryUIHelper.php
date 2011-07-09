<?php

/**
 * A base class for Semantic Search UIs. All Semantic Search UI's may subclass
 * from this.
 *
 * @author Markus Krötzsch
 * @author Yaron Koren
 * @author Sanyam Goyal
 * @author Jeroen De Dauw
 */
abstract class SMWQueryUI extends SpecialPage {
	protected $m_ui_helper;
	protected abstract function execute( $p ) {
		/*
		 * Extract the parameters from the UI. Use either the constructor or
		 * $this->m_ui_helper=new SMWUIHelper()
		 */
	}
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
	}
	protected function makeHtmlResult() {
		global $wgOut;
		if ( is_a( $this->m_ui_helper,  'SMWQueryUIHelper' ) ) {

		}
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
	 * @since 1.6
	 *
	 * @param mixed $param
	 *
	 * @return Parameter
	 */
	protected function toValidatorParam( $param ) {
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

	protected function usesNavigationBar() {
		return true;
	}

	/**
	 * Build the navigation for some given query result, reuse url-tail parameters.
	 *
	 * @param SMWQueryResult $res
	 * @param string $urltail
	 *
	 * @return string
	 */
	protected function getNavigationBar( SMWQueryResult $res, $urltail, $offset, $limit ) {
		global $smwgQMaxInlineLimit;

		// Prepare navigation bar.
			if ( $offset > 0 ) {
				$navigation = Html::element(
					'a',
					array(
						'href' => SpecialPage::getSafeTitleFor( 'Ask' )->getLocalURL( array(
							'offset' => max( 0, $offset - $limit ),
							'limit' => $limit . $urltail
						) ),
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
							'limit' => $limit . $urltail
						) ),
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
								'limit' => $l . $urltail
							) ),
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
}

/**
 * This class helps to implement a Special Page for creating and executing queries.
 *
 * Query UIs may use this class and override methods to create a customised UI
 * interface.
 *
 * @author Devayon Das
 *
 * @property boolean $enable_validation If set to TRUE causes each of the parametes to be checked for errors.
 */
class SMWQueryUIHelper {

// members
	protected $m_querystring = ''; // The query
	protected $m_params = array(); // Parameters controlling how the results should be displayed
	protected $m_printouts = array(); // Properties to be printed along with results
	protected static $m_UIPages = array(); // A list of Query UIs
	public $enable_validation;
	private $fatal_errors = false;
	private $context;
	private $errors = array();
	const SPECIAL_PAGE = 0;// parameters passed from special page
	const WIKI_LINK = 1;// parameters passed from 'further links' in the wiki.


// constructor
	public function __construct( $enable_validation = true, $context = self::SPECIAL_PAGE ) {
		$this -> enable_validation = $enable_validation;
		$this->context = $context;
	}

	public function hasError() {
		return $this->fatal_errors;
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
	public function setQueryString( $querystring = "" ) {
		$this -> m_querystring = $querystring;
		$errors = array();
		if ( $this->enable_validation ) {
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
	public function setPrintOuts( array $printouts = array() ) {
		$errors = array();
		if ( $this -> enable_validation ) {
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

	public function setParams( array $params = array() ) {
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

		if ( $this->enable_validation ) {
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
		$this->errors = array_merge( $errors, $this->errors );
		return $errors;
	}

	public function makeHTMLResult() {
		/*
		 * Once $m_querystring, $m_params, $m_printouts are set, generates the
		 * results / or link. The pagination links (or navigation bar) are expected
		 * to be created by the UI designer. (or maybe we can put a method here to
		 * make the nav-bar which also calls makeHTMLResult().
		 */
		$result = '';
		$errors = array();
		$query = SMWQueryProcessor::createQuery( $this->m_querystring, $this->m_params, SMWQueryProcessor::SPECIAL_PAGE , $this->m_params['format'], $this->m_printouts );
		$res = smwfGetStore()->getQueryResult( $query );
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

	public function getParams() {
		return $this->m_params;
	}
	/**
	 *
	 * @return array of SMWPrintRequest
	 */
	public function getPrintOuts() {
		if ( !empty( $this->printouts ) ) {
			if ( is_a( $this->printouts[0], 'SMWPrintRequest' ) ) {
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
		$result = new SMWQueryUIHelper( $enable_validation, self::WIKI_LINK );
		$result->extractParameters( $p );
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
		$result = new SMWQueryUIHelper( $enable_validation, self::SPECIAL_PAGE );
		$result->setParams( $params );
		$result->setPrintOuts( $printouts );
		$result->setQueryString( $query );
		$result->extractParameters( "" );
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
