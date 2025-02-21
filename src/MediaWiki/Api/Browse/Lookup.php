<?php

namespace SMW\MediaWiki\Api\Browse;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
abstract class Lookup {

	/**
	 * @since 3.0
	 *
	 * @return string|int
	 */
	abstract public function getVersion();

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	// phpcs:ignore Generic.NamingConventions.ConstructorName.OldStyle
	abstract public function lookup( array $parameters );

}
