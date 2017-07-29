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
use SMW\Query\DeferredQuery;
use SMW\PostProcHandler;

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
	 * Fixed identifier for a deferred query request
	 */
	const DEFERRED_REQUEST = '@deferred';

	/**
	 * Fixed identifier
	 */
	const NO_TRACE = '@notrace';

	/**
	 * Fixed identifier to signal to the PostProcHandler that a post update is
	 * required with the output being used as input value for an annotation.
	 */
	const IS_ANNOTATION = '@annotation';

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
	 * @var ExpensiveFuncExecutionWatcher
	 */
	private $expensiveFuncExecutionWatcher;

	/**
	 * @var boolean
	 */
	private $showMode = false;

	/**
	 * @var integer
	 */
	private $context = QueryProcessor::INLINE_QUERY;

	/**
	 * @var PostProcHandler
	 */
	private $postProcHandler;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param MessageFormatter $messageFormatter
	 * @param CircularReferenceGuard $circularReferenceGuard
	 * @param ExpensiveFuncExecutionWatcher $expensiveFuncExecutionWatcher
	 */
	public function __construct( ParserData $parserData, MessageFormatter $messageFormatter, CircularReferenceGuard $circularReferenceGuard, ExpensiveFuncExecutionWatcher $expensiveFuncExecutionWatcher ) {
		$this->parserData = $parserData;
		$this->messageFormatter = $messageFormatter;
		$this->circularReferenceGuard = $circularReferenceGuard;
		$this->expensiveFuncExecutionWatcher = $expensiveFuncExecutionWatcher;
	}

	/**
	 * @since 3.0
	 *
	 * @param PostProcHandler $postProcHandler
	 */
	public function setPostProcHandler( PostProcHandler $postProcHandler ) {
		$this->postProcHandler = $postProcHandler;
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
		$GLOBALS['smwgIQRunningNumber']++;
		$result = '';

		list( $functionParams, $extraKeys ) = $this->prepareFunctionParameters(
			$functionParams
		);

		if ( !isset( $extraKeys[self::NO_TRACE] ) ) {
			$extraKeys[self::NO_TRACE] = $this->parserData->getOption( ParserData::NO_QUERY_DEPENDENCY_TRACE );
		}

		// No trace on queries invoked by special pages
		if ( $this->parserData->getTitle()->getNamespace() === NS_SPECIAL ) {
			$extraKeys[self::NO_TRACE] = true;
		}

		$result = $this->doFetchResultsFromFunctionParameters(
			$functionParams,
			$extraKeys
		);

		if ( $this->context === QueryProcessor::DEFERRED_QUERY ) {
			DeferredQuery::registerResourceModules( $this->parserData->getOutput() );
		}

		$this->parserData->pushSemanticDataToParserOutput();

		// 'userlang' will trigger a cache fragmentation by user language
		$this->parserData->addExtraParserKey( 'userlang' );

		// 'dateformat'  will trigger a cache fragmentation by date preference
		$this->parserData->addExtraParserKey( 'dateformat' );

		return $result;
	}

	private function prepareFunctionParameters( array $functionParams ) {

		// Remove parser object from parameters array
		if( isset( $functionParams[0] ) && $functionParams[0] instanceof Parser ) {
			array_shift( $functionParams );
		}

		$extraKeys = array();

		// Filter invalid parameters
		foreach ( $functionParams as $key => $value ) {

			if ( $value === self::DEFERRED_REQUEST ) {
				$this->context = QueryProcessor::DEFERRED_QUERY;
				unset( $functionParams[$key] );
				continue;
			}

			if ( $value === self::NO_TRACE ) {
				$extraKeys[self::NO_TRACE] = true;
				unset( $functionParams[$key] );
				continue;
			}

			if ( $value === self::IS_ANNOTATION ) {
				$extraKeys[self::IS_ANNOTATION] = true;
				unset( $functionParams[$key] );
				continue;
			}

			// @see ParserOptionsRegister hook, use registered `localTime` key
			if ( strpos( $value, '#LOCL#TO' ) !== false ) {
				$this->parserData->addExtraParserKey( 'localTime' );
			}

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

		return array( $functionParams, $extraKeys );
	}

	private function doFetchResultsFromFunctionParameters( array $functionParams, array $extraKeys ) {

		$contextPage = $this->parserData->getSubject();

		if ( $extraKeys[self::NO_TRACE] === true ) {
			$contextPage = null;
		}

		list( $query, $this->params ) = QueryProcessor::getQueryAndParamsFromFunctionParams(
			$functionParams,
			SMW_OUTPUT_WIKI,
			$this->context,
			$this->showMode,
			$contextPage
		);

		if ( ( $result = $this->hasReachedExpensiveExecutionLimit( $query ) ) !== false ) {
			return $result;
		}

		$query->setOption( Query::PROC_CONTEXT, 'AskParserFunction' );
		$query->setOption( Query::NO_DEPENDENCY_TRACE, $extraKeys[self::NO_TRACE] );
		$query->setOption( 'request.action', $this->parserData->getOption( 'request.action' ) );

		$queryHash = $query->getHash();

		if ( $this->postProcHandler !== null && isset( $extraKeys[self::IS_ANNOTATION] ) ) {
			$this->postProcHandler->addQueryRef( $queryHash );
		}

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
			$this->context
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
		$this->expensiveFuncExecutionWatcher->incrementExpensiveCount( $query );

		// In case of an query error add a marker to the subject for discoverability
		// of a failed query, don't bail-out as we can have results and errors
		// at the same time
		$this->addProcessingError( $query->getErrors() );

		$this->addQueryProfile(
			$query,
			$format,
			$extraKeys[self::NO_TRACE]
		);

		return $result;
	}

	private function hasReachedExpensiveExecutionLimit( $query ) {

		if ( $this->expensiveFuncExecutionWatcher->hasReachedExpensiveLimit( $query ) === false ) {
			return false;
		}

		// Adding to error in order to be discoverable
		$this->addProcessingError( array( 'smw-parser-function-expensive-execution-limit' ) );

		return $this->messageFormatter->addFromKey( 'smw-parser-function-expensive-execution-limit' )->getHtml();
	}

	private function addQueryProfile( $query, $format, $noTrace ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		// If the smwgQueryProfiler is marked with FALSE then just don't create a profile.
		if ( ( $queryProfiler = $settings->get( 'smwgQueryProfiler' ) ) === false || $noTrace === true ) {
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

		$profileAnnotatorFactory = $applicationFactory->getQueryFactory()->newProfileAnnotatorFactory();

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
