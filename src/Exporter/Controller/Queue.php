<?php

namespace SMW\Exporter\Controller;

use SMW\DIWikiPage;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class Queue {

	/**
	 * Maximum to not let cache arrays get larger than this
	 */
	const MAX_CACHE_SIZE = 5000;

	// kill this many cached entries if limit is reached, avoids too much array
	// copying; <= MAX_CACHE_SIZE!
	const CACHE_BACKJUMP = 500;

	/**
	 * An array that keeps track of the elements for which we still need to
	 * write auxiliary definitions/declarations.
	 */
	private $queue = [];

	/**
	 * An array that keeps track of the recursion depth with which each object
	 * has been serialised.
	 */
	private $done = [];

	/**
	 * @since 3.2
	 */
	public function clear() {
		$this->queue = [];
		$this->done = [];
	}

	/**
	 * @since 3.2
	 *
	 * @return []
	 */
	public function getMembers() : array {
		return $this->queue;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $key
	 */
	public function remove( string $key ) {
		unset( $this->queue[$key] );
	}

	/**
	 * @since 3.2
	 *
	 * @return int
	 */
	public function count() : int {
		return count( $this->queue );
	}

	/**
	 * @since 3.2
	 */
	public function reset() {
		return reset( $this->queue );
	}

	/**
	 * @since 3.2
	 *
	 * @param DIWikiPage $dataItem
	 * @param int $recdepth
	 */
	public function add( DIWikiPage $dataItem, int $recdepth ) {

		if ( $this->isDone( $dataItem, $recdepth ) ) {
			return;
		}

		// add a field
		$dataItem->recdepth = $recdepth;
		$this->queue[$dataItem->getSha1()] = $dataItem;
	}

	/**
	 * @since 3.2
	 *
	 * @param SMWDIWikiPage $st specifying the object to check
	 *
	 * @return bool
	 */
	public function isNotDone( DIWikiPage $dataItem ) : bool {
		return !isset( $this->done[$dataItem->getSha1()] );
	}

	/**
	 * Check if the given object has already been serialised at sufficient
	 * recursion depth.
	 *
	 * @since 3.2
	 *
	 * @param SMWDIWikiPage $st specifying the object to check
	 * @param int $recdepth
	 *
	 * @return boolean
	 */
	public function isDone( DIWikiPage $dataItem, int $recdepth ) : bool {
		return $this->isHashDone( $dataItem->getSha1(), $recdepth );
	}

	/**
	 * Mark an article as done while making sure that the cache used for this
	 * stays reasonably small. Input is given as an SMWDIWikiPage object.
	 *
	 * @since 3.2
	 *
	 * @param SMWDIWikiPage $st specifying the object to check
	 * @param int $recdepth
	 */
	public function done( DIWikiPage $dataItem, int $recdepth ) {
		$hash = $dataItem->getSha1();

		if ( count( $this->done ) >= self::MAX_CACHE_SIZE ) {
			$this->done = array_slice(
				$this->done, self::CACHE_BACKJUMP, self::MAX_CACHE_SIZE - self::CACHE_BACKJUMP, true
			);
		}

		// mark title as done, with given recursion
		if ( !$this->isHashDone( $hash, $recdepth ) ) {
			$this->done[$hash] = $recdepth;
		}

		// make sure it is not in the queue
		unset( $this->queue[$hash] );

	}

	/**
	 * Check if the given task has already been completed at sufficient
	 * recursion depth.
	 */
	private function isHashDone( string $hash, int $recdepth ) {

		if ( isset( $this->done[$hash] ) && $this->done[$hash] == -1 ) {
			return true;
		}

		if ( isset( $this->done[$hash] ) && ( $recdepth != -1 ) && ( $this->done[$hash] >= $recdepth ) ) {
			return true;
		}

		return false;
	}

}
