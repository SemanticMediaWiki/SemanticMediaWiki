<?php

namespace SMW;

use Parser;

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
	 * Represents IQueryProcessor
	 */
	protected $queryProcessor;

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
	 * @param IQueryProcessor $queryProcessor
	 * @param QueryData $queryData
	 */
	public function __construct( IParserData $parserData, IQueryProcessor $queryProcessor, QueryData $queryData ) {
		$this->parserData = $parserData;
		$this->queryProcessor = $queryProcessor;
		$this->queryData = $queryData;
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
	public function parse( array $rawParams, $enabled = true ) {
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

		// Process parameters
		$this->queryProcessor->map( $rawParams );

		// Add query data from the query
		$this->queryData->setQueryId( $rawParams );
		$this->queryData->add(
			$this->queryProcessor->getQuery(),
			$this->queryProcessor->getParameters()
		);

		// Store query data to the semantic data instance
		$this->parserData->getData()->addPropertyObjectValue(
			$this->queryData->getProperty(),
			$this->queryData->getContainer()
		);

		// Update ParserOutput
		$this->parserData->updateOutput();

		return $this->queryProcessor->getResult();
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
			new QueryProcessor( SMW_OUTPUT_WIKI, QueryProcessor::INLINE_QUERY, false ),
			new QueryData( $parser->getTitle() )
		);
		return $instance->parse( func_get_args(), $GLOBALS['smwgQEnabled'] );
	}
}
