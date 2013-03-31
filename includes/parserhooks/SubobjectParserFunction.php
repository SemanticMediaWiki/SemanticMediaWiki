<?php

namespace SMW;

use Parser;
use MWException;

use SMWParseData;
use SMWDIWikiPage;

/**
 * Class for the #subobject parser functions.
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
 * @author Markus Krötzsch
 */
class SubobjectParserFunction {

	/**
	 * Defines a subobject instance
	 * @var $subobject
	 */
	protected $subobject;

	/**
	 * Constructor which returns an immutable value object
	 *
	 * @since 1.9
	 *
	 * @param SMWDIWikiPage $subject wikipage subject
	 * @param array $parameters array of parameters
	 * @param string $identifier named subobject identifier
	 */
	public function __construct( SMWDIWikiPage $subject, $parameters = '', $identifier = '' ) {
		if ( !is_array( $parameters ) ) {
			throw new MWException( 'Parameters array is not initialized' );
		}

		$this->subobject = new Subobject( $subject );
		$this->add( $parameters, $identifier );
	}

	/**
	 * Returns the subobject instance
	 *
	 * @since 1.9
	 *
	 * @return Subobject
	 */
	public function getSubobject() {
		return $this->subobject;
	}

	/**
	 * Add values to the instance
	 *
	 * @since 1.9
	 *
	 * @param array $parameters array of parameters
	 * @param string $identifier named subobject identifier
	 */
	protected function add( $parameters, $identifier ) {

		// An instance that don't use a named identifier will get an anonymous Id
		if ( $identifier === '' || $identifier === '-' ){
			$identifier = $this->subobject->getAnonymousIdentifier( serialize( $parameters ) );
		}

		// Prepare semantic container
		$this->subobject->setSemanticData( $identifier );

		// Add property / values
		foreach ( $parameters as $property => $values ){
			foreach ( $values as $value ) {
				$this->subobject->addPropertyValue( $property, $value );
			}
		}
	}

	/**
	 * Method for handling the subobject parser function.
	 *
	 * @since 1.9
	 *
	 * @param  Parser $parser
	 */
	public static function render( Parser &$parser ) {

		$params = func_get_args();
		array_shift( $params );

		$mainSemanticData = SMWParseData::getSMWData( $parser );
		$subject = $mainSemanticData->getSubject();
		$name = str_replace( ' ', '_', trim( array_shift( $params ) ) );

		// FIXME Use a class instance here
		$parameters = ParserParameterFormatter::singleton()->getParameters( $params );

		// Create handler instance and encapsulate the subobject instance by
		// returning a value object
		$handler = new self( $subject, $parameters, $name );

		// Store subobject
		SMWParseData::getSMWData( $parser )->addPropertyObjectValue(
			$handler->getSubobject()->getProperty(),
			$handler->getSubobject()->getContainer()
		);

		// Error output

		// FIXME Use a real ErrorReport class where context determines the
		// message language instead of having the object contain a translated
		// error message; An error object should only hold a message key which at
		// the time of the output is translated within context
		return smwfEncodeMessages( $handler->getSubobject()->getErrors() );
	}
}