<?php

namespace SMW\Maintenance;

use SMW\MediaWiki\ManualEntryLogger;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class MaintenanceLogger {

	/**
	 * @var string
	 */
	private $performer = '';

	/**
	 * @var ManualEntryLogger
	 */
	private $manualEntryLogger;

	/**
	 * @since 2.4
	 *
	 * @param string $performer
	 * @param ManualEntryLogger $manualEntryLogger
	 */
	public function __construct( $performer, ManualEntryLogger $manualEntryLogger ) {
		$this->performer = $performer;
		$this->manualEntryLogger = $manualEntryLogger;
		$this->manualEntryLogger->registerLoggableEventType( 'maintenance' );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $message
	 * @param string $target
	 */
	public function log( $message, $target = '' ) {

		if ( $target === '' ) {
			$target = $this->performer;
		}

		$this->manualEntryLogger->log( 'maintenance', $this->performer, $target, $message );
	}

}
