<?php

namespace SMW\Maintenance;

use RuntimeException;
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
	 * @var integer
	 */
	private $maxNameChars = 255;

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
	 * @since 2.5
	 *
	 * @param integer $maxNameChars
	 */
	public function setMaxNameChars( $maxNameChars ) {
		$this->maxNameChars = $maxNameChars;
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

		// #1983
		if ( $this->maxNameChars < strlen( $target ) ) {
			throw new RuntimeException( 'wgMaxNameChars requires at least ' . strlen( $target ) );
		}

		$this->manualEntryLogger->log( 'maintenance', $this->performer, $target, $message );
	}

}
