<?php

namespace SMW\Elastic\QueryEngine;

use SMW\Elastic\QueryEngine\TermsLookup\Parameters;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
interface TermsLookup {

	/**
	 * @since 3.0
	 *
	 * @return Parameters
	 */
	public function newParameters( array $parameters = [] );

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param Parameters $parameters
	 *
	 * @return array
	 */
	public function lookup( $key, Parameters $parameters );

	/**
	 * @since 3.0
	 */
	public function clear();

}
