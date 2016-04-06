<?php

namespace SMW\Deserializers\DVDescriptionDeserializer;

use RuntimeException;
use SMWDataValue as DataValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class DispatchingDescriptionDeserializer {

	/**
	 * @var DescriptionDeserializer[]
	 */
	private $descriptionDeserializers = array();

	/**
	 * @var DescriptionDeserializer
	 */
	private $defaultDescriptionDeserializer = null;

	/**
	 * @since  2.3
	 *
	 * @param DescriptionDeserializer $descriptionDeserializer
	 */
	public function addDescriptionDeserializer( DescriptionDeserializer $descriptionDeserializer ) {
		$this->descriptionDeserializers[] = $descriptionDeserializer;
	}

	/**
	 * @since 2.3
	 *
	 * @param DescriptionDeserializer $defaultDescriptionDeserializer
	 */
	public function addDefaultDescriptionDeserializer( DescriptionDeserializer $defaultDescriptionDeserializer ) {
		$this->defaultDescriptionDeserializer = $defaultDescriptionDeserializer;
	}

	/**
	 * @since 2.3
	 *
	 * @param DataValue $dataValue
	 *
	 * @return DescriptionDeserializer
	 * @throws RuntimeException
	 */
	public function getDescriptionDeserializerFor( DataValue $dataValue ) {

		foreach ( $this->descriptionDeserializers as $descriptionDeserializer ) {
			if ( $descriptionDeserializer->isDeserializerFor( $dataValue ) ) {
				$descriptionDeserializer->setDataValue( $dataValue );
				return $descriptionDeserializer;
			}
		}

		if ( $this->defaultDescriptionDeserializer !== null && $this->defaultDescriptionDeserializer->isDeserializerFor( $dataValue ) ) {
			$this->defaultDescriptionDeserializer->setDataValue( $dataValue );
			return $this->defaultDescriptionDeserializer;
		}

		throw new RuntimeException( "Missing registered DescriptionDeserializer." );
	}

}
