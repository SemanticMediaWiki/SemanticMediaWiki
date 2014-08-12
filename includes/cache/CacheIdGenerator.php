<?php

namespace SMW;

/**
 * This class is responsible for generating a cache key
 *
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * This class is responsible for generating a cache key
 *
 * @ingroup Cache
 */
class CacheIdGenerator extends HashIdGenerator {

	/**
	 * Returns a prefix
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getPrefix() {
		return $this->prefix === null ? $this->buildPrefix( 'smw' ) : $this->buildPrefix( 'smw' . ':' . $this->prefix );
	}

	/**
	 * Builds a prefix string
	 *
	 * @note Somehow eliminate the global function wfWikiID
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	protected function buildPrefix( $prefix ) {
		$CachePrefix = $GLOBALS['wgCachePrefix'] === false ? wfWikiID() : $GLOBALS['wgCachePrefix'];
		return $CachePrefix . ':' . $prefix . ':';
	}

}
