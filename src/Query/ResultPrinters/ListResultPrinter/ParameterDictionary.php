<?php

namespace SMW\Query\ResultPrinters\ListResultPrinter;

/**
 * Class ParameterDictionary
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author Stephan Gambke
 */
class ParameterDictionary {

	private $configuration = [];

	/**
	 * @param string|string[] $setting
	 * @param mixed $value
	 */
	public function set( $setting, $value = null ) {

		if ( !is_array( $setting ) ) {
			$setting = [ $setting => $value ];
		}

		$this->configuration = array_replace( $this->configuration, $setting );
	}

	/**
	 * @param string $setting
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get( $setting, $default = '' ) {
		return isset( $this->configuration[ $setting ] ) ? $this->configuration[ $setting ] : $default;
	}

	/**
	 * @param string|string[] $setting
	 * @param mixed $value
	 */
	public function setDefault( $setting, $value = null ) {

		if ( !is_array( $setting ) ) {
			$setting = [ $setting => $value ];
		}

		$this->configuration = array_replace( $setting, $this->configuration );
	}

}