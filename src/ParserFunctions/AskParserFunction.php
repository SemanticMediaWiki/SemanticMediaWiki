<?php

namespace SMW\ParserFunctions;

use SMW\ParserData;
use SMW\MessageFormatter;
use SMW\Utils\CircularReferenceGuard;
use SMW\ApplicationFactory;
use SMW\ProcessingErrorMsgHandler;
use SMW\DIProperty;
use Parser;
use SMWQueryProcessor as QueryProcessor;
use SMWQuery as Query;

/**
 * Provides the {{#ask}} parser function
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:Ask
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class AskParserFunction {

	/**
	 * @var ParserData
	 */
	private $parserData;

	/**
	 * @var MessageFormatter
	 */
	private $messageFormatter;

	/**
	 * @var CircularReferenceGuard
	 */
	private $circularReferenceGuard;

	/**
	 * @var boolean
	 */
	private $showMode = false;

	/**
	 * @var boolean
	 */
	private $noTrace = false;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param MessageFormatter $messageFormatter
	 * @param CircularReferenceGuard $circularReferenceGuard
	 */
	public function __construct( ParserData $parserData, MessageFormatter $messageFormatter, CircularReferenceGuard $circularReferenceGuard ) {
		$this->parserData = $parserData;
		$this->messageFormatter = $messageFormatter;
		$this->circularReferenceGuard = $circularReferenceGuard;
	}

	/**
	 * Enable showMode (normally only invoked by {{#show}})
	 *
	 * @since 1.9
	 *
	 * @return AskParserFunction
	 */
	public function setShowMode( $mode ) {
		$this->showMode = $mode;
		return $this;
	}

	/**
	 * {{#ask}} is disabled (see $smwgQEnabled)
	 *
	 * @since 1.9
	 *
	 * @return string|null
	 */
	public function isQueryDisabled() {
		return $this->messageFormatter->addFromKey( 'smw_iq_disabled' )->getHtml();
	}

	/**
	 * Parse parameters, return results from the query printer and update the
	 * ParserOutput with meta data from the query
	 *
	 * FIXME $rawParams use IParameterFormatter -> QueryParameterFormatter class
	 * Parse parameters and return query results to the ParserOutput
	 * object and output result data from the QueryProcessor
	 *
	 * @todo $rawParams should be of IParameterFormatter
	 * QueryParameterFormatter class
	 *
	 * @since 1.9
	 *
	 * @param array $functionParams
	 *
	 * @return string|null
	 */
	public function parse( array $functionParams ) {

		// Do we still need this?
		// Reference found in SRF_Exhibit.php, SRF_Ploticus.php, SRF_Timeline.php, SRF_JitGraph.php
		global $smwgIQRunningNumber;
		$smwgIQRunningNumber++;

		$this->applicationFactory = ApplicationFactory::getInstance();

		$functionParams = $this->prepareFunctionParameters(
			$functionParams
		);

		if ( !$this->noTrace ) {
			$this->noTrace = $this->parserData->getOption( ParserData::NO_QUERY_DEPENDENCY_TRACE );
		}

		// No trace on queries invoked by special pages
		if ( $this->parserData->getTitle()->getNamespace() === NS_SPECIAL ) {
			$this->noTrace = true;
		}

		$result = $this->doFetchResultsFromFunctionParameters(
			$functionParams
		);

		$this->parserData->pushSemanticDataToParserOutput();

		// 1.23+ add options so changes are recognized in case of:
		// - 'userlang' will trigger a cache fragmentation by user language
		// - 'dateformat'  will trigger a cache fragmentation by date preference
		if ( method_exists( $this->parserData->getOutput(), 'recordOption' ) ) {
			$this->parserData->getOutput()->recordOption( 'userlang' );
			$this->parserData->getOutput()->recordOption( 'dateformat' );
		}

		return $result;
	}

	private function prepareFunctionParameters( array $functionParams ) {

		// Remove parser object from parameters array
		if( isset( $functionParams[0] ) && $functionParams[0] instanceof Parser ) {
			array_shift( $functionParams );
		}

		// Filter invalid parameters
		foreach ( $functionParams as $key => $value ) {

			// First and marked printrequests
			if (  $key == 0 || ( $value !== '' && $value{0} === '?' ) ) {
				continue;
			}

			// Filter parameters that can not be split into
			// argument=value
			if ( strpos( $value, '=' ) === false ) {
				unset( $functionParams[$key] );
			}
		}

		return $functionParams;
	}

	private function doFetchResultsFromFunctionParameters( array $functionParams ) {

		$contextPage = $this->parserData->getSubject();

		if ( $this->noTrace === true ) {
			$contextPage = null;
		}

		list( $query, $this->params ) = QueryProcessor::getQueryAndParamsFromFunctionParams(
			$functionParams,
			SMW_OUTPUT_WIKI,
			QueryProcessor::INLINE_QUERY,
			$this->showMode,
			$contextPage
		);

		$query->setOption( Query::PROC_CONTEXT, 'AskParserFunction' );
		$query->setOption( Query::NO_DEPENDENCY_TRACE, $this->noTrace );

		$queryHash = $query->getHash();

		$this->circularReferenceGuard->mark( $queryHash );

		// If we caught in a circular loop (due to a template referencing to itself)
		// then we stop here before the next query execution to avoid an infinite
		// self-reference
		if ( $this->circularReferenceGuard->isCircularByRecursionFor( $queryHash ) ) {
			return '';
		}

		$result = QueryProcessor::getResultFromQuery(
			$query,
			$this->params,
			SMW_OUTPUT_WIKI,
			QueryProcessor::INLINE_QUERY
		);

		$format = $this->params['format']->getValue();

		// FIXME Parser should be injected into the ResultPrinter
		// Enables specific formats to import its annotation data from
		// a recursive parse process in the result format
		// e.g. using ask query to search/set an invert property value
		if ( isset( $this->params['import-annotation'] ) && $this->params['import-annotation']->getValue() ) {
			$this->parserData->importFromParserOutput( $GLOBALS['wgParser']->getOutput() );
		}

		$this->circularReferenceGuard->unmark( $queryHash );

		// In case of an query error add a marker to the subject for discoverability
		// of a failed query, don't bail-out as we can have results and errors
		// at the same time
		$this->addProcessingError( $query->getErrors() );

		$this->addQueryProfile(
			$query,
			$format
		);

		return $result;
	}

	private function addQueryProfile( $query, $format ) {

		$settings = $this->applicationFactory->getSettings();

		// If the smwgQueryProfiler is marked with FALSE then just don't create a profile.
		if ( ( $queryProfiler = $settings->get( 'smwgQueryProfiler' ) ) === false || $this->noTrace === true ) {
			return;
		}

		if ( !isset( $queryProfiler['smwgQueryDurationEnabled'] ) || $queryProfiler['smwgQueryDurationEnabled'] === false ) {
			$query->setOption( Query::PROC_QUERY_TIME, 0 );
		}

		if ( isset( $queryProfiler['smwgQueryParametersEnabled'] ) ) {
			$query->setOption( Query::OPT_PARAMETERS, $queryProfiler['smwgQueryParametersEnabled'] );
		}

		$query->setContextPage(
			$this->parserData->getSubject()
		);

		$profileAnnotatorFactory = $this->applicationFactory->getQueryFactory()->newProfileAnnotatorFactory();

		$combinedProfileAnnotator = $profileAnnotatorFactory->newCombinedProfileAnnotator(
			$query,
			$format
		);

		$combinedProfileAnnotator->pushAnnotationsTo(
			$this->parserData->getSemanticData()
		);
	}

	private function addProcessingError( $errors ) {

		if ( $errors === array() ) {
			return;
		}

		$processingErrorMsgHandler = new ProcessingErrorMsgHandler(
			$this->parserData->getSubject()
		);

		$property = new DIProperty( '_ASK' );

		foreach ( $errors as $error ) {
			$processingErrorMsgHandler->addToSemanticData(
				$this->parserData->getSemanticData(),
				$processingErrorMsgHandler->newErrorContainerFromMsg( $error, $property )
			);
		}
	}

}
