<?php

namespace SMW\DataValues;

use SMW\DataValues\ValueFormatters\CodeStringValueFormatter;
use SMW\DataValues\ValueFormatters\DataValueFormatter;
use SMW\DataValues\ValueFormatters\DispatchingDataValueFormatter;
use SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter;
use SMW\DataValues\ValueFormatters\NoValueFormatter;
use SMW\DataValues\ValueFormatters\NumberValueFormatter;
use SMW\DataValues\ValueFormatters\StringValueFormatter;
use SMW\DataValues\ValueFormatters\TimeValueFormatter;
use SMWDataValue as DataValue;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ValueFormatterRegistry {

	/**
	 * @var ValueFormatterRegistry
	 */
	private static $instance = null;

	/**
	 * @var DispatchingDataValueFormatter
	 */
	private $dispatchingDataValueFormatter = null;

	/**
	 * @since 2.4
	 *
	 * @param DispatchingDataValueFormatter|null $dispatchingDataValueFormatter
	 */
	public function __construct( DispatchingDataValueFormatter $dispatchingDataValueFormatter = null ) {
		$this->dispatchingDataValueFormatter = $dispatchingDataValueFormatter;
	}

	/**
	 * @since 2.4
	 *
	 * @return self
	 */
	public static function getInstance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @since 2.4
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @note This allows extensions to inject their own DataValueFormatter
	 * without further violating SRP of the DataType or DataValue.
	 *
	 * @since 2.4
	 *
	 * @param DataValueFormatter $dataValueFormatter
	 */
	public function registerDataValueFormatter( DataValueFormatter $dataValueFormatter ) {

		if ( $this->dispatchingDataValueFormatter === null ) {
			$this->dispatchingDataValueFormatter = $this->newDispatchingDataValueFormatter();
		}

		$this->dispatchingDataValueFormatter->addDataValueFormatter( $dataValueFormatter );
	}

	/**
	 * @since 2.4
	 *
	 * @param DataValue $dataValue
	 *
	 * @return DataValueFormatter
	 */
	public function getDataValueFormatterFor( DataValue $dataValue ) {

		if ( $this->dispatchingDataValueFormatter === null ) {
			$this->dispatchingDataValueFormatter = $this->newDispatchingDataValueFormatter();
		}

		return $this->dispatchingDataValueFormatter->getDataValueFormatterFor( $dataValue );
	}

	private function newDispatchingDataValueFormatter() {

		$dispatchingDataValueFormatter = new DispatchingDataValueFormatter();
		$dispatchingDataValueFormatter->addDataValueFormatter( new MonolingualTextValueFormatter() );
		$dispatchingDataValueFormatter->addDataValueFormatter( new CodeStringValueFormatter() );

		// To be checked only after DispatchingDataValueFormatter::addDataValueFormatter did
		// not match any previous registered DataValueFormatters
		$dispatchingDataValueFormatter->addDefaultDataValueFormatter( new StringValueFormatter() );
		$dispatchingDataValueFormatter->addDefaultDataValueFormatter( new NumberValueFormatter() );
		$dispatchingDataValueFormatter->addDefaultDataValueFormatter( new TimeValueFormatter() );
		$dispatchingDataValueFormatter->addDefaultDataValueFormatter( new NoValueFormatter() );

		return $dispatchingDataValueFormatter;
	}

}
