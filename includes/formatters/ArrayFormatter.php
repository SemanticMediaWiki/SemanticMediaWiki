<?php

namespace SMW;

/**
 * Interface related to classes responsible for array formatting
 *
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Interface related to classes responsible for array formatting
 *
 * @ingroup Formatter
 * @codeCoverageIgnore
 */
abstract class ArrayFormatter {

	/** @var array */
	protected $errors = array();

	/**
	 * Returns collected errors
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Adds an error
	 *
	 * @since 1.9
	 *
	 * @param mixed $error
	 */
	public function addError( $error ) {
		$this->errors = array_merge( (array)$error === $error ? $error : array( $error ), $this->errors );
	}

	/**
	 * Returns a formatted array
	 *
	 * @since 1.9
	 *
	 * Implementation is carried out by a subclasses
	 */
	abstract public function toArray();
}
