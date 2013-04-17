<?php

namespace SMW;

use Parser;

/**
 * {{#subobject}} parser function
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
 * @see http://www.semantic-mediawiki.org/wiki/Help:Subobject
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
 * Class that provides the {{#subobject}} parser hook function
 *
 * @ingroup SMW
 * @ingroup ParserHooks
 */
class SubobjectParserFunction {

	/**
	 * Represents IParserData
	 */
	protected $parserData;

	/**
	 * Represents Subobject
	 */
	protected $subobject;

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param IParserData $parserData
	 * @param Subobject $subobject
	 */
	public function __construct( IParserData $parserData, Subobject $subobject ) {
		$this->parserData = $parserData;
		$this->subobject = $subobject;
	}

	/**
	 * Returns subobject
	 *
	 * @since 1.9
	 *
	 * @return Subobject
	 */
	public function getSubobject() {
		return $this->subobject;
	}

	/**
	 * Add values to the subobject instance
	 *
	 * @since 1.9
	 *
	 * @param array $parameters array of parameters
	 * @param string $identifier named subobject identifier
	 */
	protected function addSubobjectValues( $parameters, $identifier = '' ) {

		// An instance that don't use a named identifier will get an anonymous Id
		if ( $identifier === '' || $identifier === '-' ){
			$identifier = $this->subobject->getAnonymousIdentifier( serialize( $parameters ) );
		}

		// Prepare and set semantic container for the given identifier
		$this->subobject->setSemanticData( $identifier );

		// Add property / values to the subobject instance
		foreach ( $parameters as $property => $values ){
			foreach ( $values as $value ) {
				$this->subobject->addPropertyValue( $property, $value );
			}
		}
	}

	/**
	 * Parse parameters and return results to the ParserOutput object
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 *
	 * @return string|null
	 */
	public function parse( IParameterFormatter $parameters ) {

		// Add values to the instantiated subobject
		// getFirst() will indicate if a subobject becomes a named or
		// anonymous subobject
		$this->addSubobjectValues(
			$parameters->toArray(),
			$parameters->getFirst()
		);

		// Store subobject to the semantic data instance
		$this->parserData->getData()->addPropertyObjectValue(
			$this->subobject->getProperty(),
			$this->subobject->getContainer()
		);

		$this->parserData->addError( $this->subobject->getErrors() );

		// Update ParserOutput
		$this->parserData->updateOutput();

		return $this->parserData->getReport();
	}

	/**
	 * Method for handling the subobject parser function
	 *
	 * @param Parser $parser
	 *
	 * @return string|null
	 */
	public static function render( Parser &$parser ) {
		$instance = new self(
			new ParserData( $parser->getTitle(), $parser->getOutput() ),
			new Subobject( $parser->getTitle() )
		);
		return $instance->parse( new ParserParameterFormatter( func_get_args() ) );
	}
}