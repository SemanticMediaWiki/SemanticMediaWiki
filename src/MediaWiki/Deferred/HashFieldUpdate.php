<?php

namespace SMW\MediaWiki\Deferred;

use DeferrableUpdate;
use DeferredUpdates;
use Psr\Log\LoggerAwareTrait;
use SMW\MediaWiki\Database;
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
	 * @var Database
	 */
	private $connection;

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 */
	private $hash;

	/**
	 * @var bool
	 */
	public static $isCommandLineMode;

	/**
	 * @since 3.1
	 *
	 * @param Database $connection
	 * @param int $id
	 * @param string $hash
	 */
	public function __construct( Database $connection, $id, $hash ) {
		$this->connection = $connection;
		$this->id = $id;
		$this->hash = $hash;
	}

	/**
	 * @since 3.1
	 *
	 * @param Database $connection
	 * @param int $id
	 * @param string $hash
	 */
	public static function addUpdate( Database $connection, $id, $hash ) {
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
	public function doUpdate() {
		$this->logger->info(
			[ 'DeferrableUpdate', 'HashFieldUpdate', "ID: {id}, sha1:{hash}" ],
			[ 'role' => 'user', 'id' => $this->id, 'hash' => $this->hash ]
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
