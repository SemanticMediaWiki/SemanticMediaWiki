<?php

namespace SMW\Schema;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
interface Schema {

	const SCHEMA_TYPE = 'type';
	const SCHEMA_DESCRIPTION = 'description';
	const SCHEMA_TAG = 'tags';

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
	 * Returns the name of the schema which is equivalent with the page name
	 * without the namespace prefix.
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
	public function getValidationSchema();

}
