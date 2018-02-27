<?php

namespace SMW\Query;

use SMW\Query\Language\Description;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
interface Parser {

	/**
	 * @since 3.0
	 *
	 * @param DIProperty|string $property
	 * @param string $value
	 *
	 * @return string
	 */
	public function createCondition( $property, $value );

	/**
	 * @since 3.0
	 *
	 * @return []
	 */
	public function getErrors();

	/**
	 * @since 3.0
	 *
	 * @param string $condition
	 *
	 * @return Description
	 */
	public function getQueryDescription( $condition );

}
