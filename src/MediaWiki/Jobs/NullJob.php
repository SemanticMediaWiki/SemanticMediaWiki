<?php

namespace SMW\MediaWiki\Jobs;

use SMW\ApplicationFactory;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class NullJob extends JobBase {

	/**
	 * @since 2.5
	 *
	 * @param Title|null $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title = null, $params = array() ) {
	}

	/**
	 * @see Job::run
	 *
	 * @since  2.5
	 */
	public function run() {
		return true;
	}

	/**
	 * @see JobBase::insert
	 *
	 * @since  2.5
	 */
	public function insert() {
	}

}
