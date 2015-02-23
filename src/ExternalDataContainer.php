<?php

namespace SMW;

use SMWDataValue as DataValue;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ExternalDataContainer {

	/**
	 * @var string|null
	 */
	private $dataSource = null;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @var string|null
	 */
	private $dataGroup = null;

	/**
	 * @var Subobject|null
	 */
	private $subobject = null;

	/**
	 * @since 2.2
	 *
	 * @param string $dataSource
	 */
	public function __construct( $dataSource ) {
		$this->dataSource = $dataSource;
		$this->semanticData = new SemanticData( new DIWikiPage( 'DataContainer', NS_SPECIAL ) );
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	/**
	 * @since 2.2
	 *
	 * @return DIWikiPage
	 */
	public function getSubject() {
		return $this->semanticData->getSubject();
	}

	/**
	 * @since 2.2
	 *
	 * @param string $dataGroup
	 */
	public function setDataGroup( $dataGroup ) {
		$this->dataGroup = $dataGroup;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $dataIdentifier
	 * @throws RuntimeException
	 */
	public function setEmptyContainerForDataIdentifier( $dataIdentifier ) {

		if ( $this->dataGroup === '' || $dataIdentifier === '' ) {
			throw new RuntimeException( "Missing a group or storage identifier" );
		}

		$subject = $this->semanticData->getSubject();
		$this->subobject = new Subobject( $subject->getTitle() );
		$this->subobject->setEmptyContainerForId( '_' . $this->dataSource . '.' . $this->dataGroup . '.' . $dataIdentifier );

		$this->addFixedDataToContainer( 'Has external data source', $this->dataSource );
		$this->addFixedDataToContainer( 'Has external data group', $this->dataGroup );
		$this->addFixedDataToContainer( 'Has external data id', $dataIdentifier );
	}

	/**
	 * @since 2.2
	 *
	 * @param DataValue $dataValue
	 */
	public function addDataValueToContainer( DataValue $dataValue ) {

		if ( $this->subobject === null ) {
			throw new RuntimeException( "Missing a storage" );
		}

		$this->subobject->addDataValue( $dataValue );
	}

	/**
	 * @since 2.2
	 */
	public function copyContainerToSemanticData() {
		$this->semanticData->addSubobject( $this->subobject );
		$this->subobject = null;
	}

	/**
	 * @since 2.2
	 *
	 * @return SemanticData
	 */
	public function getSemanticData() {
		return $this->semanticData;
	}

	private function addFixedDataToContainer( $property, $value ) {

		$dataValue = $this->dataValueFactory->newPropertyValue(
				$property,
				$value,
				false,
				$this->getSubject()
			);

		$this->addDataValueToContainer( $dataValue );
	}

}
