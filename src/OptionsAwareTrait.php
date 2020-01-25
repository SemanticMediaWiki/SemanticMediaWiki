<?php

namespace SMW;

use SMW\Options;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
trait OptionsAwareTrait {

	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @since 3.2
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		$this->options = new Options( $options );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setOption( $key, $value ) {

		if ( $this->options === null ) {
			$this->setOptions( [] );
		}

		$this->options->set( $key, $value );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getOption( $key, $default = null ) {

		if ( $this->options === null ) {
			$this->setOptions( [] );
		}

		return $this->options->safeGet( $key, $default );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 * @param mixed $flag
	 *
	 * @return boolean
	 */
	public function isFlagSet( $key, $flag ) {
		return $this->options->isFlagSet( $key, $flag );
	}

}
