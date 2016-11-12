<?php

namespace SMW\MediaWiki\Jobs;

use SMW\ApplicationFactory;
use SMW\SQLStore\TransitionalTableDiffStore;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SequentialCachePurgeJob extends JobBase {

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = array() ) {
		parent::__construct( 'SMW\SequentialCachePurgeJob', $title, $params );
	}

	/**
	 * @see Job::run
	 *
	 * @since  2.5
	 */
	public function run() {

		if ( $this->hasParameter( 'slot:id' ) ) {
			$this->doPurgeTransitionalDiffStore( $this->getParameter( 'slot:id' ) );
		}

		return true;
	}

	private function doPurgeTransitionalDiffStore( $slot ) {
		ApplicationFactory::getInstance()->singleton( 'TransitionalDiffStore' )->delete( $slot );
		wfDebugLog( 'smw', __METHOD__ . ' :: '. $slot );
	}

}
