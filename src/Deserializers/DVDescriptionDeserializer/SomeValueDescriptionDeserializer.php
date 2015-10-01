<?php

namespace SMW\Deserializers\DVDescriptionDeserializer;

use SMWDataValue as DataValue;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use InvalidArgumentException;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class SomeValueDescriptionDeserializer extends DescriptionDeserializer {

	/**
	 * @since 2.3
	 *
	 * {@inheritDoc}
	 */
	public function isDeserializerFor( $serialization ) {
		return $serialization instanceof DataValue;
	}

	/**
	 * @since 2.3
	 *
	 * {@inheritDoc}
	 */
	public function deserialize( $value ) {

		if ( !is_string( $value ) ) {
			throw new InvalidArgumentException( 'value needs to be a string' );
		}

		$comparator = SMW_CMP_EQ;

		$this->prepareValue( $value, $comparator );

		if( $comparator == SMW_CMP_LIKE ) {
			// ignore allowed values when the LIKE comparator is used (BUG 21893)
			$this->dataValue->setUserValue( $value, false, true );
		} else {
			$this->dataValue->setUserValue( $value );
		}

		if ( $this->dataValue->isValid() ) {
			return new ValueDescription( $this->dataValue->getDataItem(), $this->dataValue->getProperty(), $comparator );
		}

		return new ThingDescription();
	}

}
