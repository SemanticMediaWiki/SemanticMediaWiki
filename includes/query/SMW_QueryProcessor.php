<?php

use ParamProcessor\Options;
use ParamProcessor\Param;
use ParamProcessor\ParamDefinition;
use ParamProcessor\Processor;
use SMW\Query\PrintRequest;
use SMW\Query\PrintRequestFactory;
use SMW\ApplicationFactory;
use SMW\Message;
use SMW\Query\QueryContext;
use SMW\Query\ResultFormatNotFoundException;

/**
 * This file contains a static class for accessing functions to generate and execute
 * semantic queries and to serialise their results.
 *
 * @ingroup SMWQuery
 * @author Markus Krötzsch
 */

/**
 * Static class for accessing functions to generate and execute semantic queries
 * and to serialise their results.
 * @ingroup SMWQuery
 */
class SMWQueryProcessor implements QueryContext {

	/**
	 * Takes an array of unprocessed parameters, processes them using
	 * Validator, and returns them.
	 *
	 * Both input and output arrays are
	 * param name (string) => param value (mixed)
	 *
	 * @since 1.6.2
	 * The return value changed in SMW 1.8 from an array with result values
	 * to an array with Param objects.
	 *
	 * @param array $params
	 * @param array $printRequests
	 * @param boolean $unknownInvalid
	 *
	 * @return Param[]
	 */
	public static function getProcessedParams( array $params, array $printRequests = array(), $unknownInvalid = true ) {
		$validator = self::getValidatorForParams( $params, $printRequests, $unknownInvalid );
		$validator->processParameters();
		$parameters =  $validator->getParameters();

		// Negates some weird behaviour of ParamDefinition::setDefault used in
		// an individual printer.
		// Applying $smwgQMaxLimit, $smwgQMaxInlineLimit will happen at a later
		// stage.
		if ( isset( $params['limit'] ) && isset( $parameters['limit'] ) ) {
			$parameters['limit']->setValue( (int)$params['limit'] );
		}

		return $parameters;
	}

	/**
	 * Takes an array of unprocessed parameters,
	 * and sets them on a new Validator object,
	 * which is returned and ready to process the parameters.
	 *
	 * @since 1.8
	 *
	 * @param array $params
	 * @param array $printRequests
	 * @param boolean $unknownInvalid
	 *
	 * @return Processor
	 */
	public static function getValidatorForParams( array $params, array $printRequests = array(), $unknownInvalid = true ) {
		$paramDefinitions = self::getParameters();

		$paramDefinitions['format']->setPrintRequests( $printRequests );

		$processorOptions = new Options();
		$processorOptions->setUnknownInvalid( $unknownInvalid );

		$validator = Processor::newFromOptions( $processorOptions );

		$validator->setParameters( $params, $paramDefinitions, false );

		return $validator;
	}

	/**
	 * Parse a query string given in SMW's query language to create
	 * an SMWQuery. Parameters are given as key-value-pairs in the
	 * given array. The parameter $context defines in what context the
	 * query is used, which affects ceretain general settings.
	 * An object of type SMWQuery is returned.
	 *
	 * The format string is used to specify the output format if already
	 * known. Otherwise it will be determined from the parameters when
	 * needed. This parameter is just for optimisation in a common case.
	 *
	 * @param string $queryString
	 * @param array $params These need to be the result of a list fed to getProcessedParams
	 * @param $context
	 * @param string $format
	 * @param array $extraPrintouts
	 *
	 * @return SMWQuery
	 */
	static public function createQuery( $queryString, array $params, $context = self::INLINE_QUERY, $format = '', array $extraPrintouts = array(), $contextPage = null ) {

		if ( $format === '' || is_null( $format ) ) {
			$format = $params['format']->getValue();
		}

		$defaultSort = 'ASC';

		if ( $format == 'count' ) {
			$queryMode = SMWQuery::MODE_COUNT;
		} elseif ( $format == 'debug' ) {
			$queryMode = SMWQuery::MODE_DEBUG;
		} else {
			$printer = self::getResultPrinter( $format, $context );
			$queryMode = $printer->getQueryMode( $context );
			$defaultSort = $printer->getDefaultSort();
		}

		// set mode, limit, and offset:
		$offset = 0;
		$limit = $GLOBALS['smwgQDefaultLimit'];

		if ( ( array_key_exists( 'offset', $params ) ) && ( is_int( $params['offset']->getValue() + 0 ) ) ) {
			$offset = $params['offset']->getValue();
		}

		if ( ( array_key_exists( 'limit', $params ) ) && ( is_int( trim( $params['limit']->getValue() ) + 0 ) ) ) {
			$limit = $params['limit']->getValue();

			// limit < 0: always show further results link only
			if ( ( trim( $params['limit']->getValue() ) + 0 ) < 0 ) {
				$queryMode = SMWQuery::MODE_NONE;
			}
		}

		// largest possible limit for "count", even inline
		if ( $queryMode == SMWQuery::MODE_COUNT ) {
			$offset = 0;
			$limit = $GLOBALS['smwgQMaxLimit'];
		}

		$queryCreator = ApplicationFactory::getInstance()->getQueryFactory()->newQueryCreator();

		$queryCreator->setConfiguration(  array(
			'extraPrintouts' => $extraPrintouts,
			'queryMode'   => $queryMode,
			'context'     => $context,
			'contextPage' => $contextPage,
			'offset'      => $offset,
			'limit'       => $limit,
			'querySource' => $params['source']->getValue(),
			'mainLabel'   => $params['mainlabel']->getValue(),
			'sort'        => $params['sort']->getValue(),
			'order'       => $params['order']->getValue(),
			'defaultSort' => $defaultSort
		) );

		return $queryCreator->create( $queryString );
	}

