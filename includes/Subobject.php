<?php

namespace SMW;

use SMWContainerSemanticData;
use SMWDIContainer;
use SMWDIWikiPage;
use SMWDataValue;
use SMWDIProperty;

use Title;

/**
 * Class to interact with a 'subobject'
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
 *
 * @author mwjames
 */

/**
 * Class to interact with a 'subobject'
 *
 * @ingroup SMW
 */
class Subobject {

	/** @var Title */
	 protected $title;

	/** @var string */
	 protected $identifier;

	/** @var SMWContainerSemanticData */
	 protected $semanticData;

	/** @var array */
	protected $errors = array();

	/**
	 * @since 1.9
	 *
	 * @param Title $title
	 */
	public function __construct( Title $title ) {
		$this->title = $title;
	}

	/**
	 * Convenience method for immediate object instantiation that creates a
	 * subobject for a given title and identifier
	 *
	 * @par Example:
	 * @code
	 *  $subobject = Subobject::newFromId( 'Foo', 'Bar' );
	 *  $subobject->addPropertyValue( $dataValue )
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param string|false $identifier
	 *
	 * @return Subobject
	 */
	public static function newFromId( Title $title, $identifier = false ) {
		$instance = new self( $title );
		$instance->setSemanticData( $identifier );
		return $instance;
	}

	/**
	 * Returns the subobject Id
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getId() {
		return $this->identifier;
	}

	/**
	 * Returns an anonymous identifier
	 *
	 * @since 1.9
	 *
	 * @param string
	 *
	 * @return string
	 */
	public function getAnonymousIdentifier( $string ) {
		return '_' . hash( 'md4', $string , false );
	}

	/**
	 * Returns an array of collected errors
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Add errors that appeared during internal processing
	 *
	 * @since 1.9
	 *
	 * @param array $error
	 */
	protected function addError( array $error ) {
		$this->errors = array_merge( $this->errors, $error );
	}

	/**
	 * Initializes the semantic data container for a given identifier
	 * and its invoked subject
	 *
	 * @since 1.9
	 *
	 * @param string $identifier
	 *
	 * @return SMWContainerSemanticData
	 */
	public function setSemanticData( $identifier ) {

		if ( $identifier != '' ) {
			$this->identifier = $identifier;

			$diSubWikiPage = new SMWDIWikiPage(
				$this->title->getDBkey(),
				$this->title->getNamespace(),
				$this->title->getInterwiki(),
				$identifier
			);

			return $this->semanticData = new SMWContainerSemanticData( $diSubWikiPage );
		}

	}

	/**
	 * Returns semantic data container for a subobject
	 *
	 * @since 1.9
	 *
	 * @return SMWContainerSemanticData
	 */
	public function getSemanticData() {
		return $this->semanticData;
	}

	/**
	 * Returns the property data item for the subobject
	 *
	 * @since 1.9
	 *
	 * @return SMWDIProperty
	 */
	public function getProperty() {
		return new SMWDIProperty( SMWDIProperty::TYPE_SUBOBJECT );
	}

	/**
	 * Returns the container data item for the subobject
	 *
	 * @since 1.9
	 *
	 * @return SMWDIContainer
	 */
	public function getContainer() {
		return new SMWDIContainer( $this->semanticData );
	}

	/**
	 * Adds a data value object to the semantic data container
	 *
	 * @par Example:
	 * @code
	 *  $dataValue = DataValueFactory::newPropertyValue( $userProperty, $userValue )
	 *
	 *  Subobject::newFromId( 'Foo', 'Bar' )->addPropertyValue( $dataValue )
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param DataValue $dataValue
	 *
	 * @throws InvalidSemanticDataException
	 */
	public function addPropertyValue( SMWDataValue $dataValue ) {

		if ( !( $this->semanticData instanceof SMWContainerSemanticData ) ) {
			throw new InvalidSemanticDataException( 'The semantic data container is not initialized' );
		}

		wfProfileIn( __METHOD__ );

		if ( $dataValue->getProperty() instanceof SMWDIProperty ) {
			if ( $dataValue->isValid() ) {
				$this->semanticData->addPropertyObjectValue(
					$dataValue->getProperty(),
					$dataValue->getDataItem()
				);
			} else {
				$this->semanticData->addPropertyObjectValue(
					new SMWDIProperty( SMWDIProperty::TYPE_ERROR ),
					$dataValue->getProperty()->getDiWikiPage()
				);
				$this->addError( $dataValue->getErrors() );
			}
		} else {
			$this->addError( $dataValue->getErrors() );
		}

		wfProfileOut( __METHOD__ );
	}
}
