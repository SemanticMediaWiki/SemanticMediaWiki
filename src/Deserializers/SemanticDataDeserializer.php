<?php

namespace SMW\Deserializers;

use Deserializers\Deserializer;
use OutOfBoundsException;
use SMW\DataTypeRegistry;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMWContainerSemanticData;
use SMWDataItem as DataItem;
use SMWDIContainer as DIContainer;
use SMWErrorValue as ErrorValue;

/**
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SemanticDataDeserializer implements Deserializer {

	/**
	 * @var array
	 */
	private $dataItemTypeIdCache = array();

	/**
	 * @see Deserializers::deserialize
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 * @throws OutOfBoundsException
	 */
	public function deserialize( $data ) {

		$semanticData = null;

		if ( isset( $data['version'] ) && $data['version'] !== 0.1 ) {
			throw new OutOfBoundsException( 'Serializer/Unserializer version does not match, please update your data' );
		}

		if ( isset( $data['subject'] ) ) {
			$semanticData = new SemanticData( DIWikiPage::doUnserialize( $data['subject'] ) );
		}

		if ( !$semanticData instanceof SemanticData ) {
			throw new OutOfBoundsException( 'SemanticData could not be created probably due to a missing subject' );
		}

		$this->doDeserialize( $data, $semanticData );

		return $semanticData;
	}

	/**
	 * @return null
	 */
	private function doDeserialize( $data, &$semanticData ) {

		$property = null;

		if ( !isset( $data['data'] ) ) {
			return;
		}

		foreach ( $data['data'] as $values ) {

			if ( is_array( $values ) ) {

				foreach ( $values as $key => $value ) {

					/**
					 * @var DIProperty $property
					 */
					if ( $key === 'property' ) {
						$property = DIProperty::doUnserialize( $value );
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
	private function doDeserializeDataItem( $property, $data, $value, $semanticData ) {

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
			$property = new DIProperty( DIProperty::TYPE_ERROR );

			$semanticData->addError( array(
				new ErrorValue( $type, 'type mismatch', $property->getLabel() )
			) );

		}

		// Check whether the current dataItem has a subobject reference
		if ( $dataItem->getDIType() === DataItem::TYPE_WIKIPAGE && $dataItem->getSubobjectName() !== '' ) {

			$dataItem = $this->unserializeSubobject(
				$data,
				$value['item'],
				new SMWContainerSemanticData( $dataItem )
			);

		}

		// Ensure that errors are collected from a subobject level as well and
		// made available at the top
		if ( $dataItem instanceof DIContainer ) {
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
	 * @return DIContainer|null
	 */
	private function unserializeSubobject( $data, $id, $semanticData ) {

		if ( !isset( $data['sobj'] ) ) {
			return null;
		}

		foreach ( $data['sobj'] as $subobject ) {

			if ( isset( $subobject['subject'] ) && $subobject['subject'] === $id ) {
				$this->doDeserialize( $subobject, $semanticData );
			}

		}

		return new DIContainer( $semanticData );
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
	 * @return integer
	 */
	private function getDataItemId( DIProperty $property ) {

		if ( !isset( $this->dataItemTypeIdCache[$property->getKey()] ) ) {
			$this->dataItemTypeIdCache[$property->getKey()] = DataTypeRegistry::getInstance()->getDataItemId( $property->findPropertyTypeID() );
		}

		return $this->dataItemTypeIdCache[$property->getKey()];
	}

}
