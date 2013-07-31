<?php

namespace SMW;

/**
 * Semantic MediaWiki interface to access a cachable entity (CacheStore etc.)
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Specifies an interface to access a cachable entity (CacheStore etc.)
 *
 * @ingroup Utility
 */
interface Cacheable {

	/**
	 * Returns cachable entity
	 *
	 * @since 1.9
	 *
	 * @return Cacheable
	 */
	public function getCache();

}
