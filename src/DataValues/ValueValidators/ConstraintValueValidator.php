<?php

namespace SMW\DataValues\ValueValidators;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
interface ConstraintValueValidator {

	/**
	 * @since 2.4
	 *
	 * @param DataValue $dataValue
	 */
	public function validate( $dataValue );

	/**
	 * @since 2.4
	 *
	 * @return boolean
	 */
	public function hasConstraintViolation();

}
