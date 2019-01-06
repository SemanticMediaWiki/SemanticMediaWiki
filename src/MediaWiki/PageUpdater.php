<?php

namespace SMW\MediaWiki;

use DeferrableUpdate;
use DeferredpendingUpdates;
use Psr\Log\LoggerAwareTrait;
use SMW\MediaWiki\Deferred\TransactionalCallableUpdate;
use SMW\Utils\Timer;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class PageUpdater implements DeferrableUpdate {

	use LoggerAwareTrait;

	/**
	 * @var TransactionalCallableUpdate
	 */
	private $transactionalCallableUpdate;

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var Title[]
	 */
	private $titles = [];

	/**
	 * @var string
	 */
	private $origin = '';

	/**
	 * @var string|null
	 */
	private $fingerprint = null;

	/**
	 * @var boolean
	 */
	private $isHtmlCacheUpdate = true;

	/**
	 * @var boolean
	 */
	private $onTransactionIdle = false;

	/**
	 * @var boolean
	 */
	private $asPoolPurge = false;

	/**
	 * @var boolean
	 */
	private $isPending = false;

	/**
	 * @var array
	 */
	private $pendingUpdates = [];

	/**
	 * @since 2.5
	 *
	 * @param Database|null $connection
	 * @param TransactionalCallableUpdate|null $transactionalCallableUpdate
	 */
	public function __construct( Database $connection = null, TransactionalCallableUpdate $transactionalCallableUpdate = null ) {
		$this->connection = $connection;
		$this->transactionalCallableUpdate = $transactionalCallableUpdate;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $origin
	 */
	public function setOrigin( $origin ) {
		$this->origin = $origin;
	}

	/**
	 * @since 3.0
	 *
	 * @param string|null $fingerprint
	 */
	public function setFingerprint( $fingerprint = null ) {
		$this->fingerprint = $fingerprint;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isHtmlCacheUpdate
	 */
	public function isHtmlCacheUpdate( $isHtmlCacheUpdate ) {
		$this->isHtmlCacheUpdate = $isHtmlCacheUpdate;
	}

	/**
	 * @since 3.0
	 *
	 * @param booloan $isPending
	 */
	public function markAsPending() {
		$this->isPending = true;
	}

	/**
	 * @since 2.1
	 *
	 * @param Title|null $title
	 */
	public function addPage( Title $title = null ) {

		if ( $title === null ) {
			return;
		}

		$this->titles[$title->getDBKey()] = $title;
	}

	/**
	 * @note MW 1.29+ runs Title::invalidateCache in AutoCommitUpdate which has
	 * been shown to cause transaction issues when executed while a transaction
	 * hasn't finished therefore use 'onTransactionIdle' to isolate the
	 * execution.
	 *
	 * @since 2.5
	 */
	public function waitOnTransactionIdle() {
		$this->onTransactionIdle = true;
	}

	/**
	 * Controls the purge to use a direct DB access to make changes to avoid
	 * racing conditions for a large number of title entities.
	 *
	 * @since 3.0
	 */
	public function doPurgeParserCacheAsPool() {
		if ( $this->connection !== null ) {
			$this->connection->onTransactionIdle( function() {
				 $this->doPoolPurge();
			} );
		} else {
			$this->doPoolPurge();
		}
	}

	/**
	 * @since 2.1
	 */
	public function clear() {
		$this->titles = [];
	}

	/**
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function canUpdate() {
		return !wfReadOnly();
	}

	/**
	 * Push pendingUpdates to be either deferred or direct executable, pending
	 * the setting invoked by PageUPdater::markAsPending.
	 *
	 * @since 3.0
	 */
	public function pushUpdate() {

		if ( $this->transactionalCallableUpdate === null ) {
			return $this->log( __METHOD__ . ' it is not possible to push updates as DeferredTransactionalUpdate)' );
		}

		$this->transactionalCallableUpdate->setCallback( function(){
			$this->doUpdate();
		} );

		if ( $this->onTransactionIdle ) {
			$this->transactionalCallableUpdate->waitOnTransactionIdle();
		}
		if ( $this->isPending ) {
			$this->transactionalCallableUpdate->markAsPending();
		}

		$this->transactionalCallableUpdate->setFingerprint(
			$this->fingerprint
		);

		$this->transactionalCallableUpdate->setOrigin( [
			__METHOD__,
			$this->origin
		] );

		$this->transactionalCallableUpdate->pushUpdate();
	}

	/**
	 * @since 3.0
	 */
	public function doUpdate() {
		$this->isPending = false;
		$this->onTransactionIdle = false;

		foreach ( array_keys( $this->pendingUpdates ) as $update ) {
			call_user_func( [ $this, $update ] );
		}

		$this->pendingUpdates = [];
	}

	/**
	 * @since 2.1
	 */
	public function doPurgeParserCache() {

		$method = __METHOD__;

		if ( $this->isPending || $this->onTransactionIdle ) {
			return $this->pendingUpdates['doPurgeParserCache'] = true;
		}

		foreach ( $this->titles as $title ) {
			$title->invalidateCache();
		}
	}

	/**
	 * @since 2.1
	 */
	public function doPurgeHtmlCache() {

		if ( $this->isHtmlCacheUpdate === false ) {
			return;
		}

		if ( $this->isPending || $this->onTransactionIdle ) {
			return $this->pendingUpdates['doPurgeHtmlCache'] = true;
		}

		$method = __METHOD__;

		// Calls HTMLCacheUpdate, HTMLCacheUpdateJob including HTMLFileCache,
		// CdnCacheUpdate
		foreach ( $this->titles as $title ) {
			$title->touchLinks();
		}
	}

	/**
	 * @since 2.1
	 */
	public function doPurgeWebCache() {

		$method = __METHOD__;

		if ( $this->isPending || $this->onTransactionIdle ) {
			return $this->pendingUpdates['doPurgeWebCache'] = true;
		}

		foreach ( $this->titles as $title ) {
			$title->purgeSquid();
		}
	}

	/**
	 * Copied from PurgeJobUtils to avoid the AutoCommitUpdate from
	 * Title::invalidateCache introduced with MW 1.28/1.29 on a large update pool
	 */
	private function doPoolPurge() {

		Timer::start( __METHOD__ );

		// #3413
		$byNamespace = [];

		foreach ( $this->titles as $title ) {
			$namespace = $title->getNamespace();
			$pagename = $title->getDBkey();
			$byNamespace[$namespace][] = $pagename;
		}

		$conds = [];

		foreach ( $byNamespace as $namespaces => $pagenames ) {

			$cond = [
				'page_namespace' => $namespaces,
				'page_title' => $pagenames,
			];

			$conds[] = $this->connection->makeList( $cond, LIST_AND );
		}

		$titleConds = $this->connection->makeList( $conds, LIST_OR );

		// Required due to postgres and "Error: 22007 ERROR:  invalid input
		// syntax for type timestamp with time zone: "20170408113703""
		$now = $this->connection->timestamp();
		$res = $this->connection->select(
			'page',
			'page_id',
			[
				$titleConds,
				'page_touched < ' . $this->connection->addQuotes( $now )
			],
			__METHOD__
		);

		if ( $res === false ) {
			return;
		}

		$ids = [];

		foreach ( $res as $row ) {
			$ids[] = $row->page_id;
		}

		if ( $ids === [] ) {
			return;
		}

		$this->connection->update(
			'page',
			[ 'page_touched' => $now ],
			[
				'page_id' => $ids,
				'page_touched < ' . $this->connection->addQuotes( $now )
			],
			__METHOD__
		);

		$context = [
			'method' => __METHOD__,
			'procTime' => Timer::getElapsedTime( __METHOD__, 7 ),
			'role' => 'developer'
		];

		$this->logger->info( 'Page update, pool update (procTime in sec: {procTime})', $context );
	}

}
