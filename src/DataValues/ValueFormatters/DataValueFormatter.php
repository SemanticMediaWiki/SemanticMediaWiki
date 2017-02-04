<?php

namespace SMW\DataValues\ValueFormatters;

use SMW\Options;
use SMWDataValue as DataValue;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
abstract class DataValueFormatter implements ValueFormatter {

	/**
	 * Return the plain wiki version of the value, or FALSE if no such version
	 * is available. The returned string suffices to reobtain the same DataValue
	 * when passing it as an input string to DataValue::setUserValue.
	 */
	const VALUE = 0;

	/**
	 * Returns a short textual representation for this data value. If the value
	 * was initialised from a user supplied string, then this original string
	 * should be reflected in this short version (i.e. no normalisation should
	 * normally happen). There might, however, be additional parts such as code
	 * for generating tooltips. The output is in wiki text.
	 */
	const WIKI_SHORT = 1;

	/**
	 * Returns a short textual representation for this data value. If the value
	 * was initialised from a user supplied string, then this original string
	 * should be reflected in this short version (i.e. no normalisation should
	 * normally happen). There might, however, be additional parts such as code
	 * for generating tooltips. The output is in HTML text.
	 */
	const HTML_SHORT = 2;

	/**
	 * Return the long textual description of the value, as printed for example
	 * in the factbox. If errors occurred, return the error message. The result
	 * is always a wiki-source string.
	 */
	const WIKI_LONG = 3;

	/**
	 * Return the long textual description of the value, as printed for
	 * example in the factbox. If errors occurred, return the error message
	 * The result always is an HTML string.
	 */
	const HTML_LONG = 4;

	/**
	 * @var DataValue
	 */
	protected $dataValue;

	/**
	 * @var Options
	 */
	private $options = null;

	/**
	 * @since 2.4
	 *
	 * @param DataValue|null $dataValue
	 */
	public function __construct( DataValue $dataValue = null ) {
		$this->dataValue = $dataValue;
	}

	/**
	 * @since 2.4
	 *
	 * @param DataValue $dataValue
	 *
	 * @return boolean
	 */
	abstract public function isFormatterFor( DataValue $dataValue );

	/**
	 * @since 2.4
	 *
	 * @param DataValue $dataValue
	 */
	public function setDataValue( DataValue $dataValue ) {
		$this->dataValue = $dataValue;
		$this->options = null;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->dataValue->getErrors();
	}

	/**
	 * @since 2.4
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setOption( $key, $value ) {

		if ( $this->options === null ) {
			$this->options = new Options();
		}

		$this->options->set( $key, $value );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $key
	 *
	 * @return mixed|false
	 */
	public function getOption( $key ) {

		if ( $this->options !== null && $this->options->has( $key ) ) {
			return $this->options->get( $key );
		}

		return false;
	}

}
