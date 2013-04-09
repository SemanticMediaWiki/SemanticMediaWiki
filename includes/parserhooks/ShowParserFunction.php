<?php

namespace SMW;

use Parser;
use Title;
use ParserOutput;

/**
 * {{#show}} parser function
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
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup ParserHooks
 *
 * @author mwjames
 */

/**
 * {{#show}} parser function
 *
 * @ingroup SMW
 * @ingroup ParserHooks
 */
class ShowParserFunction {

	/**
	 * Local properties
	 */
	protected $parserData;
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
	 * Parse parameters and return results to the ParserOutput object
	 *
	 * @note The show parser function uses the same parser hook logic as
	 * SMW\AskParser therefore {{#show}} returns results from an instantiated
	 * SMW\AskParser object
	 *
	 * @note The SMW\ShowParser constructor is not really needed as it
	 * could be called directly in render() but an instantiated SMW\ShowParser
	 * allows separate unit testing
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param boolean $showMode
	 *
	 * @return string|null
	 */
	public function parse( array $rawParams, $enabled ) {
		$instance = new AskParserFunction( $this->parserData, $this->queryData );
		return $instance->parse( $rawParams, $enabled, true );
	}

	/**
	 * Method for handling the show parser
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
