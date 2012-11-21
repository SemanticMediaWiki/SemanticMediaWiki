<?php
/**
 * This file contains a static class for accessing functions to generate and execute
 * semantic queries and to serialise their results.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup SMWQuery
 * @author Markus Krötzsch
 */

/**
 * Static class for accessing functions to generate and execute semantic queries
 * and to serialise their results.
 * @ingroup SMWQuery
 */
class SMWQueryProcessor {

	// "query contexts" define restrictions during query parsing and
	// are used to preconfigure query (e.g. special pages show no further
	// results link):
	const SPECIAL_PAGE = 0; // query for special page
	const INLINE_QUERY = 1; // query for inline use
	const CONCEPT_DESC = 2; // query for concept definition

	/**
	 * Takes an array of unprocessed parameters, processes them using
	 * Validator, and returns them.
	 *
	 * Both input and output arrays are
	 * param name (string) => param value (mixed)
	 *
	 * @since 1.6.2
	 * The return value changed in SMW 1.8 from an array with result values
	 * to an array with IParam objects.
	 *
	 * @param array $params
	 * @param array $printRequests
	 * @param boolean $unknownInvalid
	 *
	 * @return array of IParam
	 */
	public static function getProcessedParams( array $params, array $printRequests = array(), $unknownInvalid = true ) {
		$validator = self::getValidatorForParams( $params, $printRequests, $unknownInvalid );
		$validator->validateParameters();
		return $validator->getParameters();
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
	 * @return Validator
	 */
	public static function getValidatorForParams( array $params, array $printRequests = array(), $unknownInvalid = true ) {
		$paramDefinitions = self::getParameters();

		$paramDefinitions['format']->setPrintRequests( $printRequests );

		$validator = new Validator( 'SMW query', $unknownInvalid );
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
	static public function createQuery( $queryString, array $params, $context = self::INLINE_QUERY, $format = '', array $extraPrintouts = array() ) {
		global $smwgQDefaultNamespaces, $smwgQFeatures, $smwgQConceptFeatures;

		// parse query:
		$queryfeatures = ( $context == self::CONCEPT_DESC ) ? $smwgQConceptFeatures : $smwgQFeatures;
		$qp = new SMWQueryParser( $queryfeatures );
		$qp->setDefaultNamespaces( $smwgQDefaultNamespaces );
		$desc = $qp->getQueryDescription( $queryString );

		if ( $format === '' || is_null( $format ) ) {
			$format = $params['format']->getValue();
		}

		if ( $format == 'count' ) {
			$querymode = SMWQuery::MODE_COUNT;
		} elseif ( $format == 'debug' ) {
			$querymode = SMWQuery::MODE_DEBUG;
		} else {
			$printer = self::getResultPrinter( $format, $context );
			$querymode = $printer->getQueryMode( $context );
		}

		$query = new SMWQuery( $desc, ( $context != self::SPECIAL_PAGE ), ( $context == self::CONCEPT_DESC ) );
		$query->setQueryString( $queryString );
		$query->setExtraPrintouts( $extraPrintouts );
		$query->setMainLabel( $params['mainlabel']->getValue() );
		$query->addErrors( $qp->getErrors() ); // keep parsing errors for later output

		// set mode, limit, and offset:
		$query->querymode = $querymode;
		if ( ( array_key_exists( 'offset', $params ) ) && ( is_int( $params['offset']->getValue() + 0 ) ) ) {
			$query->setOffset( max( 0, trim( $params['offset']->getValue() ) + 0 ) );
		}

		if ( $query->querymode == SMWQuery::MODE_COUNT ) { // largest possible limit for "count", even inline
			global $smwgQMaxLimit;
			$query->setOffset( 0 );
			$query->setLimit( $smwgQMaxLimit, false );
		} else {
			if ( ( array_key_exists( 'limit', $params ) ) && ( is_int( trim( $params['limit']->getValue() ) + 0 ) ) ) {
				$query->setLimit( max( 0, trim( $params['limit']->getValue() ) + 0 ) );
				if ( ( trim( $params['limit']->getValue() ) + 0 ) < 0 ) { // limit < 0: always show further results link only
					$query->querymode = SMWQuery::MODE_NONE;
				}
			} else {
				global $smwgQDefaultLimit;
				$query->setLimit( $smwgQDefaultLimit );
			}
		}

		$defaultSort = $format === 'rss' ? 'DESC' : 'ASC';
		$sort = self::getSortKeys( $params['sort']->getValue(), $params['order']->getValue(), $defaultSort );

		$query->sortkeys = $sort['keys'];
		$query->addErrors( $sort['errors'] );
		$query->sort = count( $query->sortkeys ) > 0; // TODO: Why would we do this here?

		return $query;
	}

	/**
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
		$orders = array();
		$sortKeys = array();
		$sortErros = array();

		foreach ( $orderParam as $key => $order ) {
			$order = strtolower( trim( $order ) );
			if ( ( $order == 'descending' ) || ( $order == 'reverse' ) || ( $order == 'desc' ) ) {
				$orders[$key] = 'DESC';
			} elseif ( ( $order == 'random' ) || ( $order == 'rand' ) ) {
				$orders[$key] = 'RANDOM';
			} else {
				$orders[$key] = 'ASC';
			}
		}

		foreach ( $sortParam as $sort ) {
			$sortKey = false;

			// An empty string indicates we mean the page, such as element 0 on the next line.
			// sort=,Some property
			if ( trim( $sort ) === '' ) {
				$sortKey = '';
			}
			else {
				$propertyValue = SMWPropertyValue::makeUserProperty( trim( $sort ) );

				if ( $propertyValue->isValid() ) {
					$sortKey = $propertyValue->getDataItem()->getKey();
				} else {
					$sortErros = array_merge( $sortErros, $propertyValue->getErrors() );
				}
			}

			if ( $sortKey !== false ) {
				$order = empty( $orders ) ? $defaultSort : array_shift( $orders );
				$sortKeys[$sortKey] = $order;
			}
		}

		// If more sort arguments are provided then properties, assume the first one is for the page.
		// TODO: we might want to add errors if there is more then one.
		if ( !array_key_exists( '', $sortKeys ) && !empty( $orders ) ) {
			$sortKeys[''] = array_shift( $orders );
		}

		return array( 'keys' => $sortKeys, 'errors' => $sortErros );
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
				array_unshift( $printRequests, new SMWPrintRequest(
					SMWPrintRequest::PRINT_THIS,
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
		foreach ( $rawParams as $name => $rawParam ) {
			// special handling for arrays - this can happen if the
			// parameter came from a checkboxes input in Special:Ask:
			if ( is_array( $rawParam ) ) {
				$rawParam = implode( ',', array_keys( $rawParam ) );
			}

			// accept 'name' => 'value' just as '' => 'name=value':
			if ( is_string( $name ) && ( $name !== '' ) ) {
				$rawParam = $name . '=' . $rawParam;
			}

			if ( $rawParam === '' ) {
			} elseif ( $rawParam { 0 } == '?' ) { // print statement
				$rawParam = substr( $rawParam, 1 );
				$lastprintout = self::getSMWPrintRequestFromString( $rawParam, $showMode );
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
				$parts = explode( '=', $rawParam, 2 );

				if ( count( $parts ) >= 2 ) {
					// don't trim here, some parameters care for " "
					$parameters[strtolower( trim( $parts[0] ) )] = $parts[1];
				} else {
					$queryString .= $rawParam;
				}
			}
		}

		$queryString = str_replace( array( '&lt;', '&gt;' ), array( '<', '>' ), $queryString );
		if ( $showMode ) {
			$queryString = "[[:$queryString]]";
		}

		return array( $queryString, $parameters, $printouts);
	}

	/**
	 * Create an SMWPrintRequest object from a string description as one
	 * would normally use in #ask and related inputs. The string must start
	 * with a "?" and may contain label and formatting parameters after "="
	 * or "#", respectively. However, further parameters, given in #ask by
	 * "|+param=value" are not allowed here; they must be added
	 * individually.
	 *
	 * @param string $printRequestString
	 * @param boolean $showMode
	 * @return SMWPrintRequest||null
	 * @since 1.8
	 */
	static protected function getSMWPrintRequestFromString( $printRequestString, $showMode ) {
		global $wgContLang;

		$parts = explode( '=', $printRequestString, 2 );
		$propparts = explode( '#', $parts[0], 2 );

		$data = null;

		if ( trim( $propparts[0] ) === '' ) { // print "this"
			$printmode = SMWPrintRequest::PRINT_THIS;
			$label = ''; // default
		} elseif ( $wgContLang->getNsText( NS_CATEGORY ) == ucfirst( trim( $propparts[0] ) ) ) { // print categories
			$printmode = SMWPrintRequest::PRINT_CATS;
			$label = $showMode ? '' : $wgContLang->getNSText( NS_CATEGORY ); // default
		} else { // print property or check category
			$title = Title::newFromText( trim( $propparts[0] ), SMW_NS_PROPERTY ); // trim needed for \n
			if ( is_null( $title ) ) { // not a legal property/category name; give up
				return null;
			}

			if ( $title->getNamespace() == NS_CATEGORY ) {
				$printmode = SMWPrintRequest::PRINT_CCAT;
				$data = $title;
				$label = $showMode ? '' : $title->getText();  // default
			} else { // enforce interpretation as property (even if it starts with something that looks like another namespace)
				$printmode = SMWPrintRequest::PRINT_PROP;
				$data = SMWPropertyValue::makeUserProperty( trim( $propparts[0] ) );
				if ( !$data->isValid() ) { // not a property; give up
					return null;
				}
				$label = $showMode ? '' : $data->getWikiValue();  // default
			}
		}

		if ( count( $propparts ) == 1 ) { // no outputformat found, leave empty
			$propparts[] = false;
		} elseif ( trim( $propparts[1] ) === '' ) { // "plain printout", avoid empty string to avoid confusions with "false"
			$propparts[1] = '-';
		}

		if ( count( $parts ) > 1 ) { // label found, use this instead of default
			$label = trim( $parts[1] );
		}

		try {
			return new SMWPrintRequest( $printmode, $label, $data, trim( $propparts[1] ) );
		} catch ( InvalidArgumentException $e ) { // something still went wrong; give up
			return null;
		}
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
	static public function getQueryAndParamsFromFunctionParams( array $rawParams, $outputMode, $context, $showMode ) {
		list( $queryString, $params, $printouts ) = self::getComponentsFromFunctionParams( $rawParams, $showMode );

		if ( !$showMode ) {
			self::addThisPrintout( $printouts, $params );
		}

		$params = self::getProcessedParams( $params, $printouts );

		$query  = self::createQuery( $queryString, $params, $context, '', $printouts );
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
		wfProfileIn( 'SMWQueryProcessor::getResultFromQueryString (SMW)' );

		$query  = self::createQuery( $queryString, $params, $context, '', $extraPrintouts );
		$result = self::getResultFromQuery( $query, $params, $outputMode, $context );

		wfProfileOut( 'SMWQueryProcessor::getResultFromQueryString (SMW)' );

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
		wfProfileIn( 'SMWQueryProcessor::getResultFromQuery (SMW)' );

		$res = $params['source']->getValue()->getQueryResult( $query );

		if ( ( $query->querymode == SMWQuery::MODE_INSTANCES ) ||
			( $query->querymode == SMWQuery::MODE_NONE ) ) {
			wfProfileIn( 'SMWQueryProcessor::getResultFromQuery-printout (SMW)' );

			$printer = self::getResultPrinter( $params['format']->getValue(), $context );
			$result = $printer->getResult( $res, $params, $outputMode );

			wfProfileOut( 'SMWQueryProcessor::getResultFromQuery-printout (SMW)' );
			wfProfileOut( 'SMWQueryProcessor::getResultFromQuery (SMW)' );

			return $result;
		} else { // result for counting or debugging is just a string or number
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

			wfProfileOut( 'SMWQueryProcessor::getResultFromQuery (SMW)' );

			return $result;
		}
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
	 * @throws MWException if no printer is known for the given format
	 */
	static public function getResultPrinter( $format, $context = self::SPECIAL_PAGE ) {
		global $smwgResultFormats;

		if ( !array_key_exists( $format, $smwgResultFormats ) ) {
			throw new MWException( "There is no result format for '$format'." );
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
	 * @return array of IParamDefinition
	 */
	public static function getParameters() {
		$params = array();

		$allowedFormats = $GLOBALS['smwgResultFormats'];

		foreach ( $GLOBALS['smwgResultAliases'] as $aliases ) {
			$allowedFormats += $aliases;
		}

		$allowedFormats[] = 'auto';

		$params['format'] = new SMWParamFormat( 'format', 'auto' );
		$params['format']->setToLower( true );
		// TODO:$allowedFormats


		$params['source'] = new SMWParamSource( 'source' );

		$params['limit'] = array(
			'type' => 'integer',
			'default' => $GLOBALS['smwgQDefaultLimit'],
			'negatives' => false,
		);

		$params['offset'] = array(
			'type' => 'integer',
			'default' => 0,
			'negatives' => false,
			'upperbound' => 5000 // TODO: make setting
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
			'default' => wfMessage( 'smw_iq_moreresults' )->inContentLanguage()->text(),
		);

		$params['default'] = array(
			'default' => '',
		);

		foreach ( $params as $name => &$param ) {
			if ( is_array( $param ) ) {
				$param['message'] = 'smw-paramdesc-' . $name;
			}
		}

		return ParamDefinition::getCleanDefinitions( $params );
	}

	/**
	 * Returns the definitions of all parameters supported by the specified format.
	 *
	 * @since 1.8
	 *
	 * @param string $format
	 *
	 * @return array of IParamDefinition
	 */
	public static function getFormatParameters( $format ) {
		SMWParamFormat::resolveFormatAliases( $format );

		if ( array_key_exists( $format, $GLOBALS['smwgResultFormats'] ) ) {
			return ParamDefinition::getCleanDefinitions(
				SMWQueryProcessor::getResultPrinter( $format )->getParamDefinitions( SMWQueryProcessor::getParameters() )
			);
		} else {
			return array();
		}
	}

}
