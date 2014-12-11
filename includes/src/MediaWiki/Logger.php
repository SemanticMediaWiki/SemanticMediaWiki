<?php

namespace SMW\MediaWiki;

use SMW\Logger as LoggerInterface;

use ManualLogEntry;
use Title;
use User;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class Logger implements LoggerInterface {

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
	 * {@inheritDoc}
	 *
	 * @since 2.1
	 *
	 * @return integer|null
	 */
	public function logToTable( $type, $performer, $target, $comment ) {

		if ( !isset( $this->logEventTypes[ $type ] ) || !$this->logEventTypes[ $type ] ) {
			return null;
		}

		$logEntry = $this->newManualLogEntryForType( $type );
		$logEntry->setTarget( Title::newFromText( $target ) );

		$logEntry->setPerformer( User::newFromName( $performer ) );
		$logEntry->setParameters( array() );
		$logEntry->setComment( $comment );

		return $logEntry->insert();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 2.1
	 */
	public function log( $fname, $comment ) {
		wfDebugLog( 'smw', $fname . ' ' . $comment . "\n" );
	}

	protected function newManualLogEntryForType( $type ) {
		return new ManualLogEntry( 'smw', $type );
	}

}
