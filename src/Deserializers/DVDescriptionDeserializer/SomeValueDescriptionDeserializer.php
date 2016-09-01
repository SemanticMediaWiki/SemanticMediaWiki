<?php

namespace SMW\Deserializers\DVDescriptionDeserializer;

use InvalidArgumentException;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMWDataValue as DataValue;

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
	 * @param string $value
	 *
	 * @return Description
	 * @throws InvalidArgumentException
	 */
	public function deserialize( $value ) {

		if ( !is_string( $value ) ) {
			throw new InvalidArgumentException( 'Value needs to be a string' );
		}

		$comparator = SMW_CMP_EQ;
		$this->prepareValue( $value, $comparator );

		$this->dataValue->setOption(
			DataValue::OPT_QUERY_COMP_CONTEXT,
			( $comparator !== SMW_CMP_EQ && $comparator !== SMW_CMP_NEQ )
		);

		$this->dataValue->setUserValue( $value );

		if ( $this->dataValue->isValid() ) {
			return new ValueDescription(
				$this->dataValue->getDataItem(),
				$this->dataValue->getProperty(),
				$comparator
			);
		}

		return new ThingDescription();
	}

}
