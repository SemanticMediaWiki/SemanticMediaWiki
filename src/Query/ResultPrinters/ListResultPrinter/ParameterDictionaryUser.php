<?php

namespace SMW\Query\ResultPrinters\ListResultPrinter;

/**
 * Class RowBuilder
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author Stephan Gambke
 */
trait ParameterDictionaryUser {

	/** @var ParameterDictionary */
	private $configuration;

	/**
	 * @param ParameterDictionary $configuration
	 */
	public function setConfiguration( ParameterDictionary &$configuration ) {
		$this->configuration = $configuration;
	}

	/**
	 * @param string $setting
	 * @param string $default
	 *
	 * @return mixed
	 */
	protected function get( $setting, $default = '' ) {
		return $this->configuration->get( $setting, $default );
	}

}