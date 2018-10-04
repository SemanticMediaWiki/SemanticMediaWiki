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
	 * Describes a processed description instance in terms of the existence of
	 * a self reference in connection with the context page a query is
	 * embedded.
	 *
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function containsSelfReference();

	/**
	 * @since 3.0
	 *
	 * @param string $condition
	 *
	 * @return Description
	 */
	public function getQueryDescription( $condition );

}
