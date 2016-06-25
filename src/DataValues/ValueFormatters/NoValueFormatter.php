<?php

namespace SMW\DataValues\ValueFormatters;

use RuntimeException;
use SMWDataValue as DataValue;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class NoValueFormatter extends DataValueFormatter {

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function isFormatterFor( DataValue $dataValue ) {
		return $dataValue instanceof DataValue;
	}

	/**
	 * @since 2.4
	 *
	 * {@inheritDoc}
	 */
	public function format( $type, $linker = null ) {

		if ( !$this->dataValue instanceof DataValue ) {
			throw new RuntimeException( "The formatter is missing a valid DataValue object" );
		}

		return $this->dataValue->getDataItem()->getSerialization();
	}

}
