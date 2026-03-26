<?php

namespace SMW\Maintenance;

use RuntimeException;
use SMW\MediaWiki\ManualEntryLogger;

/**
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class MaintenanceLogger {

	/**
	 * @var int
	 */
	private $maxNameChars = 255;

	/**
	 * @since 2.4
	 */
	public function __construct(
		private $performer,
		private readonly ManualEntryLogger $manualEntryLogger,
	) {
		$this->manualEntryLogger->registerLoggableEventType( 'maintenance' );
	}

	/**
	 * @since 2.5
	 *
	 * @param int $maxNameChars
	 */
	public function setMaxNameChars( $maxNameChars ): void {
		$this->maxNameChars = $maxNameChars;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $message
	 * @param string $target
	 */
	public function logFromArray( array $message, $target = '' ): void {
		if ( isset( $message['Options'] ) ) {
			unset( $message['Options']['with-maintenance-log'] );
			unset( $message['Options']['memory-limit'] );
			unset( $message['Options']['profiler'] );
			unset( $message['Options']['conf'] );

			// If it is null then removed it.
			if ( !isset( $message['Options']['auto-recovery'] ) ) {
				unset( $message['Options']['auto-recovery'] );
			}
		}

		$this->log( self::formatMessage( $message ), $target );
	}

	private static function formatMessage( array $message ): string {
		$parts = [];

		foreach ( $message as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( $value === [] ) {
					continue;
				}
				$value = json_encode( $value );
			} elseif ( $key === 'Memory used' ) {
				$value = self::formatBytes( (int)$value );
			}

			$parts[] = "$key: $value";
		}

		return implode( ', ', $parts );
	}

	private static function formatBytes( int $bytes ): string {
		if ( $bytes >= 1073741824 ) {
			return round( $bytes / 1073741824, 2 ) . ' GB';
		}

		if ( $bytes >= 1048576 ) {
			return round( $bytes / 1048576, 2 ) . ' MB';
		}

		if ( $bytes >= 1024 ) {
			return round( $bytes / 1024, 2 ) . ' KB';
		}

		return $bytes . ' bytes';
	}

	/**
	 * @since 2.4
	 *
	 * @param string $message
	 * @param string $target
	 */
	public function log( $message, $target = '' ): void {
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
