<?php

namespace SMW\ParserFunctions;

use Parser;
use SMW\ApplicationFactory;
use SMW\DIProperty;
use SMW\MessageFormatter;
use SMW\Parser\RecursiveTextProcessor;
use SMW\ParserData;
use SMW\PostProcHandler;
use SMW\ProcessingErrorMsgHandler;
use SMW\Query\Deferred;
use SMW\Utils\CircularReferenceGuard;
use SMWQuery as Query;
use SMWQueryProcessor as QueryProcessor;

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
	 * @var RecursiveTextProcessor
	 */
	private $recursiveTextProcessor;

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
	 * @since 3.0
	 *
	 * @param RecursiveTextProcessor $recursiveTextProcessor
	 */
	public function setRecursiveTextProcessor( RecursiveTextProcessor $recursiveTextProcessor ) {
		$this->recursiveTextProcessor = $recursiveTextProcessor;
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
			Deferred::registerResources( $this->parserData->getOutput() );
		}

		$this->parserData->copyToParserOutput();

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

		$extraKeys = [];
		$previous = false;

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

			// Skip the first (being the condition) and other marked
			// printrequests
			if ( $key == 0 || ( $value !== '' && $value{0} === '?' ) ) {
				continue;
			}

			// The MW parser swallows any `|` char (as it is used as field
			// separator) hence make an educated guess about the condition when
			// it contains `[[`...`]]` and the previous value was empty (expected
			// due to ||) then the string should be concatenated and interpret
			// as [[Foo]] || [[Bar]]
			if (
				( $key > 0 && $previous === '' ) &&
				( strpos( $value, '[[' ) !== false && strpos( $value, ']]' ) !== false ) ) {
				$functionParams[0] .= " || $value";
				unset( $functionParams[$key] );
			}

			// Filter parameters that can not be split into
			// argument=value
			if ( strpos( $value, '=' ) === false ) {
				unset( $functionParams[$key] );
			}

			$previous = $value;
		}

		return [ $functionParams, $extraKeys ];
	}

	private function doFetchResultsFromFunctionParameters( array $functionParams, array $extraKeys ) {

		$contextPage = $this->parserData->getSubject();
		$action = $this->parserData->getOption( 'request.action' );
		$status = [];

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
		$query->setOption( 'request.action', $action );

		$queryHash = $query->getHash();

		if ( $this->postProcHandler !== null && isset( $extraKeys[self::IS_ANNOTATION] ) ) {
			$status[] = 100;
			$this->postProcHandler->addUpdate( $query );
		}

		if ( $this->context === QueryProcessor::DEFERRED_QUERY ) {
			$status[] = 200;
		}

		$this->circularReferenceGuard->mark( $queryHash );

		// If we caught in a circular loop (due to a template referencing to itself)
		// then we stop here before the next query execution to avoid an infinite
		// self-reference
		if ( $this->circularReferenceGuard->isCircular( $queryHash ) ) {
			return '';
		}

		// #3230
		// If the query contains a self reference (embedding page is part of the
		// query condition) for a `edit` action then set an extra key so that the
		// parser uses a different parser cache hereby allows for an additional
		// parse on the next GET request to retrieve newly stored values that may
		// have been appended during the `edit`.
		if ( ( $action === 'submit' || $action === 'stashedit' ) && $query->getOption( 'self.reference' ) ) {
			$this->parserData->addExtraParserKey( 'smwq' );
		}

		QueryProcessor::setRecursiveTextProcessor(
			$this->recursiveTextProcessor
		);

		$params = [];

		foreach ( $this->params as $key => $value) {
			$params[$key] = $value->getValue();
		}

		$query->setOption( 'query.params', $params );

		// Only request a result_hash in case the `check-query` is enabled
		if ( $this->postProcHandler !== null ) {
			$query->setOption( 'calc.result_hash', $this->postProcHandler->getOption( 'check-query' ) );
		}

		$result = QueryProcessor::getResultFromQuery(
			$query,
			$this->params,
			SMW_OUTPUT_WIKI,
			$this->context
		);

		if ( $this->postProcHandler !== null && $this->context !== QueryProcessor::DEFERRED_QUERY ) {
			$this->postProcHandler->addCheck( $query );
		}

		$format = $this->params['format']->getValue();

		if ( $this->recursiveTextProcessor !== null ) {
			$this->recursiveTextProcessor->copyData( $this->parserData );
		}

		$this->circularReferenceGuard->unmark( $queryHash );
		$this->expensiveFuncExecutionWatcher->incrementExpensiveCount( $query );

		// In case of an query error add a marker to the subject for discoverability
		// of a failed query, don't bail-out as we can have results and errors
		// at the same time
		$this->addProcessingError( $query->getErrors() );

		$query->setOption( Query::PROC_STATUS_CODE, $status );

		$this->addQueryProfile(
			$query,
			$format,
			$extraKeys
		);

		return $result;
	}

	private function hasReachedExpensiveExecutionLimit( $query ) {

		if ( $this->expensiveFuncExecutionWatcher->hasReachedExpensiveLimit( $query ) === false ) {
			return false;
		}

		// Adding to error in order to be discoverable
		$this->addProcessingError( [ 'smw-parser-function-expensive-execution-limit' ] );

		return $this->messageFormatter->addFromKey( 'smw-parser-function-expensive-execution-limit' )->getHtml();
	}

	private function addQueryProfile( $query, $format, $extraKeys ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		// If the smwgQueryProfiler is marked with FALSE then just don't create a profile.
		if ( $settings->get( 'smwgQueryProfiler' ) === false || $extraKeys[self::NO_TRACE] === true ) {
			return;
		}

		if ( !$settings->isFlagSet( 'smwgQueryProfiler', SMW_QPRFL_DUR ) ) {
			$query->setOption( Query::PROC_QUERY_TIME, 0 );
		}

		if ( $settings->isFlagSet( 'smwgQueryProfiler', SMW_QPRFL_PARAMS ) ) {
			$query->setOption( Query::OPT_PARAMETERS, true );
		}

		$query->setContextPage(
			$this->parserData->getSubject()
		);

		$profileAnnotatorFactory = $applicationFactory->getQueryFactory()->newProfileAnnotatorFactory();

		$profileAnnotator = $profileAnnotatorFactory->newProfileAnnotator(
			$query,
			$format
		);

		$profileAnnotator->pushAnnotationsTo(
			$this->parserData->getSemanticData()
		);
	}

	private function addProcessingError( $errors ) {

		if ( $errors === [] ) {
			return;
		}

		$processingErrorMsgHandler = new ProcessingErrorMsgHandler(
			$this->parserData->getSubject()
		);

		foreach ( $errors as $error ) {

			if ( ( $property = $processingErrorMsgHandler->grepPropertyFromRestrictionErrorMsg( $error ) ) === null ) {
				$property = new DIProperty( '_ASK' );
			}

			$container = $processingErrorMsgHandler->newErrorContainerFromMsg(
				$error,
				$property
			);

			$processingErrorMsgHandler->addToSemanticData(
				$this->parserData->getSemanticData(),
				$container
			);
		}
	}

}
