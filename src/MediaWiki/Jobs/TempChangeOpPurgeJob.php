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
class TempChangeOpPurgeJob extends JobBase {

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = array() ) {
		parent::__construct( 'SMW\TempChangeOpPurgeJob', $title, $params );
		$this->logger = ApplicationFactory::getInstance()->getMediaWikiLogger();
	}

	/**
	 * @see Job::run
	 *
	 * @since  2.5
	 */
	public function run() {

		if ( $this->hasParameter( 'slot:id' ) ) {
			$this->doPurgeTempChangeOpStore( $this->getParameter( 'slot:id' ) );
		}

		return true;
	}

	private function doPurgeTempChangeOpStore( $slot ) {
		ApplicationFactory::getInstance()->singleton( 'TempChangeOpStore' )->delete( $slot );
		$this->log( __METHOD__ . ' :: '. $slot );
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
