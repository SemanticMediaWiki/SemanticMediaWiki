<?php

namespace SMW;

use Parser;

/**
 * Class that provides the {{#set}} parser function
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
 * @see http://semantic-mediawiki.org/wiki/Help:Properties_and_types#Silent_annotations_using_.23set
 * @see http://www.semantic-mediawiki.org/wiki/Help:Setting_values
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup ParserFunction
 *
 * @licence GNU GPL v2+
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */

/**
 * Class that provides the {{#set}} parser function
 *
 * @ingroup SMW
 * @ingroup ParserFunction
 */
class SetParserFunction {

	/**
	 * Represents IParserData object
	 * @var IParserData
	 */
	protected $parserData;

	/**
	 * @since 1.9
	 *
	 * @param IParserData $parserData
	 */
	public function __construct( IParserData $parserData ) {
		$this->parserData = $parserData;
	}

	/**
	 * Parse parameters and store its results to the ParserOutput object
	 *
	 * @since  1.9
	 *
	 * @param IParameterFormatter $parameters
	 *
	 * @return string|null
	 */
	public function parse( IParameterFormatter $parameters ) {

		// Add dataValues
		foreach ( $parameters->toArray() as $property => $values ){
			foreach ( $values as $value ) {
				$this->parserData->addPropertyValue(
					DataValueFactory::newPropertyValue(
						$property,
						$value
					)
				);
			}
		}

		// Update ParserOutput
		$this->parserData->updateOutput();

		return $this->parserData->getReport();
	}

	/**
	 * Parser::setFunctionHook {{#set}} handler method
	 *
	 * @param Parser $parser
	 *
	 * @return string|null
	 */
	public static function render( Parser &$parser ) {
		$set = new self( new ParserData( $parser->getTitle(), $parser->getOutput() ) );
		return $set->parse( new ParserParameterFormatter( func_get_args() ) );
	}
}
