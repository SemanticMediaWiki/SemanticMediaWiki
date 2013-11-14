<?php

namespace SMW;

/**
 * Specifing the QueryProfiler interface
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface QueryProfiler {

	/**
	 * Returns a SemanticData container
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function getSemanticData();

	/**
	 * Returns the query meta data property
	 *
	 * @since 1.9
	 *
	 * @return DIProperty
	 */
	public function getProperty();

	/**
	 * Returns the query meta data container
	 *
	 * @since 1.9
	 *
	 * @return DIContainer
	 */
	public function getContainer();

	/**
	 * Create a query profile
	 *
	 * @since 1.9
	 *
	 * @return QueryProfiler
	 */
	public function createProfile();

}
