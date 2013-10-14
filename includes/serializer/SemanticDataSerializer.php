<?php

namespace SMW;

use SMWContainerSemanticData;
use SMWDIContainer as DIContainer;
use SMWDataItem as DataItem;
use SMWErrorValue as ErrorValue;

use OutOfBoundsException;

/**
 * SemanticData serializer / unserializer
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * SemanticData serializer / unserializer
 *
 * @ingroup SMW
 */
class SemanticDataSerializer implements SerializerInterface {

	/**
	 * The version number is an indicator among serialized data to track structural
	 * integrity which means that any change in its output format is to be accompanied
	 * by a version number change
	 *
	 * The unserializer will raise an exception if the version of the incoming
	 * data does not match with the version number found in this class.
	 */
	const VERSION = 0.1;

	/**
	 * Cache for already processed Id's which is to minimize lock-up performance
	 * during unserialization
	 *
	 * @var array
	 */
	protected $dataItemTypeIdCache = array();

	/**
	 * Initiates serialization of a SemanticData object
	 *
	 * @since 1.9
	 *
	 * @return array
	 * @throws OutOfBoundsException
	 */
	public function serialize( $semanticData ) {

		if ( !( $semanticData instanceOf SemanticData ) ) {
			throw new OutOfBoundsException( 'Object was not identified as a SemanticData instance' );
		}

		return $this->serializeSemanticData( $semanticData ) + array( 'serializer' => __CLASS__, 'version' => self::VERSION );
	}

	/**
	 * Initiates unserialization of an object
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 * @throws OutOfBoundsException
	 */
	public function unserialize( array $data ) {

		$semanticData = null;

		if ( isset( $data['version'] ) && $data['version'] !== self::VERSION ) {
			throw new OutOfBoundsException( 'Serializer/Unserializer version do not match, please update your data' );
		}

		if ( isset( $data['subject'] ) ) {
			$semanticData = new SemanticData( DIWikiPage::doUnserialize( $data['subject'] ) );
		}

		if ( !( $semanticData instanceOf SemanticData ) ) {
			throw new OutOfBoundsException( 'SemanticData could not be created probably due to a missing subject' );
		}

		$this->unserializeSemanticData( $data, $semanticData );

		return $semanticData;
	}

	/**
	 * Returns serialized SemanticData
	 *
	 * @return array
	 */
	protected function serializeSemanticData( SemanticData $semanticData ) {

		$output = array();

		$output['subject'] = $semanticData->getSubject()->getSerialization();

		/**
		 * Build property and dataItem serialization record
		 */
		foreach ( $semanticData->getProperties() as $key => $property ) {

			$prop = array();

			$prop['property'] = $property->getSerialization();

			foreach ( $semanticData->getPropertyValues( $property ) as $dataItem ) {
				$prop['dataitem'][] = $this->serializeDataItem( $dataItem );
			}

			$output['data'][] = $prop;

		}

		$this->serializeSubobject( $semanticData->getSubSemanticData(), $output );

		return $output;
	}

	/**
	 * Returns DataItem serialization
	 *
	 * @note 'type' is added to ensure that during unserialization the type
	 * definition of the requested data is in alignment with the definition found
	 * in the system (type changes that can occur during the time between
	 * serialization and unserialization)
	 *
	 * @note 'sobj' is only added for when a subobject is present
	 *
	 * @return array
	 */
	protected function serializeDataItem( DataItem $dataItem ) {

		$di = array(
			'type' => $dataItem->getDIType(),
			'item' => $dataItem->getSerialization()
		);

		if ( $dataItem->getDIType() === DataItem::TYPE_WIKIPAGE && $dataItem->getSubobjectName() ) {
			$di += array( 'sobj' => $dataItem->getSubobjectName() );
		}

		return $di;
	}

	/**
	 * Returns all subobjects of a SemanticData instance
	 *
	 * @note The subobject name is used as reference key as it is the only
	 * reliable unique key to allow a performable lookup during unserialization
	 *
	 * @return array
	 */
	protected function serializeSubobject( $subSemanticData, &$output ) {

		foreach ( $subSemanticData as $semanticData ) {
			$output['sobj'][] = $this->serializeSemanticData( $semanticData );
		}

	}

	/**
	 * @return null
	 */
	protected function unserializeSemanticData( $data, &$semanticData ) {

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
							$this->unserializeDataItem( $property, $data, $val, $semanticData );
						}
					}
				}
			}
		}
	}

	/**
	 * @return DataItem
	 */
	protected function unserializeDataItem( $property, $data, $value, $semanticData ) {

		$dataItem = null;

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
		if ( isset( $value['sobj'] ) && $value['sobj'] !== null ) {

			$dataItem = $this->unserializeSubobject(
				$data,
				$value['item'],
				new SMWContainerSemanticData( $dataItem )
			);

		}

		// Ensure that errors are collected from a subobject level as well and
		// made available at the top
		if ( $dataItem instanceOf DIContainer ) {
			$semanticData->addError( $dataItem->getSemanticData()->getErrors() );
		}

		if ( $property !== null ) {
			$semanticData->addPropertyObjectValue( $property, $dataItem );
		}

	}

	/**
	 * Resolves properties and dataitems assigned to a subobject recursively
	 *
	 * @return DIContainer
	 */
	protected function unserializeSubobject( $data, $id, $semanticData ) {

		foreach ( $data['sobj'] as $subobject ) {

			if ( $subobject['subject'] === $id ) {
				$this->unserializeSemanticData( $subobject, $semanticData );
			}

		}

		return new DIContainer( $semanticData );
	}

	/**
	 * Returns DataItemId for a property
	 *
	 * @note Not sure why matching can only be done with the help of DataValueFactory
	 * because it is the only place the holds both definitions
	 *
	 * Word of caution, findPropertyTypeID is calling the Store to find the
	 * typeId reference this is costly but at the moment there is no other
	 * way to determine the typeId without the Store being involved
	 *
	 * Reason for this check is to ensure that during unserialization the
	 * correct item in terms of its definition is being sought otherwise
	 * inconsistencies can occur due to type changes of a property between
	 * the time of serialization and its unserialization (e.g for when the
	 * serialization object is stored in cache, DB etc.)
	 *
	 * @return integer
	 */
	protected function getDataItemId( DIProperty $property ) {

		if ( !isset( $this->dataItemTypeIdCache[ $property->getKey() ] ) ) {
			$this->dataItemTypeIdCache[ $property->getKey() ] = DataValueFactory::getDataItemId( $property->findPropertyTypeID() );
		}

		return $this->dataItemTypeIdCache[ $property->getKey() ];
	}

}
