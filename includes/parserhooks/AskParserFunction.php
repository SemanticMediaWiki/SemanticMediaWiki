<?php

namespace SMW;

use Parser;
use SMWQueryProcessor;

/**
 * Class that provides the {{#ask}} parser function
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:Ask
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */

/**
 * Class that provides the {{#ask}} parser function
 *
 * @ingroup ParserFunction
 */
class AskParserFunction {

	/** @var IParserData */
	protected $parserData;

	/** @var QueryData */
	protected $queryData;

	/** @var MessageFormatter */
	protected $msgFormatter;

	/** @var boolean */
	protected $showMode = false;

	/**
	 * @since 1.9
	 *
	 * @param IParserData $parserData
	 * @param QueryData $queryData
	 * @param MessageFormatter $msgFormatter
	 */
	public function __construct( IParserData $parserData, QueryData $queryData, MessageFormatter $msgFormatter ) {
		$this->parserData = $parserData;
		$this->queryData = $queryData;
		$this->msgFormatter = $msgFormatter;
	}

	/**
	 * {{#ask}} is disabled (see $smwgQEnabled)
	 *
	 * @since 1.9
	 *
	 * @return string|null
	 */
	protected function disabled() {
		return $this->msgFormatter->addFromKey( 'smw_iq_disabled' )->getHtml();
	}

	/**
	 * After some discussion IQueryProcessor/QueryProcessor is not being
	 * used in 1.9 and instead rely on SMWQueryProcessor
	 *
	 * @todo Static class SMWQueryProcessor, please fixme
	 */
	private function initQueryProcessor( array $rawParams ) {
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
	}

	/**
	 * Enable showMode (normally only invoked by {{#show}})
	 *
	 * @since 1.9
	 *
	 * @return AskParserFunction
	 */
	public function useShowMode() {
		$this->showMode = true;
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

		$this->initQueryProcessor( $rawParams );

		// Add query data from the query
		// Suppose the the query returns with an error, right now we store
		// the query itself even though it returned with unqualified data
		$this->queryData->setQueryId( new HashIdGenerator( $rawParams ) );
		$this->queryData->add( $this->query, $this->params );

		// Store query data to the semantic data instance
		$this->parserData->getData()->addPropertyObjectValue(
			$this->queryData->getProperty(),
			$this->queryData->getContainer()
		);

		// Update ParserOutput
		$this->parserData->updateOutput();

		return $this->result;
	}

	/**
	 * Parser::setFunctionHook {{#ask}} handler method
	 *
	 * @since 1.9
	 *
	 * @param Parser $parser
	 *
	 * @return string
	 */
	public static function render( Parser &$parser ) {
		$ask = new self(
			new ParserData( $parser->getTitle(), $parser->getOutput() ),
			new QueryData( $parser->getTitle() ),
			new MessageFormatter( $parser->getTargetLanguage() )
		);
		return $GLOBALS['smwgQEnabled'] ? $ask->parse( func_get_args() ) : $ask->disabled();
	}
}
