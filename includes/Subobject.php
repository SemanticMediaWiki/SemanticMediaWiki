<?php

namespace SMW;

use InvalidArgumentException;
use SMWContainerSemanticData;
use SMWDataValue;
use SMWDIContainer;
use Title;
use SMW\Exception\SubSemanticDataException;

/**
 * @see http://www.semantic-mediawiki.org/wiki/Help:Subobject
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class Subobject {

	/**
	 * @var Title
	 */
	 protected $title;

	/**
	 * @var SMWContainerSemanticData
	 */
	 protected $semanticData;

	/**
	 * @var array
	 */
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
	 * @since 2.1
	 *
	 * @return DIWikiPage
	 */
	public function getSubject() {
		return $this->getSemanticData()->getSubject();
	}

	/**
	 * @since 2.1
	 *
	 * @return string
	 */
	public function getSubobjectId() {
		return $this->getSemanticData()->getSubject()->getSubobjectName();
	}

	/**
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 1.9
	 *
	 * @param array|string $error
	 */
	public function addError( $error ) {

		if ( is_string( $error ) ) {
			$error = array( md5( $error ) => $error );
		}

		// Preserve the keys, avoid using array_merge to avert a possible
		// Fatal error: Allowed memory size of ... bytes exhausted ... Subobject.php on line 89
		$this->errors += $error;
	}

	/**
	 * @since 2.0
	 *
	 * @param string $identifier
	 *
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public function setEmptyContainerForId( $identifier ) {

		if ( $identifier === '' ) {
			throw new InvalidArgumentException( 'Expected a valid (non-empty) indentifier' );
		}

		$subWikiPage = new DIWikiPage(
			$this->title->getDBkey(),
			$this->title->getNamespace(),
			$this->title->getInterwiki(),
			$identifier
		);

		$this->semanticData = new SMWContainerSemanticData( $subWikiPage );

		return $this;
	}

	/**
	 * @deprecated since 2.0
	 */
	public function setSemanticData( $identifier ) {
		$this->setEmptyContainerForId( $identifier );
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
			throw new SubSemanticDataException( 'The semantic data container is not initialized' );
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
	 * @since 1.9
	 *
	 * @param DataValue $dataValue
	 *
	 * @throws SubSemanticDataException
	 */
	public function addDataValue( SMWDataValue $dataValue ) {

		if ( !( $this->semanticData instanceof SMWContainerSemanticData ) ) {
			throw new SubSemanticDataException( 'The semantic data container is not initialized' );
		}

		$this->semanticData->addDataValue( $dataValue );
		$this->addError( $this->semanticData->getErrors() );
	}

}
