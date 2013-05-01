<?php

namespace SMW;

use Parser;
use SMWQueryProcessor;

/**
 * Class that provides the {{#ask}} parser function
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
 * @see http://www.semantic-mediawiki.org/wiki/Help:Ask
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup ParserFunction
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */

/**
 * Class that provides the {{#ask}} parser function
 *
 * @ingroup SMW
 * @ingroup ParserFunction
 */
class AskParserFunction {

	/**
	 * Represents IParserData object
	 * @var QueryData
	 */
	protected $parserData;

	/**
	 * Represents QueryData object
	 * @var IParserData
	 */
	protected $queryData;

	/**
	 * SMWQueryProcessor showMode indicator
	 * @var boolean
	 */
	protected $showMode = false;

	/**
	 * @since 1.9
	 *
	 * @param IParserData $parserData
	 * @param QueryData $queryData
	 */
	public function __construct( IParserData $parserData, QueryData $queryData ) {
		$this->parserData = $parserData;
		$this->queryData = $queryData;
	}

	/**
	 * After some discussion IQueryProcessor/QueryProcessor is not being
	 * used in 1.9 and instead rely on SMWQueryProcessor
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
	 * Returns a message about inline queries being disabled
	 *
	 * @see $smwgQEnabled
	 *
	 * FIXME Replace with IMessageFormatter -> ErrorMessageFormatter class
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function disabled() {
		return smwfEncodeMessages( array( wfMessage( 'smw_iq_disabled' )->inContentLanguage()->text() ) );
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
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 *
	 * @return string|null
	 */
	public function parse( array $rawParams ) {
		global $smwgIQRunningNumber;

		// Counter for what? Where and for what is it used?
		$smwgIQRunningNumber++;

		// Remove parser object from parameters array
		if( isset( $rawParams[0] ) && $rawParams[0] instanceof Parser ) {
			array_shift( $rawParams );
		}

		$this->initQueryProcessor( $rawParams );

		// Add query data from the query
		// Suppose the the query returns with an error, right now we store
		// the query itself even though it returned with unqualified data
		$this->queryData->setQueryId( $rawParams );
		$this->queryData->add(
			$this->query,
			$this->params
		);

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
			new QueryData( $parser->getTitle() )
		);
		return $GLOBALS['smwgQEnabled'] ? $ask->parse( func_get_args() ) : $ask->disabled();
	}
}
