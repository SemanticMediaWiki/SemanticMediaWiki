<?php

namespace SMW\DataValues\ValueValidators;

/**
 * @license GPL-2.0-or-later
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
	 * @return bool
	 */
	public function hasConstraintViolation();

}
