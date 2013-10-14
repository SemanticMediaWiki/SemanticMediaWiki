<?php

namespace SMW;

use SMW\Serializers\Serializer;
use SMW\Deserializers\Deserializer;
use SMW\Serializers\SemanticDataSerializer;
use SMW\Deserializers\SemanticDataDeserializer;

use OutOfBoundsException;

/**
 * Serializer handler for a serializable object
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * A factory class that assigns registered serializers to an object and
 * identifies an unserializer based on the invoked array.
 *
 * @ingroup SMW
 */
class SerializerFactory {

	/**
	 * Initiates serialization of an object
	 *
	 * @since 1.9
	 *
	 * @param mixed $object
	 *
	 * @return array
	 */
	public static function serialize( $object ) {

		$serializer = null;

		if ( $object instanceof SemanticData ) {
			$serializer = new SemanticDataSerializer;
		}

		if ( !( $serializer instanceof Serializer ) ) {
			throw new OutOfBoundsException( 'For the object no serializer has been registered' );
		}

		return $serializer->serialize( $object );
	}

	/**
	 * Initiates unserialization of an object
	 *
	 * @note Each object is expected to hold the serializer/unserializer reference
	 * class within in its records otherwise an exception is raised
	 *
	 * @since 1.9
	 *
	 * @param array $object
	 *
	 * @return mixed
	 */
	public static function deserialize( array $object ) {

		$deserializer = null;

		if ( isset( $object['serializer'] ) ) {

			switch ( $object['serializer'] ) {
				case 'SMW\Serializers\SemanticDataSerializer':
					$deserializer = new SemanticDataDeserializer;
					break;
				default:
					$deserializer = null;
			}

		}

		if ( !( $deserializer instanceof Deserializer ) ) {
			throw new OutOfBoundsException( 'For the object no deserializer has been registered' );
		}

		return $deserializer->deserialize( $object );
	}

}
