<?php

namespace SMW\Serializers;

use OutOfBoundsException;
use Serializers\Serializer;
use SMW\Exporter\Element\ExpElement;
use SMWExpData as ExpData;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ExpDataSerializer implements Serializer {

	/**
	 * @see Serializer::serialize
	 *
	 * @since 2.2
	 */
	public function serialize( $expData ) {

		if ( !$expData instanceof ExpData ) {
			throw new OutOfBoundsException( 'Object is not supported' );
		}

		return $this->doSerialize( $expData ) + [ 'serializer' => __CLASS__, 'version' => 0.1 ];
	}

	private function doSerialize( $expData ) {

		$serialization = [
			'subject' => $expData->getSubject()->getSerialization()
		];

		$properties = [];

		foreach ( $expData->getProperties() as $property ) {
			$properties[$property->getUri()] = [
				'property' => $property->getSerialization(),
				'children' => $this->doSerializeChildren( $expData->getValues( $property ) )
			];
		}

		return $serialization + [ 'data' => $properties ];
	}

	private function doSerializeChildren( array $elements ) {

		$children = [];

		if ( $elements === [] ) {
			return $children;
		}

		foreach ( $elements as $element ) {
			$children[] = $element instanceof ExpElement ? $element->getSerialization() : $this->doSerialize( $element );
		}

		return $children;
	}

}
