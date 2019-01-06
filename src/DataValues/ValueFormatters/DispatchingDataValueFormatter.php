<?php

namespace SMW\DataValues\ValueFormatters;

use RuntimeException;
use SMWDataValue as DataValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DispatchingDataValueFormatter {

	/**
	 * @var DataValueFormatter[]
	 */
	private $dataValueFormatters = [];

	/**
	 * @var DataValueFormatter[]
	 */
	private $defaultDataValueFormatters = [];

	/**
	 * @since  2.4
	 *
	 * @param DataValueFormatter $dataValueFormatter
	 */
	public function addDataValueFormatter( DataValueFormatter $dataValueFormatter ) {
		$this->dataValueFormatters[] = $dataValueFormatter;
	}

	/**
	 * DataValueFormatters registered with this method are validated after
	 * DispatchingDataValueFormatter::getDataValueFormatterFor was not able to
	 * match any Formatter. This to ensure that a distinct FooStringValueFormatter
	 * is tried before the default StringValueFormatter.
	 *
	 * @since 2.4
	 *
	 * @param DataValueFormatter $dataValueFormatter
	 */
	public function addDefaultDataValueFormatter( DataValueFormatter $dataValueFormatter ) {
		$this->defaultDataValueFormatters[] = $dataValueFormatter;
	}

	/**
	 * @since 2.4
	 *
	 * @param DataValue $dataValue
	 *
	 * @return DataValueFormatter
	 * @throws RuntimeException
	 */
	public function getDataValueFormatterFor( DataValue $dataValue ) {

		foreach ( $this->dataValueFormatters as $dataValueFormatter ) {
			if ( $dataValueFormatter->isFormatterFor( $dataValue ) ) {
				$dataValueFormatter->setDataValue( $dataValue );
				return $dataValueFormatter;
			}
		}

		foreach ( $this->defaultDataValueFormatters as $dataValueFormatter ) {
			if ( $dataValueFormatter->isFormatterFor( $dataValue ) ) {
				$dataValueFormatter->setDataValue( $dataValue );
				return $dataValueFormatter;
			}
		}

		throw new RuntimeException( "The dispatcher could not match a DataValueFormatter for " . get_class( $dataValue ) );
	}

}
