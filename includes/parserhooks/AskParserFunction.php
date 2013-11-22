<?php

namespace SMW;

use Parser;
use SMWQueryProcessor;

/**
 * Provides the {{#ask}} parser function
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:Ask
 *
 * @ingroup ParserFunction
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class AskParserFunction {

	/** @var ParserData */
	protected $parserData;

	/** @var ContextResource */
	protected $context;

	/** @var boolean */
	protected $showMode = false;

	/**
	 * @since 1.9
	 *
	 * @param ParserData $parserData
	 * @param ContextResource $context
	 */
	public function __construct( ParserData $parserData, ContextResource $context ) {
		$this->parserData = $parserData;
		$this->context = $context;
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

		$this->runQueryProcessor( $rawParams );
		$this->runQueryProfiler( $rawParams );

		$this->parserData->updateOutput();

		return $this->result;
	}

	/**
	 * {{#ask}} is disabled (see $smwgQEnabled)
	 *
	 * @since 1.9
	 *
	 * @return string|null
	 */
	public function isQueryDisabled() {
		return $this->context->getDependencyBuilder()
			->newObject( 'MessageFormatter' )
			->addFromKey( 'smw_iq_disabled' )
			->getHtml();
	}

	/**
	 * @since  1.9
	 */
	private function runQueryProcessor( array $rawParams ) {

		// FIXME QueryDuration should be a property of the QueryProcessor or
		// QueryEngine but since we don't want to open the pandora's box and
		// increase issues within the current QueryProcessor implementation
		// we will track the time outside of the actual execution framework
		$this->queryDuration = 0;
		$start = microtime( true );

		list( $this->query, $this->params ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY,
			$this->showMode
		);

		$this->result = SMWQueryProcessor::getResultFromQuery(
			$this->query,
			$this->params,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY
		);

		if ( $this->context->getSettings()->get( 'smwgQueryDurationEnabled' ) ) {
			$this->queryDuration = microtime( true ) - $start;
		}

	}

	/**
	 * @since  1.9
	 */
	private function runQueryProfiler( array $rawParams ) {

		$profiler = $this->context->getDependencyBuilder()->newObject( 'QueryProfiler', array(
			'QueryDescription' => $this->query->getDescription(),
			'QueryParameters'  => $rawParams,
			'QueryFormat'      => $this->params['format']->getValue(),
			'QueryDuration'    => $this->queryDuration,
			'Title'            => $this->parserData->getTitle(),
		) );

		$profiler->addAnnotation();

		$this->parserData->getData()->addPropertyObjectValue(
			$profiler->getProperty(),
			$profiler->getContainer()
		);

	}

}
