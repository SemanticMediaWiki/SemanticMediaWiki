<?php

namespace SMW;

/**
 * Describe a typed processing error allowing to distinguish different
 * types of errors.
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
interface ProcessingError {

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getType();

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getHash();

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function encode();

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function __toString();

}
