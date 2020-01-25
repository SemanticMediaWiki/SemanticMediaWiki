<?php

namespace SMW\SQLStore\EntityStore;

use SMW\RequestOptions;

/**
 * @license GNU GPL v2
 * @since 3.2
 *
 * @author mwjames
 */
class ResultLimiter {

	/**
	 * @var integer
	 */
	private $size = -1;

	/**
	 * @var []
	 */
	private $counter = [];

	/**
	 * @since 3.2
	 *
	 * @param RequestOptions $requestOptions
	 */
	public function calcSize( RequestOptions $requestOptions ) {

		$this->size = -1;
		$this->counter = [];

		// `exclude_limit` indicates an unrestricted query due to use of `WHERE IN`
		// that is required by the prefetch mode
		if ( $requestOptions->exclude_limit ) {
			$this->size = $requestOptions->getLimit();

			if ( $this->size > 0 ) {
				$this->size += $requestOptions->getLookahead();
			}

			if ( $requestOptions->getOffset() > 0 ) {
				$this->size += $requestOptions->getOffset();
			}
		}
	}

	/**
	 * For cases where `exclude_limit` was used to run an unrestricted query, allow
	 * to skip the result set to restrict the size of the set to avoid creating
	 * instances for non-requested data.
	 *
	 * @since 3.2
	 *
	 * @param integer $id
	 *
	 * @return boolean
	 */
	public function canSkip( $id ) {

		if ( $this->size < 0 ) {
			return false;
		}

		$id = (int)$id;

		if ( !isset( $this->counter[$id] ) ) {
			$this->counter[$id] = 0;
		} elseif ( $this->counter[$id] >= $this->size ) {
			return true;
		}

		$this->counter[$id]++;

		return false;
	}

}
