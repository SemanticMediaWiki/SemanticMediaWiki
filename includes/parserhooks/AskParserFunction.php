<?php

namespace SMW;

use Parser;
use SMWQueryProcessor;

/**
 * {{#ask}} parser function
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
 * @ingroup ParserHooks
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */

/**
 * Class that provides the {{#ask}} parser hook function
 *
 * @ingroup SMW
 * @ingroup ParserHooks
 */
class AskParserFunction {

	/**
	 * Represents IParserData
	 */
	protected $parserData;

	/**
	 * Represents QueryData
	 */
	protected $queryData;

	/**
	 * Constructor
	 *
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
	private function initQueryProcessor( array $rawParams, $showMode = false ) {
		list( $this->query, $this->params ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY,
			$showMode
		);

		$this->result = SMWQueryProcessor::getResultFromQuery(
			$this->query,
			$this->params,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY
		);
	}

	/**
	 * Parse parameters and return results to the ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param boolean $enabled
	 *
	 * @return string|null
	 */
	public function parse( array $rawParams, $enabled = true, $showMode = false ) {
		global $smwgIQRunningNumber;

		// FIXME $rawParams will be of IParameterFormatter -> QueryParameterFormatter class

		if ( !$enabled ) {
			// FIXME Replace with IMessageFormatter -> ErrorMessageFormatter class
			return smwfEncodeMessages( array( wfMessage( 'smw_iq_disabled' )->inContentLanguage()->text() ) );
		}

		// Counter for what? Where and for what is it used?
		$smwgIQRunningNumber++;

		// Remove parser object from parameters array
		array_shift( $rawParams );

		$this->initQueryProcessor( $rawParams, $showMode );

		// Add query data from the query
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
	 * Method for handling the {{#ask}} parser function
	 *
	 * @since 1.9
	 *
	 * @param Parser $parser
	 *
	 * @return string
	 */
	public static function render( Parser &$parser ) {
		$instance = new self(
			new ParserData( $parser->getTitle(), $parser->getOutput() ),
			new QueryData( $parser->getTitle() )
		);
		return $instance->parse( func_get_args(), $GLOBALS['smwgQEnabled'] );
	}
}
