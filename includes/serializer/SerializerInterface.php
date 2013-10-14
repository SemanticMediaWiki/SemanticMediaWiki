<?php

namespace SMW;

/**
 * Serializer interface
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Common interface to access a serializable object
 *
 * @ingroup SMW
 */
interface SerializerInterface {

	/**
	 * Initiates serialization of an arbitrary object
	 *
	 * @since 1.9
	 *
	 * @param mixed $object
	 *
	 * @return array
	 */
	public function serialize( $object );

	/**
	 * Initiates unserialization of an arbitrary object
	 *
	 * @since 1.9
	 *
	 * @param array $object
	 *
	 * @return mixed
	 */
	public function unserialize( array $object );

}
