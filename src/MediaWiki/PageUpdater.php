<?php

namespace SMW\MediaWiki;

use Title;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PageUpdater {

	/**
	 * @var Title[]
	 */
	private $titles = array();

	/**
	* @since 2.1
	*
	* @param Title $title
	*/
	public function addPage( Title $title ) {
		$this->titles[$title->getPrefixedDBKey()] = $title;
	}

	/**
	* @since 2.1
	*/
	public function clear() {
		$this->titles = array();
	}

	/**
	* @since 2.1
	*
	* @return boolean
	*/
	public function canUpdate() {
		return !wfReadOnly();
	}

	/**
	* @since 2.1
	*/
	public function doPurgeParserCache() {
		foreach ( $this->titles as $title ) {
			$title->invalidateCache();
		}
	}

	/**
	* @since 2.1
	*/
	public function doPurgeHtmlCache() {
		foreach ( $this->titles as $title ) {
			$title->touchLinks();

			// @see MW 1.19 Title::invalidateCache
			\HTMLFileCache::clearFileCache( $title );
		}
	}

	/**
	* @since 2.1
	*/
	public function doPurgeWebCache() {
		foreach ( $this->titles as $title ) {
			$title->purgeSquid();
		}
	}

}
