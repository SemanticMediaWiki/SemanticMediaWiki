<?php

namespace SMW;

use Deserializers\Deserializer;
use Serializers\Serializer;
use SMW\Serializers\SemanticDataSerializer;
use SMW\Deserializers\SemanticDataDeserializer;
use SMW\Serializers\ExpDataSerializer;
use SMW\Deserializers\ExpDataDeserializer;
use SMW\Serializers\QueryResultSerializer;
use SMWQueryResult as QueryResult;
use SMWExpData as ExpData;
use OutOfBoundsException;

/**
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SerializerFactory {

	/**
	 * Method that assigns registered serializers to an object
	 *
	 * @since 2.2
	 *
	 * @param mixed $object
	 *
	 * @return Serializer
	 */
	public function getSerializerFor( $object ) {

		$serializer = null;

		if ( $object instanceof SemanticData ) {
			$serializer = $this->newSemanticDataSerializer();
		} elseif ( $object instanceof QueryResult ) {
			$serializer = $this->newQueryResultSerializer();
		} elseif ( $object instanceof ExpData ) {
			$serializer = $this->newExpDataSerializer();
		}

		if ( !$serializer instanceof Serializer ) {
			throw new OutOfBoundsException( 'No serializer can be matched to the object' );
		}

		return $serializer;
	}

	/**
	 * @since 2.2
	 *
	 * @param array $serialization
	 *
	 * @return Deserializer
	 */
	public function getDeserializerFor( array $serialization ) {

		$deserializer = null;

		if ( isset( $serialization['serializer'] ) ) {

			switch ( $serialization['serializer'] ) {
				case 'SMW\Serializers\SemanticDataSerializer':
					$deserializer = $this->newSemanticDataDeserializer();
					break;
				case 'SMW\Serializers\ExpDataSerializer':
					$deserializer = $this->newExpDataDeserializer();
					break;
			}
		}

		if ( !$deserializer instanceof Deserializer ) {
			throw new OutOfBoundsException( 'No deserializer can be matched to the serialization format' );
		}

		return $deserializer;
	}

	/**
	 * @since 2.2
	 *
	 * @return SemanticDataSerializer
	 */
	public function newSemanticDataSerializer() {
		return new SemanticDataSerializer();
	}

	/**
	 * @since 2.2
	 *
	 * @return SemanticDataDeserializer
	 */
	public function newSemanticDataDeserializer() {
		return new SemanticDataDeserializer();
	}

	/**
	 * @since 2.2
	 *
	 * @return QueryResultSerializer
	 */
	public function newQueryResultSerializer() {
		return new QueryResultSerializer();
	}

	/**
	 * @since 2.2
	 *
	 * @return ExpDataSerializer
	 */
	public function newExpDataSerializer() {
		return new ExpDataSerializer();
	}

	/**
	 * @since 2.2
	 *
	 * @return ExpDataDeserializer
	 */
	public function newExpDataDeserializer() {
		return new ExpDataDeserializer();
	}

}
