<?php

namespace SMW;

use SMWContainerSemanticData;
use SMWDIContainer;
use SMWDataValue;

use Title;
use InvalidArgumentException;

/**
 * Provides a subobject
 *
 * @see http://www.semantic-mediawiki.org/wiki/Help:Subobject
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
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
	 * Returns the Title object
	 *
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function getTitle() {
		return $this->title;
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
	 * Returns an generated identifier
	 *
	 * @since 1.9
	 *
	 * @param IdGenerator $id
	 *
	 * @return string
	 */
	public function generateId( IdGenerator $id ) {
		return $id->generateId();
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
	 * @throws InvalidArgumentException
	 */
	public function setSemanticData( $identifier ) {

		if ( $identifier === '' ) {
			throw new InvalidArgumentException( 'The identifier is empty' );
		}

		$this->identifier = $identifier;

		$subWikiPage = new DIWikiPage(
			$this->title->getDBkey(),
			$this->title->getNamespace(),
			$this->title->getInterwiki(),
			$identifier
		);

		$this->semanticData = new SMWContainerSemanticData( $subWikiPage );

	}

	/**
	 * Returns semantic data container for a subobject
	 *
	 * @since 1.9
	 *
	 * @return SMWContainerSemanticData
	 */
	public function getSemanticData() {

		if ( !( $this->semanticData instanceof SMWContainerSemanticData ) ) {
			throw new InvalidSemanticDataException( 'The semantic data container is not initialized' );
		}

		return $this->semanticData;
	}

	/**
	 * Returns the property data item for the subobject
	 *
	 * @since 1.9
	 *
	 * @return DIProperty
	 */
	public function getProperty() {
		return new DIProperty( DIProperty::TYPE_SUBOBJECT );
	}

	/**
	 * Returns the container data item for the subobject
	 *
	 * @since 1.9
	 *
	 * @return SMWDIContainer
	 */
	public function getContainer() {
		return new SMWDIContainer( $this->getSemanticData() );
	}

	/**
	 * Adds a data value object to the semantic data container
	 *
	 * @par Example:
	 * @code
	 *  $dataValue = DataValueFactory::getInstance()->newPropertyValue( $userProperty, $userValue )
	 *
	 *  $subobject = new Subobject( 'Foo' );
	 *  $subobject->setSemanticData( 'Bar' );
	 *  $subobject->addDataValue( $dataValue )
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param DataValue $dataValue
	 *
	 * @throws InvalidSemanticDataException
	 */
	public function addDataValue( SMWDataValue $dataValue ) {

		if ( !( $this->semanticData instanceof SMWContainerSemanticData ) ) {
			throw new InvalidSemanticDataException( 'The semantic data container is not initialized' );
		}

		$this->semanticData->addDataValue( $dataValue );
		$this->addError( $this->semanticData->getErrors() );
	}

}
