<?php

/**
 * This class captures the core activities of what a semantic search page should
 * do: take parameters, validate them and generate results, or errors, if any.
 *
 * Query UIs may use this class to create a customised UI interface. In most
 * cases, one is likely to extend the SMWQueryUI class to build a Search Special
 * page. However in order to access some core featues, one may directly access
 * the methods of this class.
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
	 * Various parameters passed by the user which control the format,
	 * limit, offset.
	 * @var array of strings
	 */
	protected $parameters = array();

	/**
	 * The additional columns to be displayed with results.
	 * @var array of SMWPrintRequest
	 */
	protected $printOuts = array(); // Properties to be printed along with results

	/**
	 * The The additional columns to be displayed with results in
	 * '?property' form.
	 *
	 * @var array of strings
	 */
	protected $printOutStrings = array();

	/**
	 * Have errors occurred so far?
	 * @var boolean
	 */
	private $errorsOccurred = false;

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
	 * The default result printer if no format is set at the higher level
	 */
	private $defaultResultPrinter = 'broadtable';

	/**
	 * The Query Result, if it has been fetched.
	 *
	 * @var SMWQueryResult
	 */
	private $queryResult = null;

	/**
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
	protected static $uiPages = array();

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
	 * Returns true if any errors have occurred
	 *
	 * @return boolean
	 */
	public function hasError() {
		return $this->errorsOccurred;
	}

	/**
	 * Returns the limit of results defined. If not set, it returns 0.
	 *
	 * @return integer
	 */
	public function getLimit() {
		if ( array_key_exists( 'limit', $this->parameters ) ) {
			return $this->parameters['limit'];
		} else {
			return 0;
		}
	}

	/**
	 * Returns the offset of results. If it isnt defined, returns a default
	 * value of 20.
	 *
	 * @return integer
	 */
	public function getOffset() {
		if ( array_key_exists( 'offset', $this->parameters ) ) {
			return $this->parameters['offset'];
		} else {
			return 20;
		}
	}

	/**
	 * Would there be more query results that were not shown due to a limit?
	 *
	 * @return boolean
	 */
	public function hasFurtherResults() {
		if ( !is_null( $this->queryResult ) ) { // The queryResult may not be set
			return $this->queryResult->hasFurtherResults();
		} else {
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
	}

	/**
	 * Returns an array of errors, if any have occurred.
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
	 * The corresponding method getUiList() would return the names of all
	 * lists Query UIs.
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
	 * Sets up a query. If validation is enabled, then the query string is
	 * checked for errors.
	 *
	 * @param string $queryString The query
	 * @return array array of errors, if any.
	 */
	public function setQueryString( $queryString = "", $enableValidation = false ) {
		$this -> queryString = $queryString;

		$errors = array();
		if ( $enableValidation ) {
			if ( $queryString === '' ) {
				$errors[] = wfMessage( 'smw_qui_noquery' )->text();
			} else {
				$query = SMWQueryProcessor::createQuery( $queryString, array() );
				$errors = $query ->getErrors();
			}
			if ( !empty ( $errors ) ) {
				$this->errorsOccurred = true;
			}
			$this->errors = array_merge( $errors, $this->errors );
		}

		return $errors;
	}

	/**
	 *
	 * Sets up any extra properties which need to be displayed with results.
	 * Each string in printouts should be of the form "?property" or
	 * "property".
	 *
	 * When validation is enabled, the values in $printOuts are checked
	 * against properties which exist in the wiki, and a warning string (for
	 * each property) is returned. Returns an empty array otherwise.
	 *
	 * @param array $printOuts Array of strings
	 * @param boolean $enableValidation
	 * @return array Array of errors messages (strings), if any.
	 */
	public function setPrintOuts( array $printOuts = array(), $enableValidation = false ) {
		/*
		 * Note: property validation is not very clearly defined yet, so validation is disabled by default
		 */

		$errors = array();
		if ( $enableValidation ) {
			foreach ( $printOuts as $key => $prop ) {
				if ( $prop[0] != '?' ) {
					$printOuts[$key] = "?" . $printOuts[$key];
				}
				if ( !$this->validateProperty( $prop ) ) {
					$errors[] = wfMessage( 'smw_qui_invalidprop', $prop )->text();
					$this->errorsOccurred = true;
				}
			}
		}
		$this -> printOutStrings = $printOuts;
		$this->errors = array_merge( $errors, $this->errors );
		return $errors;
	}

	/**
	 * Sets the parameters for the query.
	 *
	 * The structure of $params is defined partly by #ask and also by the
	 * Result Printer used. When validation is enabled, $params are checked
	 * for conformance, and error messages, if any, are returned.
	 *
	 * Although it is not mandatory for any params to be set while calling
	 * this method, this method must be called so that default parameters
	 * are used.
	 *
	 * @global int $smwgQMaxInlineLimit
	 * @global array $smwgResultFormats
	 * @param array $params
	 * @param boolean $enableValidation
	 * @return array of strings
	 */
	public function setParams( array $params = array(), $enableValidation = false ) {
		global $smwgQMaxInlineLimit, $smwgResultFormats;
		$errors = array();

		// checking for missing parameters and adding them
		if ( !array_key_exists( 'format', $params ) || ! array_key_exists ( $params['format'], $smwgResultFormats ) ) {
			$params[ 'format' ] = $this->defaultResultPrinter;
		}
		if ( !array_key_exists( 'limit', $params ) ) {
			$params[ 'limit' ] = 20;
		}
		$params[ 'limit' ] = min( $params[ 'limit' ], $smwgQMaxInlineLimit );
		if ( !array_key_exists( 'offset', $params ) ) {
			$params['offset'] = 0;
		}

		if ( $enableValidation ) { // validating the format
			if ( !array_key_exists( $params['format'], $smwgResultFormats ) ) {
				$errors[] = wfMessage( 'smw_qui_invalidformat', $params['format'] )->text();
				$this->errorsOccurred = true;
			} else { // validating parameters for result printer
				$printer = SMWQueryProcessor::getResultPrinter( $params[ 'format' ] );
				$para_meters = $printer->getParameters();
				if ( is_array( $para_meters ) ) {
					$validator = new Validator();
					$validator->setParameters( $params, $para_meters );
					$validator->validateParameters();
					if ( $validator->hasFatalError() ) {
						array_merge ( $errors, $validator->getErrorMessages () );
						$this->errorsOccurred = true;
					}
				}
			}
		}

		$this->parameters = $params;
		$this->errors = array_merge( $errors, $this->errors );
		return $errors;
	}

	/**
	 * Processes the QueryString, Params, and PrintOuts.
	 *
	 * @todo Combine this method with execute() or remove it altogether.
	 */
	public function extractParameters( $p ) {
		if ( $this->context == self::SPECIAL_PAGE ) {
			// assume setParams(), setPintouts and setQueryString have been called
			$rawParams = array_merge( $this->parameters, array( $this->queryString ), $this->printOutStrings );
		} else {// context is WIKI_LINK
			$rawParams = SMWInfolink::decodeParameters( $p, true );
			// calling setParams to fill in missing parameters
			$this->setParams( $rawParams );
			$rawParams = $this->parameters;
		}

		list( $this->queryString, $this->parameters, $this->m_printOuts ) = SMWQueryProcessor::getComponentsFromFunctionParams( $rawParams, false );
	}

	/**
	 * Executes the query.
	 *
	 * This method can be called once $queryString, $parameters, $printOuts
	 * are set either by using the setQueryString(), setParams() and
	 * setPrintOuts() followed by extractParameters(), or one of the static
	 * factory methods such as makeForInfoLink() or makeForUI().
	 *
	 * Errors, if any can be accessed from hasError() and getErrors().
	 */
	public function execute() {
		$errors = array();

		if ( $this->queryString !== '' ) {
			// FIXME: this is a hack
			SMWQueryProcessor::addThisPrintout( $this->printOuts, $this->parameters );
			$params = SMWQueryProcessor::getProcessedParams( $this->parameters, $this->printOuts );
			$this->parameters['format'] = $params['format']->getValue();
			$this->params = $params;

			$query = SMWQueryProcessor::createQuery(
				$this->queryString,
				$params,
				SMWQueryProcessor::SPECIAL_PAGE,
				$this->parameters['format'],
				$this->printOuts
			);

			$res = smwfGetStore()->getQueryResult( $query );
			$this->queryResult = $res;

			$errors = array_merge( $errors, $res->getErrors() );
			if ( !empty( $errors ) ) {
				$this->errorsOccurred = true;
				$this->errors = array_merge( $errors, $this->errors );
			}

			// BEGIN: Try to be smart for rss/ical if no description/title is given and we have a concept query
			if ( $this->parameters['format'] == 'rss' ) {
				$descKey = 'rssdescription';
				$titleKey = 'rsstitle';
			} elseif ( $this->parameters['format'] == 'icalendar' ) {
				$descKey = 'icalendardescription';
				$titleKey = 'icalendartitle';
			} else {
				$descKey = false;
			}

			if ( $descKey && ( $query->getDescription() instanceof SMWConceptDescription ) &&
				 ( !isset( $this->parameters[$descKey] ) || !isset( $this->parameters[$titleKey] ) ) ) {
				$concept = $query->getDescription()->getConcept();

				if ( !isset( $this->parameters[$titleKey] ) ) {
					$this->parameters[$titleKey] = $concept->getText();
				}

				if ( !isset( $this->parameters[$descKey] ) ) {
					// / @bug The current SMWStore will never return SMWConceptValue (an SMWDataValue) here; it might return SMWDIConcept (an SMWDataItem)
					$dv = end( smwfGetStore()->getPropertyValues( SMWWikiPageValue::makePageFromTitle( $concept ), new SMWDIProperty( '_CONC' ) ) );
					if ( $dv instanceof SMWConceptValue ) {
						$this->parameters[$descKey] = $dv->getDocu();
					}
				}
			}
			// END: Try to be smart for rss/ical if no description/title is given and we have a concept query

			/*
			 * If parameters have been passed in the infolink-style and the
			 * mimie-type of format is defined, generate the export, instead of
			 * showing more html.
			 */
			$printer = SMWQueryProcessor::getResultPrinter(
				$this->parameters['format'],
				SMWQueryProcessor::SPECIAL_PAGE
			);
			$resultMime = $printer->getMimeType( $res );
			if ( $this->context == self::WIKI_LINK && $resultMime != false ) {
				global $wgOut;
				$result = $printer->getResult( $res, $this->parameters, SMW_OUTPUT_FILE );
				$resultName = $printer->getFileName( $res );
				$wgOut->disable();
				header( "Content-type: $resultMime; charset=UTF-8" );
				if ( $resultName !== false ) {
					header( "content-disposition: attachment; filename=$resultName" );
				}
				echo $result;
			}
		}
	}

	/**
	 * Returns the results in HTML, or in case of exports, a link to the
	 * result.
	 *
	 * This method can only be called after execute() has been called.
	 *
	 * @return string of all the HTML generated
	 */
	public function getHTMLResult() {
		$result = '';

		$res = $this->queryResult;
		$printer = SMWQueryProcessor::getResultPrinter( $this->parameters['format'],
			SMWQueryProcessor::SPECIAL_PAGE );

		if ( $res->getCount() > 0 ) {
			$queryResult = $printer->getResult( $res, $this->params, SMW_OUTPUT_HTML );

			if ( is_array( $queryResult ) ) {
				$result .= $queryResult[0];
			} else {
				$result .= $queryResult;
			}
		} else {
			$result = wfMessage( 'smw_result_noresults' )->text();
		}

		return $result;
	}

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
			$result .= '|' . htmlspecialchars( $param_name ) .
				'=' . htmlspecialchars( $param_value ) . "\n";
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
		if ( !is_null( $this->queryResult ) ) {
			return $this->queryResult->getCount();
		} else {
			return 0;
		}
	}

	/**
	 * Returns the parameter array.
	 *
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * Returns additional prinouts as an array of SMWPrintRequests.
	 *
	 * @return array SMWPrintRequest or an empty array
	 */
	public function getPrintOuts() {
		if ( !empty( $this->printOuts ) &&
		( $this->printOuts[0] instanceof SMWPrintRequest ) ) {
			return $this->printOuts;
		}
		return array();
	}

	/**
	 * Constructs a new SMWQueryUIHelper object when the query is passed to
	 * the UI in the Info-link format. This constructor should be used for
	 * handling the "further results" links in wiki-pages that use #ask. If
	 * your search UI handles form parameters only, then consider using
	 * makeForUI().
	 *
	 * If any errors do occur while parsing parameters, they may be accessed
	 * from hasError() and getErrors().
	 *
	 * @param string $p parameters of the query.
	 * @param boolean $enableValidation
	 * @return SMWQueryUIHelper
	 *
	 * @todo Handle validation for infolink parameters
	 */
	public static function makeForInfoLink( $p, $enableValidation = false ) {
		$result = new SMWQueryUIHelper( self::WIKI_LINK );
		$result->extractParameters( $p );
		$result->execute();
		return $result;
	}

	/**
	 * Constructs a new SMWQueryUIHelper when the query is passed to the UI
	 * from a web form. This constructor should be used to handle form
	 * parameters sent from the UI itself. If your search UI must also handle
	 * "further results" links from a wiki page, consider using
	 * makeForInfoLink().
	 *
	 * If any errors do occur while parsing parameters, they may be accessed
	 * from hasError() and getErrors().
	 *
	 * @param string $query
	 * @param array $params of key=>value pairs
	 * @param array $printouts array of '?property' strings
	 * @param boolean $enableValidation
	 * @return SMWQueryUIHelper
	 *
	 */
	public static function makeForUI( $query, array $params, array $printouts, $enableValidation = false ) {
		$result = new SMWQueryUIHelper( self::SPECIAL_PAGE );
		$result->setParams( $params, $enableValidation );
		$result->setPrintOuts( $printouts, $enableValidation );
		$result->setQueryString( $query, $enableValidation );
		$result->extractParameters( '' );
		return $result;
	}

	/**
	 * Checks if $property exists in the wiki or not.
	 *
	 * @param string $property a property name in "?property" format
	 * @return boolean
	 */
	protected static function validateProperty( $property ) {
		/*
		 * Curently there isn't a simple, back-end agnost way of searching for properties from
		 * SMWStore. We hence we check if $property has a corresponding page describing it.
		 */
		$prop = substr( $property, 1 ); // removing the leading '?' while checking.
		$property_page = Title::newFromText( $prop, SMW_NS_PROPERTY );
		if ( $property_page instanceof Title ) {
			return( $property_page->exists() );
		} else {
			return false;
		}
	}
}
