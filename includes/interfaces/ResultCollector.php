<?php

namespace SMW;

/**
 * Interface for items of groups of individuals to be sampled into a
 * collection of values
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface ResultCollector {

	/**
	 * Intiates collection of items
	 *
	 * @since  1.9
	 *
	 * @return array
	 */
	public function runCollector();

	/**
	 * Returns collected items
	 *
	 * @since  1.9
	 *
	 * @return array
	 */
	public function getResults();

}