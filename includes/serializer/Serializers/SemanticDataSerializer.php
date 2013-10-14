<?php

namespace SMW\Serializers;

use SMW\SemanticData;
use SMWDataItem as DataItem;

use OutOfBoundsException;

/**
 * @since 1.9
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
class SemanticDataSerializer implements Serializer {

	/**
	 * @see Serializers::serialize
	 *
	 * @since  1.9
	 */
	public function serialize( $object ) {

		if ( !$this->isSerializerFor( $object ) ) {
			throw new OutOfBoundsException( 'Object is not supported' );
		}

		return $this->serializeSemanticData( $object ) + array( 'serializer' => __CLASS__, 'version' => 0.1 );
	}

	/**
	 * @see Serializers::isSerializerFor
	 *
	 * @since  1.9
	 */
	public function isSerializerFor( $semanticData ) {
		return $semanticData instanceof SemanticData;
	}

	/**
	 * @since  1.9
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

}
