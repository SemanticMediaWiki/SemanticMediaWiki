<?php

namespace SMW;

use Parser;
use SMWQueryProcessor;

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
	 * @var array
	 */
	private $enabledFormatsThatSupportRecursiveParse = array();

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

		// Counter for what? Where and for what is it used?
		global $smwgIQRunningNumber;
		$smwgIQRunningNumber++;

		// Remove parser object from parameters array
		if( isset( $rawParams[0] ) && $rawParams[0] instanceof Parser ) {
			array_shift( $rawParams );
		}

		$this->applicationFactory = ApplicationFactory::getInstance();

		$result = $this->doFetchResultsForRawParameters(
			$rawParams
		);

		$this->parserData->pushSemanticDataToParserOutput();

		return $result;
	}

	private function doFetchResultsForRawParameters( array $rawParams ) {

		// FIXME QueryDuration should be a property of the QueryProcessor or
		// QueryEngine but since we don't want to open the pandora's box and
		// increase issues within the current QueryProcessor implementation
		// we will track the time outside of the actual execution framework
		$queryDuration = 0;
		$start = microtime( true );

		list( $this->query, $this->params ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY,
			$this->showMode
		);

		$this->query->setSubject(
			DIWIkiPage::newFromTitle( $this->parserData->getTitle() )
		);

		$queryHash = $this->query->getHash();

		$this->circularReferenceGuard->mark( $queryHash );

		// If we caught in a circular loop (due to a template referencing to itself)
		// then we stop here before the next query execution to avoid an infinite
		// self-reference
		if ( $this->circularReferenceGuard->isCircularByRecursionFor( $queryHash ) ) {
			return '';
		}

		$result = SMWQueryProcessor::getResultFromQuery(
			$this->query,
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

		if ( $this->applicationFactory->getSettings()->get( 'smwgQueryDurationEnabled' ) ) {
			$queryDuration = microtime( true ) - $start;
		}

		$this->circularReferenceGuard->unmark( $queryHash );

		$this->createQueryProfile(
			$this->query,
			$format,
			$queryDuration
		);

		return $result;
	}

	private function createQueryProfile( $query, $format, $duration ) {

		$queryProfileAnnotatorFactory = $this->applicationFactory->newQueryProfileAnnotatorFactory();

		$jointProfileAnnotator = $queryProfileAnnotatorFactory->newJointProfileAnnotator(
			$query,
			$format,
			$duration
		);

		$jointProfileAnnotator->addAnnotation();

		$this->parserData->getSemanticData()->addPropertyObjectValue(
			$jointProfileAnnotator->getProperty(),
			$jointProfileAnnotator->getContainer()
		);
	}

}
