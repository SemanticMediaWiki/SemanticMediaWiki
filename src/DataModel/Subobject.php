<?php

namespace SMW\DataModel;

use InvalidArgumentException;
use MediaWiki\Title\Title;
use SMW\DataItems\Container;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValues\DataValue;
use SMW\Exception\SubSemanticDataException;

/**
 * @see http://www.semantic-mediawiki.org/wiki/Help:Subobject
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class Subobject {

	protected Title $title;

	/**
	 * @var ContainerSemanticData
	 */
	 protected $semanticData;

	/**
	 * @var array
	 */
	protected $errors = [];

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
	public function getTitle(): Title {
		return $this->title;
	}

	/**
	 * @since 2.1
	 *
	 * @return WikiPage
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
	 *
	 * @return void
	 */
	public function addError( $error ): void {
		if ( is_string( $error ) ) {
			$error = [ md5( $error ) => $error ];
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

		$subWikiPage = new WikiPage(
			$this->title->getDBkey(),
			$this->title->getNamespace(),
			$this->title->getInterwiki(),
			$identifier
		);

		$this->semanticData = new ContainerSemanticData( $subWikiPage );

		return $this;
	}

	/**
	 * @deprecated since 2.0
	 */
	public function setSemanticData( $identifier ): void {
		$this->setEmptyContainerForId( $identifier );
	}

	/**
	 * Returns semantic data container for a subobject
	 *
	 * @since 1.9
	 *
	 * @return ContainerSemanticData
	 * @throws SubSemanticDataException
	 */
	public function getSemanticData() {
		if ( !( $this->semanticData instanceof ContainerSemanticData ) ) {
			throw new SubSemanticDataException( 'The semantic data container is not initialized' );
		}

		return $this->semanticData;
	}

	/**
	 * Returns the property data item for the subobject
	 *
	 * @since 1.9
	 *
	 * @return Property
	 */
	public function getProperty(): Property {
		return new Property( Property::TYPE_SUBOBJECT );
	}

	/**
	 * Returns the container data item for the subobject
	 *
	 * @since 1.9
	 *
	 * @return Container
	 */
	public function getContainer(): Container {
		return new Container( $this->getSemanticData() );
	}

	/**
	 * @since 1.9
	 *
	 * @param DataValue $dataValue
	 *
	 * @return void
	 * @throws SubSemanticDataException
	 */
	public function addDataValue( DataValue $dataValue ): void {
		if ( !( $this->semanticData instanceof ContainerSemanticData ) ) {
			throw new SubSemanticDataException( 'The semantic data container is not initialized' );
		}

		$this->semanticData->addDataValue( $dataValue );
		$this->addError( $this->semanticData->getErrors() );
	}

}

/**
 * @deprecated since 7.0.0
 */
class_alias( Subobject::class, 'SMW\Subobject' );
