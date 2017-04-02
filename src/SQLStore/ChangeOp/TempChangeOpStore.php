<?php

namespace SMW\SQLStore\ChangeOp;

use Onoi\Cache\Cache;
use SMW\DIWikiPage;
use SMW\SQLStore\ChangeOp\TableChangeOp;
use SMW\SQLStore\CompositePropertyTableDiffIterator;
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
 * It is expected that the ChronologyPurgeJob is removing inactive slots.
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
	 * @var string
	 */
	private $prefix = '';

	/**
	 * loggerInterface
	 */
	private $logger;

	/**
	 * @since 2.5
	 *
	 * @param Cache $cache
	 * @param string $prefix
	 */
	public function __construct( Cache $cache, $prefix = '' ) {
		$this->cache = $cache;
		$this->prefix = $prefix;
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
	 * @param CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator
	 *
	 * @return string
	 */
	public function getSlot( CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator ) {
		return $this->prefix . self::CACHE_NAMESPACE . $compositePropertyTableDiffIterator->getHash();
	}

	/**
	 * @since 2.5
	 *
	 * @param CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator
	 *
	 * @return null|string
	 */
	public function createSlotFrom( CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator ) {

		$orderedDiffByTable = $compositePropertyTableDiffIterator->getOrderedDiffByTable();

		if ( $orderedDiffByTable === array() ) {
			return null;
		}

		$slot = $this->getSlot( $compositePropertyTableDiffIterator );

		$this->cache->save(
			$slot,
			serialize( $compositePropertyTableDiffIterator )
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
	 * @return CompositePropertyTableDiffIterator|null
	 */
	public function newCompositePropertyTableDiffIterator( $slot ) {

		$compositePropertyTableDiffIterator = unserialize(
			$this->cache->fetch( $slot )
		);

		if ( $compositePropertyTableDiffIterator === false || $compositePropertyTableDiffIterator === null ) {
			return null;
		}

		return $compositePropertyTableDiffIterator;
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
