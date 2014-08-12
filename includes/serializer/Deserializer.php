<?php

namespace SMW\Deserializers;

/**
 * Borrowed from Serializers\Deserializer as drop-in interface
 *
 * @since 1.0
 *
 * @ingroup Serialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Deserializer {

	/**
	 * @since 1.0
	 *
	 * @param mixed $serialization
	 *
	 * @return object
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization );

	/**
	 * Returns if the deserializer can deserialize the provided object.
	 *
	 * @since 1.0
	 *
	 * @param mixed $serialization
	 *
	 * @return boolean
	 */
	public function isDeserializerFor( $serialization );

}
