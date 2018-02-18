<?php

namespace SMW\Rule;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
interface RuleDef {

	const RULE_TYPE = 'type';
	const RULE_DESCRIPTION = 'description';
	const RULE_TAG = 'tags';

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get( $key, $default = null );

	/**
	 * Returns the name of rule definition.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getSchema();

}
