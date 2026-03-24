<?php

namespace SMW\Deserializers;

use Deserializers\Deserializer;
use OutOfBoundsException;
use RuntimeException;
use SMW\DataItems\Container;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\DataModel\SemanticData;
use SMW\DataTypeRegistry;
use SMW\DataValues\ErrorValue;

/**
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class SemanticDataDeserializer implements Deserializer {

	private array $dataItemTypeIdCache = [];

	/**
	 * @see Deserializers::deserialize
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 * @throws OutOfBoundsException
	 * @throws RuntimeException
	 */
	public function deserialize( $data ): ?SemanticData {
		$semanticData = null;

		if ( isset( $data['version'] ) && $data['version'] !== 0.1 && $data['version'] !== 2 ) {
			throw new OutOfBoundsException( 'Serializer/Unserializer version does not match, please update your data' );
		}

		if ( isset( $data['subject'] ) ) {
			$semanticData = new SemanticData( WikiPage::doUnserialize( $data['subject'] ) );
		}

		if ( !$semanticData instanceof SemanticData ) {
			throw new RuntimeException( 'SemanticData could not be created probably due to a missing subject' );
		}

		$this->doDeserialize( $data, $semanticData );

		return $semanticData;
	}

	/**
	 * @return null
	 */
	private function doDeserialize( array $data, &$semanticData ): void {
		$property = null;

		if ( !isset( $data['data'] ) ) {
			return;
		}

		foreach ( $data['data'] as $values ) {

			if ( is_array( $values ) ) {

				foreach ( $values as $key => $value ) {

					/**
					 * @var Property $property
					 */
					if ( $key === 'property' ) {
						$property = Property::doUnserialize( $value );
					}

					/**
					 * @var DataItem
					 */
					if ( $key === 'dataitem' ) {
						foreach ( $value as $val ) {
							$this->doDeserializeDataItem( $property, $data, $val, $semanticData );
						}
					}
				}
			}
		}
	}

	/**
	 * @return DataItem
	 */
	private function doDeserializeDataItem( ?Property $property, array $data, $value, $semanticData ): void {
		$dataItem = null;

		if ( !is_array( $value ) ) {
			return;
		}

		$type = $this->getDataItemId( $property );

		// Verify that the current property type definition and the type of the
		// property during serialization do match, throw an error value to avoid any
		// exception during unserialization caused by the DataItem object due to a
		// mismatch of type definitions

		if ( $type === $value['type'] ) {
			$dataItem = DataItem::newFromSerialization( $value['type'], $value['item'] );
		} else {
			$dataItem = $property->getDiWikiPage();
			$property = new Property( Property::TYPE_ERROR );

			$semanticData->addError( [
				new ErrorValue( $type, 'type mismatch', $property->getLabel() )
			] );

		}

		// Check whether the current dataItem has a subobject reference
		if ( $dataItem->getDIType() === DataItem::TYPE_WIKIPAGE && $dataItem->getSubobjectName() !== '' ) {

			$dataItem = $this->doDeserializeSubSemanticData(
				$data,
				$value['item'],
				new ContainerSemanticData( $dataItem )
			);

		}

		// Ensure that errors are collected from a subobject level as well and
		// made available at the top
		if ( $dataItem instanceof Container ) {
			$semanticData->addError( $dataItem->getSemanticData()->getErrors() );
		}

		if ( $property !== null && $dataItem !== null ) {
			$semanticData->addPropertyObjectValue( $property, $dataItem );
		}
	}

	/**
	 * Resolves properties and dataitems assigned to a subobject recursively
	 *
	 * @note The serializer has to make sure to provide a complete data set
	 * otherwise the subobject is neglected (of course one could set an error
	 * value to the DIContainer but as of now that seems unnecessary)
	 *
	 * @return Container|null
	 */
	private function doDeserializeSubSemanticData( array $data, $id, ContainerSemanticData $semanticData ): Container {
		if ( !isset( $data['sobj'] ) ) {
			return new Container( $semanticData );
		}

		foreach ( $data['sobj'] as $subobject ) {
			if ( isset( $subobject['subject'] ) && $subobject['subject'] === $id && isset( $subobject['data'] ) ) {
				$this->doDeserialize( $subobject, $semanticData );
			}
		}

		return new Container( $semanticData );
	}

	/**
	 * Returns DataItemId for a property
	 *
	 * @note findPropertyTypeID is calling the Store to find the
	 * typeId reference this is costly but at the moment there is no other
	 * way to determine the typeId
	 *
	 * This check is to ensure that during unserialization the correct item
	 * in terms of its definition is being sought otherwise inconsistencies
	 * can occur due to type changes of a property between the time of
	 * the serialization and the deserialization (e.g for when the
	 * serialization object is stored in cache, DB etc.)
	 *
	 * @return int
	 */
	private function getDataItemId( Property $property ) {
		if ( !isset( $this->dataItemTypeIdCache[$property->getKey()] ) ) {
			$this->dataItemTypeIdCache[$property->getKey()] = DataTypeRegistry::getInstance()->getDataItemId( $property->findPropertyTypeID() );
		}

		return $this->dataItemTypeIdCache[$property->getKey()];
	}

}
