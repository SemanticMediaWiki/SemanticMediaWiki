<?php

namespace SMW\Schema;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
interface SchemaFilter {

	/**
	 * Indicates that a specific filter condition is not required to exists.
	 *
	 * For example, in case `NamespaceFilter` is marked with to be not required
	 * then both rule sets will be for inspection.
	 *
	 *```
	 * {
	 *	"if": {
	 *		"category": { "anyOf": [ "Foo", "Bar" ] }
	 *	}
	 *}
	 *```
	 *```
	 * {
	 *	"if": {
	 *		"namespace": "NS_MAIN",
	 *		"category": { "anyOf": [ "Foo", "Bar" ] }
	 *	}
	 *}
	 *```
	 */
	const FILTER_CONDITION_NOT_REQUIRED = 'filter/condition/not_required';

	/**
	 * @since 3.2
	 *
	 * @return bool
	 */
	public function hasMatches() : bool;

	/**
	 * @since 3.2
	 *
	 * @return iterable
	 */
	public function getMatches() : iterable;

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function addOption( string $key, $value );

	/**
	 * @since 3.2
	 *
	 * @param iterable $comparators
	 */
	public function filter( iterable $comparators );

}
