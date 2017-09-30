<?php

namespace SMW\MediaWiki\Api\Browse;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
abstract class Lookup {

	/**
	 * @since 3.0
	 *
	 * @return string|integer
	 */
	abstract public function getVersion();

	/**
	 * @since 3.0
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	abstract public function lookup( array $parameters );

}
