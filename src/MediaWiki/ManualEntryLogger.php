<?php

namespace SMW\MediaWiki;

use LogEntry;
use ManualLogEntry;
use Title;
use User;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ManualEntryLogger {

	/**
	 * @var logEntry
	 */
	private $logEntry = null;

	/**
	 * @var array
	 */
	private $eventTypes = [];

	/**
	 * @since 2.4
	 *
	 * @param LogEntry|null $logEntry
	 */
	public function __construct( LogEntry $logEntry = null ) {
		$this->logEntry = $logEntry;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $eventTypes
	 */
	public function registerLoggableEventType( $eventType ) {
		$this->eventTypes[$eventType] = true;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $type
	 * @param string $performer
	 * @param string $target
	 * @param string $comment
	 *
	 * @return integer|null
	 */
	public function log( $type, $performer, $target, $comment ) {

		if ( !isset( $this->eventTypes[$type] ) || !$this->eventTypes[$type] ) {
			return null;
		}

		$logEntry = $this->newManualLogEntryForType( $type );
		$logEntry->setTarget( Title::newFromText( $target ) );

		if ( is_string( $performer) ) {
			$performer = User::newFromName( $performer );
		}

		$logEntry->setPerformer( $performer );
		$logEntry->setParameters( [] );
		$logEntry->setComment( $comment );

		return $logEntry->insert();
	}

	protected function newManualLogEntryForType( $type ) {

		if ( $this->logEntry !== null ) {
			return $this->logEntry;
		}

		return new ManualLogEntry( 'smw', $type );
	}

}
