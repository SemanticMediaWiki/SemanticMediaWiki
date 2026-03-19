<?php

namespace SMW\Parser;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMWDataItem as DataItem;

/**
 * To allow for an in-memory processing of existing SemanticData references during
 * an annotation process, encupsulate the `DataValueFactory` to ensure the
 * relevant instance reference is set and is available while building a
 * `DataValue` object instance.
 *
 * https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/3901
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class AnnotationProcessor {

	/**
	 * @var bool
	 */
	private $canAnnotate = true;

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly SemanticData $semanticData,
		private ?DataValueFactory $dataValueFactory = null,
	) {
		if ( $this->dataValueFactory === null ) {
			$this->dataValueFactory = DataValueFactory::getInstance();
		}

		$this->dataValueFactory->addCallable( SemanticData::class, [ $this, 'getSemanticData' ] );
	}

	/**
	 * @since 3.1
	 *
	 * @param bool $canAnnotate
	 */
	public function setCanAnnotate( $canAnnotate ): void {
		$this->canAnnotate = $canAnnotate;
	}

	/**
	 * @since 3.1
	 *
	 * @return bool
	 */
	public function canAnnotate() {
		return $this->canAnnotate;
	}

	/**
	 * @since 3.1
	 */
	public function release(): void {
		$this->dataValueFactory->clearCallable( SemanticData::class );
	}

	/**
	 * @since 3.1
	 *
	 * @return SemanticData
	 */
	public function getSemanticData(): SemanticData {
		return $this->semanticData;
	}

	/**
	 * @since 3.1
	 *
	 * @return DataValue
	 */
	public function newDataValueByText( $propertyName, $valueString, $caption = false, ?DIWikiPage $contextPage = null ) {
		return $this->dataValueFactory->newDataValueByText( $propertyName, $valueString, $caption, $contextPage );
	}

	/**
	 * @since 3.1
	 *
	 * @return DataValue
	 */
	public function newDataValueByItem( DataItem $dataItem, ?DIProperty $property = null, $caption = false, $contextPage = null ) {
		return $this->dataValueFactory->newDataValueByItem( $dataItem, $property, $caption, $contextPage );
	}

}
