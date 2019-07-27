<?php

namespace SMW\Parser;

use SMW\SemanticData;
use SMW\DataValueFactory;
use SMWDataItem as DataItem;
use SMW\DIProperty;
use SMW\DIWikiPage;

/**
 * To allow for an in-memory processing of existing SemanticData references during
 * an annotation process, encupsulate the `DataValueFactory` to ensure the
 * relevant instance reference is set and is available while building a
 * `DataValue` object instance.
 *
 * https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3901
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class AnnotationProcessor {

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @var boolean
	 */
	private $canAnnotate = true;

	/**
	 * @since 3.1
	 *
	 * @param SemanticData $semanticData
	 * @param DataValueFactory|null $dataValueFactory
	 */
	public function __construct( SemanticData $semanticData, DataValueFactory $dataValueFactory = null ) {
		$this->semanticData = $semanticData;
		$this->dataValueFactory = $dataValueFactory;

		if ( $this->dataValueFactory === null ) {
			$this->dataValueFactory = DataValueFactory::getInstance();
		}

		$this->dataValueFactory->addCallable( SemanticData::class, [ $this, 'getSemanticData' ] );
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $canAnnotate
	 */
	public function setCanAnnotate( $canAnnotate ) {
		$this->canAnnotate = $canAnnotate;
	}

	/**
	 * @since 3.1
	 *
	 * @return boolean
	 */
	public function canAnnotate() {
		return $this->canAnnotate;
	}

	/**
	 * @since 3.1
	 */
	public function release() {
		$this->dataValueFactory->clearCallable( SemanticData::class );
	}

	/**
	 * @since 3.1
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->semanticData;
	}

	/**
	 * @since 3.1
	 *
	 * @return DataValue
	 */
	public function newDataValueByText( $propertyName, $valueString, $caption = false, DIWikiPage $contextPage = null ) {
		return $this->dataValueFactory->newDataValueByText( $propertyName, $valueString, $caption, $contextPage );
	}

	/**
	 * @since 3.1
	 *
	 * @return DataValue
	 */
	public function newDataValueByItem( DataItem $dataItem, DIProperty $property = null, $caption = false, $contextPage = null ) {
		return $this->dataValueFactory->newDataValueByItem( $dataItem, $property, $caption, $contextPage );
	}

}