	/**
	 * @deprecated since 2.5, This method should no longer be used but since it
	 * was made protected (and therefore can be derived from) it will remain until
	 * 3.0 to avoid a breaking BC.
	 *
	 * Takes the sort and order parameters and returns a list of sort keys and a list of errors.
	 *
	 * @since 1.7
	 *
	 * @param array $sortParam
	 * @param array $orders
	 * @param string $defaultSort
	 *
	 * @return array ( keys => array(), errors => array() )
	 */
	protected static function getSortKeys( array $sortParam, array $orderParam, $defaultSort ) {
		return ApplicationFactory::getInstance()->getQueryFactory()->newConfigurableQueryCreator()->getSortKeys( $sortParam, $orderParam, $defaultSort );
	}

	/**
	 * Add the subject print request, unless mainlabel is set to "-".
	 *
	 * @since 1.7
	 *
	 * @param array $printRequests
	 * @param array $rawParams
	 */
	public static function addThisPrintout( array &$printRequests, array $rawParams ) {
		if ( !is_null( $printRequests ) ) {
			$hasMainlabel = array_key_exists( 'mainlabel', $rawParams );

			if  ( !$hasMainlabel || trim( $rawParams['mainlabel'] ) !== '-' ) {
				array_unshift( $printRequests, new PrintRequest(
					PrintRequest::PRINT_THIS,
					$hasMainlabel ? $rawParams['mainlabel'] : ''
				) );
			}
		}
	}

	/**
	 * Preprocess a query as given by an array of parameters as is typically
	 * produced by the #ask parser function. The parsing results in a querystring,
	 * an array of additional parameters, and an array of additional SMWPrintRequest
	 * objects, which are filled into call-by-ref parameters.
	 * $showMode is true if the input should be treated as if given by #show
	 *
	 * @param array $rawParams
	 * @param string $querystring
	 * @param array $params
	 * @param array $printouts array of SMWPrintRequest
	 * @param boolean $showMode
	 * @deprecated Will vanish after SMW 1.8 is released.
	 * Use getComponentsFromFunctionParams which has a cleaner interface.
	 */
	static public function processFunctionParams( array $rawParams, &$querystring, &$params, &$printouts, $showMode = false ) {
		list( $querystring, $params, $printouts ) = self::getComponentsFromFunctionParams( $rawParams, $showMode );
	}


