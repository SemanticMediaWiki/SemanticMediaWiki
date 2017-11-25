<?php

namespace SMW\SQLStore\ChangeOp;

use Onoi\Cache\Cache;
use SMW\DIWikiPage;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Pending the size of a diff, transferring it with the job parameter maybe too
 * large and can eventually fail during unserialization forcing a job and hereby
 * the update transaction to fail with:
 *
 * "Notice: unserialize(): Error at offset 65504 of 65535 bytes in ...
 * JobQueueDB.php on line 817"
 *
 * This class will store the diff object temporarily in Cache with the possibility
 * to retrieve it at a later point without relying on the JobQueueDB as storage
 * medium.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TempChangeOpStore implements LoggerAwareInterface {

	const CACHE_NAMESPACE = ':smw:diff:';

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * loggerInterface
	 */
	private $logger;

	/**
	 * @since 2.5
	 *
	 * @param Cache $cache
	 */
	public function __construct( Cache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @since 2.5
	 *
	 * @param ChangeOp $changeOp
	 *
	 * @return string
	 */
	public function getSlot( ChangeOp $changeOp ) {
		return smwfCacheKey( self::CACHE_NAMESPACE, $changeOp->getHash() );
	}

	/**
	 * @since 2.5
	 *
	 * @param ChangeOp $changeOp
	 *
	 * @return null|string
	 */
	public function createSlotFrom( ChangeOp $changeOp ) {

		$orderedDiffByTable = $changeOp->getOrderedDiffByTable();

		if ( $orderedDiffByTable === array() ) {
			return null;
		}

		$slot = $this->getSlot( $changeOp );

		$this->cache->save(
			$slot,
			serialize( $changeOp )
		);

		return $slot;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $slot
	 */
	public function delete( $slot ) {
		$this->cache->delete( $slot );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $slot
	 *
	 * @return ChangeOp|null
	 */
	public function newChangeOp( $slot ) {

		$changeOp = unserialize(
			$this->cache->fetch( $slot )
		);

		if ( $changeOp === false || $changeOp === null ) {
			return null;
		}

		return $changeOp;
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
