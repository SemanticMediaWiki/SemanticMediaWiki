<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use Onoi\Cache\Cache;
use Psr\Log\LoggerAwareTrait;
use SMW\MediaWiki\Database;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\SQLStore\ChangeOp\ChangeDiff;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\ChangeOp\TableChangeOp;
use SMW\Utils\Timer;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TextChangeUpdater {

	use LoggerAwareTrait;

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var SearchTableUpdater
	 */
	private $searchTableUpdater;

	/**
	 * @var boolean
	 */
	private $asDeferredUpdate = true;

	/**
	 * @var boolean
	 */
	private $isCommandLineMode = false;

	/**
	 * @var boolean
	 */
	private $isPrimary = false;

	/**
	 * @since 2.5
	 *
	 * @param Database $connection
	 * @param Cache $cache
	 * @param SearchTableUpdater $searchTableUpdater
	 * @param TextSanitizer $textSanitizer
	 */
	public function __construct( Database $connection, Cache $cache, SearchTableUpdater $searchTableUpdater ) {
		$this->connection = $connection;
		$this->cache = $cache;
		$this->searchTableUpdater = $searchTableUpdater;
	}

	/**
	 * @note See comments in the DefaultSettings.php on the smwgFulltextDeferredUpdate setting
	 *
	 * @since 2.5
	 *
	 * @param boolean $asDeferredUpdate
	 */
	public function asDeferredUpdate( $asDeferredUpdate ) {
		$this->asDeferredUpdate = (bool)$asDeferredUpdate;
	}

	/**
	 * When running from commandLine, push updates directly to avoid overhead when
	 * it is known that within that mode transactions are FIFO (i.e. the likelihood
	 * for race conditions of unfinished updates are diminishable).
	 *
	 * @since 2.5
	 *
	 * @param boolean $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ) {
		$this->isCommandLineMode = (bool)$isCommandLineMode;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isPrimary
	 */
	public function isPrimary( $isPrimary ) {
		$this->isPrimary = $isPrimary;
	}

	/**
	 * @see SMW::SQLStore::AfterDataUpdateComplete hook
	 *
	 * @since 2.5
	 *
	 * @param ChangeOp $changeOp
	 */
	public function pushUpdates( ChangeOp $changeOp ) {

		if ( !$this->searchTableUpdater->isEnabled() ) {
			return;
		}

		Timer::start( __METHOD__ );

		// Update within the same transaction as started by SMW::SQLStore::AfterDataUpdateComplete
		if ( !$this->asDeferredUpdate || $this->isCommandLineMode || $this->isPrimary ) {
			return $this->doUpdateFromChangeDiff( $changeOp->newChangeDiff() );
		}

		if ( !$this->canPostUpdate( $changeOp ) ) {
			return;
		}

		$fulltextSearchTableUpdateJob = ApplicationFactory::getInstance()->newJobFactory()->newFulltextSearchTableUpdateJob(
			$changeOp->getSubject()->getTitle(),
			[
				'slot:id' => $changeOp->getSubject()->getHash()
			]
		);

		$fulltextSearchTableUpdateJob->lazyPush();

		$this->logger->info(
			[
				'Fulltext',
				'TextChangeUpdater',
				'Table update (as job) scheduled',
				'procTime in sec: {procTime}'
			],
			[
				'method' => __METHOD__,
				'role' => 'developer',
				'procTime' => Timer::getElapsedTime( __METHOD__, 5 )
			]
		);
	}

	/**
	 * @see SearchTableUpdateJob::run
	 *
	 * @since 2.5
	 *
	 * @param array|boolan $parameters
	 */
	public function pushUpdatesFromJobParameters( $parameters ) {

		if ( !$this->searchTableUpdater->isEnabled() || !isset( $parameters['slot:id'] ) || $parameters['slot:id'] === false ) {
			return;
		}

		$subject = DIWikiPage::doUnserialize( $parameters['slot:id'] );
		$changeDiff = ChangeDiff::fetch( $this->cache, $subject );

		if ( $changeDiff !== false ) {
			return $this->doUpdateFromChangeDiff( $changeDiff );
		}

		$this->logger->info(
			[
				'Fulltext',
				'TextChangeUpdater',
				'Failed update (ChangeDiff) on {id}'
			],
			[
				'method' => __METHOD__,
				'role' => 'developer',
				'id' => $parameters['slot:id']
			]
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param ChangeOp $changeOp
	 */
	public function doUpdateFromChangeDiff( ChangeDiff $changeDiff = null ) {

		if ( !$this->searchTableUpdater->isEnabled() || $changeDiff === null ) {
			return;
		}

		Timer::start( __METHOD__ );

		$textItems = $changeDiff->getTextItems();
		$diffChangeOps = $changeDiff->getTableChangeOps();

		$changeList = $changeDiff->getChangeListByType( 'insert' );
		$updates = [];

		// Ensure that any delete operation is being accounted for to avoid that
		// removed value annotation remain
		if ( $diffChangeOps !== [] ) {
			$this->doDeleteFromTableChangeOps( $diffChangeOps );
		}

		// Build a composite of replacements where a change occurred, this my
		// contain some false positives
		foreach ( $textItems as $sid => $textItem ) {

			if ( !isset( $changeList[$sid] ) ) {
				continue;
			}

			$this->collectUpdates( $sid, $textItem, $changeList, $updates );
		}

		foreach ( $updates as $key => $value ) {
			list( $sid, $pid ) = explode( ':', $key, 2 );

			if ( $this->searchTableUpdater->exists( $sid, $pid ) === false ) {
				$this->searchTableUpdater->insert( $sid, $pid );
			}

			$this->searchTableUpdater->update(
				$sid,
				$pid,
				$value
			);
		}

		$this->logger->info(
			[
				'Fulltext',
				'TextChangeUpdater',
				'Table update completed',
				'procTime in sec: {procTime}'
			],
			[
				'method' => __METHOD__,
				'role' => 'developer',
				'procTime' => Timer::getElapsedTime( __METHOD__, 5 )
			]
		);
	}

	private function collectUpdates( $sid, array $textItem, $changeList, &$updates ) {

		$searchTable = $this->searchTableUpdater->getSearchTable();

		foreach ( $textItem as $pid => $text ) {

			// Exempted property -> out
			if ( $searchTable->isExemptedPropertyById( $pid ) ) {
				continue;
			}

			$text = implode( ' ', $text );
			$key = $sid . ':' . $pid;

			$updates[$key] = !isset( $updates[$key] ) ? $text : $updates[$key] . ' ' . $text;
		}
	}

	private function doDeleteFromTableChangeOps( array $tableChangeOps ) {
		foreach ( $tableChangeOps as $tableChangeOp ) {
			$this->doDeleteFromTableChangeOp( $tableChangeOp );
		}
	}

	private function doDeleteFromTableChangeOp( TableChangeOp $tableChangeOp ) {

		foreach ( $tableChangeOp->getFieldChangeOps( 'delete' ) as $fieldChangeOp ) {

			// Replace s_id for subobjects etc. with the o_id
			if ( $tableChangeOp->isFixedPropertyOp() ) {
				$fieldChangeOp->set( 's_id', $fieldChangeOp->has( 'o_id' ) ? $fieldChangeOp->get( 'o_id' ) : $fieldChangeOp->get( 's_id' ) );
				$fieldChangeOp->set( 'p_id', $tableChangeOp->getFixedPropertyValueBy( 'p_id' ) );
			}

			if ( !$fieldChangeOp->has( 'p_id' ) ) {
				continue;
			}

			$this->searchTableUpdater->delete(
				$fieldChangeOp->get( 's_id' ),
				$fieldChangeOp->get( 'p_id' )
			);
		}
	}

	private function canPostUpdate( $changeOp ) {

		$searchTable = $this->searchTableUpdater->getSearchTable();
		$canPostUpdate = false;

		// Find out whether we should actual initiate an update
		foreach ( $changeOp->getChangedEntityIdSummaryList() as $id ) {
			if ( ( $dataItem = $searchTable->getDataItemById( $id ) ) instanceof DIWikiPage && $dataItem->getNamespace() === SMW_NS_PROPERTY ) {
				if ( !$searchTable->isExemptedPropertyById( $id ) ) {
					$canPostUpdate = true;
					break;
				}
			}
		}

		return $canPostUpdate;
	}

}
