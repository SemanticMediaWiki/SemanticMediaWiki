<?php

namespace SMW\MediaWiki\Deferred;

use MediaWiki\Deferred\DeferrableUpdate;
use MediaWiki\Deferred\DeferredUpdates;
use Psr\Log\LoggerAwareTrait;
use SMW\MediaWiki\Connection\Database;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Site;
use SMW\SQLStore\SQLStore;

/**
 * Run a deferred update on the `smw_hash` field.
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class HashFieldUpdate implements DeferrableUpdate {

	use LoggerAwareTrait;

	/**
	 * @var bool
	 */
	public static $isCommandLineMode;

	/**
	 * @since 3.1
	 */
	public function __construct(
		private Database $connection,
		private $id,
		private $hash,
	) {
	}

	/**
	 * @since 3.1
	 *
	 * @param Database $connection
	 * @param int $id
	 * @param string $hash
	 */
	public static function addUpdate( Database $connection, $id, $hash ): void {
		$hashFieldUpdate = new self( $connection, $id, $hash );

		$hashFieldUpdate->setLogger(
			ApplicationFactory::getInstance()->getMediaWikiLogger()
		);

		// Avoid deferring the update on CLI (and the DeferredUpdates::tryOpportunisticExecute)
		// since we use a Job instance to carry out the change
		if ( self::$isCommandLineMode || Site::isCommandLineMode() ) {
			$hashFieldUpdate->doUpdate();
		} else {
			DeferredUpdates::addUpdate( $hashFieldUpdate );
		}
	}

	/**
	 * @see DeferrableUpdate::doUpdate
	 *
	 * @since 3.1
	 */
	public function doUpdate(): void {
		$this->logger->info(
			[ 'DeferrableUpdate', 'HashFieldUpdate', "ID: {id}, sha1:{hash}" ],
			[ 'role' => 'user', 'id' => $this->id, 'hash' => bin2hex( $this->hash ) ]
		);

		$this->connection->update(
			SQLStore::ID_TABLE,
			[
				'smw_hash' => $this->hash
			],
			[
				'smw_id' => $this->id
			],
			__METHOD__
		);
	}

}