	/**
	 * Preprocess a query as given by an array of parameters as is
	 * typically produced by the #ask parser function or by Special:Ask.
	 * The parsing results in a querystring, an array of additional
	 * parameters, and an array of additional SMWPrintRequest objects,
	 * which are returned in an array with three components. If
	 * $showMode is true then the input will be processed as if for #show.
	 * This uses a slightly different way to get the query, and different
	 * default labels (empty) for additional print requests.
	 *
	 * @param array $rawParams
	 * @param boolean $showMode
	 * @return array( string, array( string => string ), array( SMWPrintRequest ) )
	 */
	static public function getComponentsFromFunctionParams( array $rawParams, $showMode ) {
		$queryString = '';
		$parameters = array();
		$printouts = array();

		$lastprintout = null;
		$printRequestFactory = new PrintRequestFactory();

		foreach ( $rawParams as $name => $rawParam ) {
			// special handling for arrays - this can happen if the
			// parameter came from a checkboxes input in Special:Ask:
			if ( is_array( $rawParam ) ) {
				$rawParam = implode( ',', array_keys( $rawParam ) );
			}

			// Bug 32955 / #640
			// Modify (e.g. replace `=`) a condition string only if enclosed by [[ ... ]]
			$rawParam = preg_replace_callback(
				'/\[\[([^\[\]]*)\]\]/xu',
				function( array $matches ) {
					return str_replace( array( '=' ), array( '-3D' ), $matches[0] );
				},
				$rawParam
			);

			// #1258 (named_args -> named args)
			// accept 'name' => 'value' just as '' => 'name=value':
			if ( is_string( $name ) && ( $name !== '' ) ) {
				$rawParam = str_replace( "_", " ", $name ) . '=' . $rawParam;
			}

			if ( $rawParam === '' ) {
			} elseif ( $rawParam { 0 } == '?' ) { // print statement
				$rawParam = substr( $rawParam, 1 );
				$lastprintout = $printRequestFactory->newPrintRequestFromText( $rawParam, $showMode );
				if ( !is_null( $lastprintout ) ) {
					$printouts[] = $lastprintout;
				}
			} elseif ( $rawParam[0] == '+' ) { // print request parameter
				if ( !is_null( $lastprintout ) ) {
					$rawParam = substr( $rawParam, 1 );
					$parts = explode( '=', $rawParam, 2 );
					if ( count( $parts ) == 2 ) {
						$lastprintout->setParameter( trim( $parts[0] ), $parts[1] );
					} else {
						$lastprintout->setParameter( trim( $parts[0] ), null );
					}
				}
			} else { // parameter or query

				// #1645
				$parts = $showMode && $name == 0 ? $rawParam : explode( '=', $rawParam, 2 );

				if ( count( $parts ) >= 2 ) {
					// don't trim here, some parameters care for " "
					// #3196 Ensure to decode `-3D` from encodeEq to
					// support things like `|intro=[[File:Foo.png|link=Bar]]`
					$parameters[strtolower( trim( $parts[0] ) )] = str_replace( array( '-3D' ), array( '=' ), $parts[1] );
				} else {
					$queryString .= $rawParam;
				}
			}
		}

		$queryString = str_replace( array( '&lt;', '&gt;', '-3D' ), array( '<', '>', '=' ), $queryString );

		if ( $showMode ) {
			$queryString = "[[:$queryString]]";
		}

		return array( $queryString, $parameters, $printouts);
	}

	/**
	 * Process and answer a query as given by an array of parameters as is
	 * typically produced by the #ask parser function. The parameter
	 * $context defines in what context the query is used, which affects
	 * certain general settings.
	 *
	 * The main task of this function is to preprocess the raw parameters
	 * to obtain actual parameters, printout requests, and the query string
	 * for further processing.
	 *
	 * @since 1.8
	 * @param array $rawParams user-provided list of unparsed parameters
	 * @param integer $outputMode SMW_OUTPUT_WIKI, SMW_OUTPUT_HTML, ...
	 * @param integer $context INLINE_QUERY, SPECIAL_PAGE, CONCEPT_DESC
	 * @param boolean $showMode process like #show parser function?
	 * @return array( SMWQuery, array of IParam )
	 */
	static public function getQueryAndParamsFromFunctionParams( array $rawParams, $outputMode, $context, $showMode, $contextPage = null ) {
		list( $queryString, $params, $printouts ) = self::getComponentsFromFunctionParams( $rawParams, $showMode );

		if ( !$showMode ) {
			self::addThisPrintout( $printouts, $params );
		}

		$params = self::getProcessedParams( $params, $printouts );

		$query  = self::createQuery( $queryString, $params, $context, '', $printouts, $contextPage );
		return array( $query, $params );
	}

