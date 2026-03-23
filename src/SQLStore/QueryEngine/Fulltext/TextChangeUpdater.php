<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use Onoi\Cache\Cache;
use Psr\Log\LoggerAwareTrait;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\ChangeOp\ChangeDiff;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\ChangeOp\TableChangeOp;
use SMW\Utils\Timer;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class TextChangeUpdater {

	use LoggerAwareTrait;

	/**
	 * @var bool
	 */
	private $asDeferredUpdate = true;

	/**
	 * @var bool
	 */
	private $isCommandLineMode = false;

	/**
	 * @var bool
	 */
	private $isPrimary = false;

	/**
	 * @since 2.5
	 */
	public function __construct(
		private Database $connection,
		private Cache $cache,
		private SearchTableUpdater $searchTableUpdater,
	) {
	}

	/**
	 * @note See comments in src/DefaultSettings.php on the smwgFulltextDeferredUpdate setting
	 *
	 * @since 2.5
	 *
	 * @param bool $asDeferredUpdate
	 */
	public function asDeferredUpdate( $asDeferredUpdate ): void {
		$this->asDeferredUpdate = (bool)$asDeferredUpdate;
	}

	/**
	 * When running from commandLine, push updates directly to avoid overhead when
	 * it is known that within that mode transactions are FIFO (i.e. the likelihood
	 * for race conditions of unfinished updates are diminishable).
	 *
	 * @since 2.5
	 *
	 * @param bool $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ): void {
		$this->isCommandLineMode = (bool)$isCommandLineMode;
	}

	/**
	 * @since 3.0
	 *
	 * @param bool $isPrimary
	 */
	public function isPrimary( $isPrimary ): void {
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
	 * @param array|bool $parameters
	 */
	public function pushUpdatesFromJobParameters( $parameters ) {
		if ( !$this->searchTableUpdater->isEnabled() || !isset( $parameters['slot:id'] ) || $parameters['slot:id'] === false ) {
			return;
		}

		$subject = WikiPage::doUnserialize( $parameters['slot:id'] );
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
	 * @param ChangeDiff|null $changeDiff
	 */
	public function doUpdateFromChangeDiff( ?ChangeDiff $changeDiff = null ): void {
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

			$this->collectUpdates( $sid, $textItem, $updates );
		}

		foreach ( $updates as $key => $value ) {
			[ $sid, $pid ] = explode( ':', $key, 2 );

			if ( !$this->searchTableUpdater->exists( $sid, $pid ) ) {
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

	private function collectUpdates( int|string $sid, array $textItem, &$updates ): void {
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

	private function doDeleteFromTableChangeOps( array $tableChangeOps ): void {
		foreach ( $tableChangeOps as $tableChangeOp ) {
			$this->doDeleteFromTableChangeOp( $tableChangeOp );
		}
	}

	private function doDeleteFromTableChangeOp( TableChangeOp $tableChangeOp ): void {
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

	private function canPostUpdate( ChangeOp $changeOp ) {
		$searchTable = $this->searchTableUpdater->getSearchTable();
		$canPostUpdate = false;

		// Find out whether we should actual initiate an update
		foreach ( $changeOp->getChangedEntityIdSummaryList() as $id ) {
			if ( ( $dataItem = $searchTable->getDataItemById( $id ) ) instanceof WikiPage && $dataItem->getNamespace() === SMW_NS_PROPERTY ) {
				if ( !$searchTable->isExemptedPropertyById( $id ) ) {
					$canPostUpdate = true;
					break;
				}
			}
		}

		return $canPostUpdate;
	}

}
