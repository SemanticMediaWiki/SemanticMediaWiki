<?php

namespace SMW\MediaWiki\Deferred;

use DeferrableUpdate;
use DeferredUpdates;
use Title;
use SMW\ApplicationFactory;
use Psr\Log\LoggerAwareTrait;
use SMW\MediaWiki\Database;
use SMW\Site;
use SMW\SQLStore\SQLStore;

/**
 * Run a deferred update on the `smw_hash` field.
 *
 * @license GNU GPL v2+
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
	 * @var integer
	 */
	private $id;

	/**
	 * @var string
	 */
	private $hash;

	/**
	 * @var boolean
	 */
	public static $isCommandLineMode;

	/**
	 * @since 3.1
	 *
	 * @param Database $connection
	 * @param integer $id
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
	 * @param integer $id
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