	/**
	 * Process and answer a query as given by an array of parameters as is
	 * typically produced by the #ask parser function. The result is formatted
	 * according to the specified $outputformat. The parameter $context defines
	 * in what context the query is used, which affects ceretain general settings.
	 *
	 * The main task of this function is to preprocess the raw parameters to
	 * obtain actual parameters, printout requests, and the query string for
	 * further processing.
	 *
	 * @note Consider using getQueryAndParamsFromFunctionParams() and
	 * getResultFromQuery() instead.
	 * @deprecated Will vanish after release of SMW 1.8.
	 * See SMW_Ask.php for example code on how to get query results from
	 * #ask function parameters.
	 */
	static public function getResultFromFunctionParams( array $rawParams, $outputMode, $context = self::INLINE_QUERY, $showMode = false ) {
		list( $queryString, $params, $printouts ) = self::getComponentsFromFunctionParams( $rawParams, $showMode );

		if ( !$showMode ) {
			self::addThisPrintout( $printouts, $params );
		}

		$params = self::getProcessedParams( $params, $printouts );

		return self::getResultFromQueryString( $queryString, $params, $printouts, SMW_OUTPUT_WIKI, $context );
	}

	/**
	 * Process a query string in SMW's query language and return a formatted
	 * result set as specified by $outputmode. A parameter array of key-value-pairs
	 * constrains the query and determines the serialisation mode for results. The
	 * parameter $context defines in what context the query is used, which affects
	 * certain general settings. Finally, $extraprintouts supplies additional
	 * printout requests for the query results.
	 *
	 * @param string $queryString
	 * @param array $params These need to be the result of a list fed to getProcessedParams
	 * @param $extraPrintouts
	 * @param $outputMode
	 * @param $context
	 *
	 * @return string
	 * @deprecated Will vanish after release of SMW 1.8.
	 * See SMW_Ask.php for example code on how to get query results from
	 * #ask function parameters.
	 */
	static public function getResultFromQueryString( $queryString, array $params, $extraPrintouts, $outputMode, $context = self::INLINE_QUERY ) {

		$query  = self::createQuery( $queryString, $params, $context, '', $extraPrintouts );
		$result = self::getResultFromQuery( $query, $params, $outputMode, $context );


		return $result;
	}

	/**
	 * Create a fully formatted result string from a query and its
	 * parameters. The method takes care of processing various types of
	 * query result. Most cases are handled by printers, but counting and
	 * debugging uses special code.
	 *
	 * @param SMWQuery $query
	 * @param array $params These need to be the result of a list fed to getProcessedParams
	 * @param integer $outputMode
	 * @param integer $context
	 * @since before 1.7, but public only since 1.8
	 *
	 * @return string
	 */
	public static function getResultFromQuery( SMWQuery $query, array $params, $outputMode, $context ) {

		$res = self::getStoreFromParams( $params )->getQueryResult( $query );
		$start = microtime( true );

		if ( ( $query->querymode == SMWQuery::MODE_INSTANCES ) ||
			( $query->querymode == SMWQuery::MODE_NONE ) ) {

			$printer = self::getResultPrinter( $params['format']->getValue(), $context );
			$result = $printer->getResult( $res, $params, $outputMode );

			$query->setOption( SMWQuery::PROC_PRINT_TIME, microtime( true ) - $start );
			return $result;
		} else { // result for counting or debugging is just a string or number

			if ( $res instanceof SMWQueryResult ) {
				$res = $res->getCountValue();
			}

			if ( is_numeric( $res ) ) {
				$res = strval( $res );
			}

			if ( is_string( $res ) ) {
				$result = str_replace( '_', ' ', $params['intro']->getValue() )
					. $res
					. str_replace( '_', ' ', $params['outro']->getValue() )
					. smwfEncodeMessages( $query->getErrors() );
			} else {
				// When no valid result was obtained, $res will be a SMWQueryResult.
				$result = smwfEncodeMessages( $query->getErrors() );
			}

			$query->setOption( SMWQuery::PROC_PRINT_TIME, microtime( true ) - $start );

			return $result;
		}
	}

	private static function getStoreFromParams( array $params ) {
		return ApplicationFactory::getInstance()->getQuerySourceFactory()->get( $params['source']->getValue() );
	}

