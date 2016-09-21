<?php

namespace SMW;

use Parser;
use SMWQueryProcessor;
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
	 * object and output result data from the SMWQueryProcessor
	 *
	 * @todo $rawParams should be of IParameterFormatter
	 * QueryParameterFormatter class
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 *
	 * @return string|null
	 */
	public function parse( array $rawParams ) {

		// Do we still need this?
		// Reference found in SRF_Exhibit.php, SRF_Ploticus.php, SRF_Timeline.php, SRF_JitGraph.php
		global $smwgIQRunningNumber;
		$smwgIQRunningNumber++;

		$this->applicationFactory = ApplicationFactory::getInstance();

		$rawParams = $this->prepareRawParameters(
			$rawParams
		);

		$result = $this->doFetchResultsForRawParameters(
			$rawParams
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

	private function prepareRawParameters( array $rawParams ) {

		// Remove parser object from parameters array
		if( isset( $rawParams[0] ) && $rawParams[0] instanceof Parser ) {
			array_shift( $rawParams );
		}

		// Filter invalid parameters
		foreach ( $rawParams as $key => $value ) {

			// First and marked printrequests
			if (  $key == 0 || ( $value !== '' && $value{0} === '?' ) ) {
				continue;
			}

			// Filter parameters that can not be split into
			// argument=value
			if ( strpos( $value, '=' ) === false ) {
				unset( $rawParams[$key] );
			}
		}

		return $rawParams;
	}

	private function doFetchResultsForRawParameters( array $rawParams ) {

		$contextPage = $this->parserData->getSubject();

		list( $query, $this->params ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY,
			$this->showMode,
			$contextPage
		);

		$query->setContextPage(
			$contextPage
		);

		$queryHash = $query->getHash();

		$this->circularReferenceGuard->mark( $queryHash );

		// If we caught in a circular loop (due to a template referencing to itself)
		// then we stop here before the next query execution to avoid an infinite
		// self-reference
		if ( $this->circularReferenceGuard->isCircularByRecursionFor( $queryHash ) ) {
			return '';
		}

		$result = SMWQueryProcessor::getResultFromQuery(
			$query,
			$this->params,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY
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
		if ( $query->getErrors() !== array() ) {
			$this->addProcessingError( $query->getErrors() );
		}

		$this->createQueryProfile(
			$query,
			$format
		);

		return $result;
	}

	private function createQueryProfile( $query, $format ) {

		// If the smwgQueryProfiler is marked with FALSE then just don't create a profile.
		if ( $this->applicationFactory->getSettings()->get( 'smwgQueryProfiler' ) === false ) {
			return;
		}

		$query->setOption(
			'smwgQueryDurationEnabled',
			$this->applicationFactory->getSettings()->get( 'smwgQueryDurationEnabled' )
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

		$processingErrorMsgHandler = new ProcessingErrorMsgHandler(
			$this->parserData->getSubject()
		);

		foreach ( $errors as $error ) {
			$processingErrorMsgHandler->pushTo(
				$this->parserData->getSemanticData(),
				$processingErrorMsgHandler->getErrorContainerFromMsg( $error, new DIProperty( '_ASK' ) )
			);
		}
	}

}
