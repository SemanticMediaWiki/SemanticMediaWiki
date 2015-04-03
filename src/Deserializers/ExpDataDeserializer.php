<?php

namespace SMW\Deserializers;

use Deserializers\Deserializer;
use SMW\Serializers\ExpDataSerializer;
use SMW\Exporter\Element\ExpElement;
use SMWExpData as ExpData;
use OutOfBoundsException;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ExpDataDeserializer implements Deserializer {

	/**
	 * @see Deserializers::deserialize
	 *
	 * @since 2.2
	 *
	 * @return ExpData
	 * @throws OutOfBoundsException
	 */
	public function deserialize( $serialization ) {

		$expData = null;

		if ( isset( $serialization['version'] ) && $serialization['version'] !== 0.1 ) {
			throw new OutOfBoundsException( 'Serializer/Deserializer version does not match, please update your data' );
		}

		if ( isset( $serialization['subject'] ) ) {
			$expData = $this->newExpData( $serialization['subject'] );
		}

		if ( !$expData instanceof ExpData ) {
			throw new OutOfBoundsException( 'ExpData could not be created probably due to an invalid subject' );
		}

		$this->doDeserialize( $serialization, $expData );

		return $expData;
	}

	private function newExpData( $subject ) {
		return new ExpData( ExpElement::newFromSerialization( $subject ) );
	}

	private function doDeserialize( $serialization, $expData ) {

		foreach ( $serialization['data'] as $data ) {

			$property = ExpElement::newFromSerialization( $data['property'] );

			foreach ( $data['children'] as $child ) {
				$expData->addPropertyObjectValue(
					$property,
					$this->doDeserializeChild( $child )
				);
			}
		}
	}

	private function doDeserializeChild( $serialization ) {

		if ( !isset( $serialization['subject'] ) ) {
			return ExpElement::newFromSerialization( $serialization );
		}

		$element = $this->newExpData( $serialization['subject'] );
		$this->doDeserialize( $serialization, $element );

		return $element;
	}

}