	/**
	 * Find suitable SMWResultPrinter for the given format. The context in
	 * which the query is to be used determines some basic settings of the
	 * returned printer object. Possible contexts are
	 * SMWQueryProcessor::SPECIAL_PAGE, SMWQueryProcessor::INLINE_QUERY,
	 * SMWQueryProcessor::CONCEPT_DESC.
	 *
	 * @param string $format
	 * @param $context
	 *
	 * @return SMWResultPrinter
	 * @throws MissingResultFormatException
	 */
	static public function getResultPrinter( $format, $context = self::SPECIAL_PAGE ) {
		global $smwgResultFormats;

		if ( !array_key_exists( $format, $smwgResultFormats ) ) {
			throw new ResultFormatNotFoundException( "There is no result format for '$format'." );
		}

		$formatClass = $smwgResultFormats[$format];

		return new $formatClass( $format, ( $context != self::SPECIAL_PAGE ) );
	}

	/**
	 * A function to describe the allowed parameters of a query using
	 * any specific format - most query printers should override this
	 * function.
	 *
	 * @since 1.6.2, return element type changed in 1.8
	 *
	 * @return IParamDefinition[]
	 */
	public static function getParameters() {
		$params = array();

		$allowedFormats = $GLOBALS['smwgResultFormats'];

		foreach ( $GLOBALS['smwgResultAliases'] as $aliases ) {
			$allowedFormats += $aliases;
		}

		$allowedFormats[] = 'auto';

		$params['format'] = array(
			'type' => 'smwformat',
			'default' => 'auto',
		);

		// TODO $params['format']->setToLower( true );
		// TODO $allowedFormats

		$params['source'] = self::getSourceParam();

		$params['limit'] = array(
			'type' => 'integer',
			'default' => $GLOBALS['smwgQDefaultLimit'],
			'negatives' => false,
		);

		$params['offset'] = array(
			'type' => 'integer',
			'default' => 0,
			'negatives' => false,
			'upperbound' => $GLOBALS['smwgQUpperbound'],
		);

		$params['link'] = array(
			'default' => 'all',
			'values' => array( 'all', 'subject', 'none' ),
		);

		$params['sort'] = array(
			'islist' => true,
			'default' => array( '' ), // The empty string represents the page itself, which should be sorted by default.
		);

		$params['order'] = array(
			'islist' => true,
			'default' => array(),
			'values' => array( 'descending', 'desc', 'asc', 'ascending', 'rand', 'random' ),
		);

		$params['headers'] = array(
			'default' => 'show',
			'values' => array( 'show', 'hide', 'plain' ),
		);

		$params['mainlabel'] = array(
			'default' => false,
		);

		$params['intro'] = array(
			'default' => '',
		);

		$params['outro'] = array(
			'default' => '',
		);

		$params['searchlabel'] = array(
			'default' => Message::get( 'smw_iq_moreresults', Message::TEXT, Message::USER_LANGUAGE )
		);

		$params['default'] = array(
			'default' => '',
		);

		// Give grep a chance to find the usages:
		// smw-paramdesc-format, smw-paramdesc-source, smw-paramdesc-limit, smw-paramdesc-offset,
		// smw-paramdesc-link, smw-paramdesc-sort, smw-paramdesc-order, smw-paramdesc-headers,
		// smw-paramdesc-mainlabel, smw-paramdesc-intro, smw-paramdesc-outro, smw-paramdesc-searchlabel,
		// smw-paramdesc-default
		foreach ( $params as $name => &$param ) {
			if ( is_array( $param ) ) {
				$param['message'] = 'smw-paramdesc-' . $name;
			}
		}

		return ParamDefinition::getCleanDefinitions( $params );
	}

	private static function getSourceParam() {
		$sourceValues = is_array( $GLOBALS['smwgQuerySources'] ) ? array_keys( $GLOBALS['smwgQuerySources'] ) : array();

		return array(
			'default' => array_key_exists( 'default', $sourceValues ) ? 'default' : '',
			'values' => $sourceValues,
		);
	}

	/**
	 * Returns the definitions of all parameters supported by the specified format.
	 *
	 * @since 1.8
	 *
	 * @param string $format
	 *
	 * @return ParamDefinition[]
	 */
	public static function getFormatParameters( $format ) {
		SMWParamFormat::resolveFormatAliases( $format );

		if ( array_key_exists( $format, $GLOBALS['smwgResultFormats'] ) ) {
			return ParamDefinition::getCleanDefinitions(
				self::getResultPrinter( $format )->getParamDefinitions( self::getParameters() )
			);
		} else {
			return array();
		}
	}

}
