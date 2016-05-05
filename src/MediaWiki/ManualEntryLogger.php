<?php

namespace SMW\MediaWiki;

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
	 * @var array
	 */
	private $eventTypes = array();

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
	 * @param array $eventTypes
	 */
	public function registerLoggableEventTypes( array $eventTypes ) {
		$this->eventTypes = $eventTypes;
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

		$logEntry->setPerformer( User::newFromName( $performer ) );
		$logEntry->setParameters( array() );
		$logEntry->setComment( $comment );

		return $logEntry->insert();
	}

	protected function newManualLogEntryForType( $type ) {
		return new ManualLogEntry( 'smw', $type );
	}

}
