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
	private $logEventTypes = array();

	/**
	 * @note This is set in the constructor to make them non-accessible through
	 * the standard settings as those are bound to MediaWiki and this class
	 *
	 * @since 2.1
	 */
	public function __construct() {
		$GLOBALS['wgLogTypes'][] = 'smw';
		$GLOBALS['wgFilterLogTypes']['smw'] = true;
	}

	/**
	 * @since 2.1
	 *
	 * @param array $logEventTypes
	 */
	public function registerLoggableEventTypes( array $logEventTypes ) {
		$this->logEventTypes = $logEventTypes;
	}

	/**
	 * @since 2.1
	 *
	 * @return integer|null
	 */
	public function log( $type, $performer, $target, $comment ) {

		if ( !isset( $this->logEventTypes[$type] ) || !$this->logEventTypes[$type] ) {
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
