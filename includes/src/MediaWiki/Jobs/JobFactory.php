<?php

namespace SMW\MediaWiki\Jobs;

use Title;

/**
 * Access MediaWiki Job instances
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class JobFactory {

	/**
	 * @since 2.0
	 *
	 * @param Title $title
	 *
	 * @return UpdateJob
	 */
	public function newUpdateJob( Title $title ) {
		return new UpdateJob( $title );
	}

	/**
	 * @since 2.0
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return UpdateDispatcherJob
	 */
	public function newUpdateDispatcherJob( Title $title, array $parameters = array() ) {
		return new UpdateDispatcherJob( $title, $parameters );
	}

}
