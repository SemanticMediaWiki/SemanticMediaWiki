<?php

namespace SMW\Serializers;

use OutOfBoundsException;
use Serializers\Serializer;
use SMW\SemanticData;

/**
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SemanticDataSerializer implements Serializer {

	/**
	 * @see Serializer::serialize
	 *
	 * @since  1.9
	 */
	public function serialize( $semanticData ) {

		if ( !$semanticData instanceof SemanticData ) {
			throw new OutOfBoundsException( 'Object is not supported' );
		}

		return $this->doSerialize( $semanticData ) + [ 'serializer' => __CLASS__, 'version' => 0.1 ];
	}

	private function doSerialize( SemanticData $semanticData ) {

		$data = [
			'subject' => $semanticData->getSubject()->getSerialization(),
			'data'    => $this->serializeProperty( $semanticData )
		];

		$subobjects = $this->serializeSubobject( $semanticData->getSubSemanticData() );

		if ( $subobjects !== [] ) {
			$data['sobj'] = $subobjects;
		}

		return $data;
	}

	/**
	 * Build property and dataItem serialization record
	 *
	 * @return array
	 */
	private function serializeProperty( $semanticData ) {

		$properties = [];

		foreach ( $semanticData->getProperties() as $property ) {
			$properties[] = [
				'property' => $property->getSerialization(),
				'dataitem' => $this->serializeDataItem( $semanticData, $property )
			];
		}

		return $properties;
	}

	/**
	 * Returns DataItem serialization
	 *
	 * @note 'type' is added to ensure that during unserialization the type
	 * definition of the requested data is in alignment with the definition found
	 * in the system (type changes that can occur during the time between
	 * serialization and unserialization)
	 *
	 * @return array
	 */
	private function serializeDataItem( $semanticData, $property ) {

		$dataItems = [];

		foreach ( $semanticData->getPropertyValues( $property ) as $dataItem ) {
			$dataItems[] = [
				'type' => $dataItem->getDIType(),
				'item' => $dataItem->getSerialization()
			];
		}

		return $dataItems;
	}

	/**
	 * Returns all subobjects of a SemanticData instance
	 *
	 * @return array
	 */
	private function serializeSubobject( $subSemanticData ) {

		$subobjects = [];

		foreach ( $subSemanticData as $semanticData ) {
			$subobjects[] = $this->doSerialize( $semanticData );
		}

		return $subobjects;
	}

}
