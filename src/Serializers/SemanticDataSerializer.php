<?php

namespace SMW\Serializers;

use OutOfBoundsException;
use Serializers\Serializer;
use SMW\SemanticData;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @license GPL-2.0-or-later
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
	public function serialize( $semanticData, $includeInverse = false ) {
		if ( !$semanticData instanceof SemanticData ) {
			throw new OutOfBoundsException( 'Object is not supported' );
		}

		$data = $this->doSerialize( $semanticData );

		// If inverse properties are requested, we serialize them as well.
		if ( $includeInverse ) {
			$data['data'] = array_merge(
				$data['data'],
				$this->doSerializeInverseProperties( $semanticData )
			);
		}

		return $data + [ 'serializer' => __CLASS__, 'version' => 2 ];
	}

	private function doSerialize( SemanticData $semanticData ) {
		$data = [
			'subject' => $semanticData->getSubject()->getSerialization(),
			'data'    => $this->doSerializeProperty( $semanticData )
		];

		$subobjects = $this->doSerializeSubSemanticData(
			$semanticData->getSubSemanticData()
		);

		if ( $subobjects !== [] ) {
			$data['sobj'] = $subobjects;
		}

		return $data;
	}

	/**
	 * Serializes all direct properties of a given semantic subject,
	 * including the property name, its associated data item and direction flag.
	 *
	 * @param SemanticData $semanticData The semantic data of the current subject.
	 * @return array List of serialized direct properties with their values.
	 */
	private function doSerializeProperty( $semanticData ) {
		$properties = [];

		foreach ( $semanticData->getProperties() as $property ) {
			$properties[] = [
				'property' => $property->getSerialization(),
				'dataitem' => $this->doSerializeDataItem( $semanticData, $property ),
				'direction'	=> 'direct'
			];
		}

		return $properties;
	}

	/**
	 * Serializes all inverse properties for which the current subject
	 * is the object, including property name, subjects and direction flag.
	 *
	 * @param SemanticData $semanticData The semantic data of the current subject.
	 * @return array List of serialized inverse properties and referencing subjects.
	 */
	private function doSerializeInverseProperties( SemanticData $semanticData ) {
		$inverseData = [];
		$dataItem = $semanticData->getSubject();

		$store = ApplicationFactory::getInstance()->getStore();
		$incomingProperties = $store->getInProperties( $dataItem );
		$semanticDataIncoming = new SemanticData( $dataItem );

		if ( isset( $incomingProperties ) && count( $incomingProperties ) > 0 ) {
			foreach ( $incomingProperties as $property ) {
				$subjects = $store->getPropertySubjects( $property, $dataItem );

				if ( $subjects === [] ) {
					continue;
				}

				foreach ( $subjects as $subject ) {
					$semanticDataIncoming->addPropertyObjectValue( $property, $subject );
				}
			}

			foreach ( $semanticDataIncoming->getProperties() as $property ) {
				$inverseData[] = [
					'property' => $property->getSerialization(),
					'dataitem' => $this->doSerializeDataItem( $semanticDataIncoming, $property ),
					'direction' => 'inverse'
				];
			}
		}

		return $inverseData;
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
	private function doSerializeDataItem( $semanticData, $property ) {
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
	protected function doSerializeSubSemanticData( $subSemanticData ) {
		$subobjects = [];

		foreach ( $subSemanticData as $semanticData ) {
			$subobjects[] = $this->doSerialize( $semanticData );
		}

		return $subobjects;
	}

}
