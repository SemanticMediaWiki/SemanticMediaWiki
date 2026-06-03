<?php

namespace SMW\SQLStore;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use SMW\DataItems\WikiPage;
use SMW\EventDispatcher\EventDispatcher;
use SMW\Iterators\ResultIterator;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Connection\LegacyOptionsApplier;
use SMW\RequestOptions;
use stdClass;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @private
 *
 * Class responsible for the clean-up (aka disposal) of any outdated table entries
 * that are contained in either the ID_TABLE or related property tables with
 * reference to a matchable ID.
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyTableIdReferenceDisposer {

	/**
	 * RequestOptions key holding the total number of modulo shards (N) for the
	 * outdated-entity selection. When greater than 1, the selection is
	 * restricted to a single shard.
	 */
	const OPT_SHARD_OF = 'shard.of';

	/**
	 * RequestOptions key holding this shard's index (k) within the modulo
	 * sharding scheme (`smw_id % N = k`).
	 */
	const OPT_SHARD_INDEX = 'shard.index';

	/**
	 * @var Database
	 */
	private $connection = null;

	private bool $onTransactionIdle = false;

	/**
	 * @var bool
	 */
	private $redirectRemoval = false;

	private bool $fulltextTableUsage = false;

	private array $namespacesWithSemanticLinks = [];

	/**
	 * @since 2.4
	 */
	public function __construct(
		private SQLStore $store,
		private EventDispatcher $eventDispatcher
	) {
		$this->connection = $this->store->getConnection( 'mw.db' );
	}

	/**
	 * @since 3.0
	 *
	 * @param bool $redirectRemoval
	 */
	public function setRedirectRemoval( $redirectRemoval ): void {
		$this->redirectRemoval = $redirectRemoval;
	}

	/**
	 * @since 3.2
	 *
	 * @param bool $fulltextTableUsage
	 */
	public function setFulltextTableUsage( bool $fulltextTableUsage ): void {
		$this->fulltextTableUsage = $fulltextTableUsage;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $namespacesWithSemanticLinks
	 */
	public function setNamespacesWithSemanticLinks( array $namespacesWithSemanticLinks ): void {
		$this->namespacesWithSemanticLinks = $namespacesWithSemanticLinks;
	}

	/**
	 * @since 2.5
	 */
	public function waitOnTransactionIdle(): void {
		$this->onTransactionIdle = true;
	}

	/**
	 * @since 3.0
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public function isDisposable( $id ): bool {
		return !$this->store->getPropertyTableIdReferenceFinder()->hasResidualReferenceForId( $id );
	}

	/**
	 * Use case: After a property changed its type (_wpg -> _txt), object values in the
	 * ID table are not removed at the time of the conversion process.
	 *
	 * Before an attempt to remove the ID from entity tables, it is secured that no
	 * references exists for the ID.
	 *
	 * @note This method does not check for an ID being object or subject value
	 * and has to be done prior calling this routine.
	 *
	 * @since 2.4
	 *
	 * @param int $id
	 */
	public function removeOutdatedEntityReferencesById( $id ): void {
		if ( $this->store->getPropertyTableIdReferenceFinder()->hasResidualReferenceForId( $id ) ) {
			return;
		}

		$this->cleanUpSecondaryReferencesById( $id, false );
	}

	/**
	 * @since 2.5
	 *
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return ResultIterator
	 */
	public function newOutdatedEntitiesResultIterator( ?RequestOptions $requestOptions = null ): ResultIterator {
		$options = [];

		if ( $requestOptions !== null ) {
			if ( $requestOptions->getLimit() > 0 ) {
				$options['LIMIT'] = $requestOptions->getLimit();
			}

			if ( $requestOptions->getOffset() > 0 ) {
				$options['OFFSET'] = $requestOptions->getOffset();
			}
		}

		$qb = $this->connection->newSelectQueryBuilder()
			->select( [ 'smw_id' ] )
			->from( SQLStore::ID_TABLE )
			->where( [ 'smw_iw' => SMW_SQL3_SMWDELETEIW ] );

		$this->applyShard( $qb, $requestOptions );

		LegacyOptionsApplier::applyTo( $qb, $options );

		return new ResultIterator( $qb->caller( __METHOD__ )->fetchResultSet() );
	}

	/**
	 * @since 3.2
	 *
	 * @param RequestOptions|null $requestOptions
	 *
	 * @return ResultIterator
	 */
	public function newByNamespaceInvalidEntitiesResultIterator( ?RequestOptions $requestOptions = null ): ResultIterator {
		$options = [];

		if ( $requestOptions !== null ) {
			if ( $requestOptions->getLimit() > 0 ) {
				$options['LIMIT'] = $requestOptions->getLimit();
			}

			if ( $requestOptions->getOffset() > 0 ) {
				$options['OFFSET'] = $requestOptions->getOffset();
			}
		}

		$qb = $this->connection->newSelectQueryBuilder()
			->select( [ 'smw_id' ] )
			->from( SQLStore::ID_TABLE )
			->where( 'smw_namespace NOT IN (' . $this->connection->makeList( array_keys( $this->namespacesWithSemanticLinks ) ) . ')' );

		$this->applyShard( $qb, $requestOptions );

		LegacyOptionsApplier::applyTo( $qb, $options );

		return new ResultIterator( $qb->caller( __METHOD__ )->fetchResultSet() );
	}

	/**
	 * Restrict the selection to a single modulo shard (`smw_id % N = k`) when
	 * shard options are present on the request, letting an operator run N
	 * disjoint disposal processes. The unsharded path is left untouched.
	 */
	private function applyShard( SelectQueryBuilder $qb, ?RequestOptions $requestOptions ): void {
		if ( $requestOptions === null ) {
			return;
		}

		$of = (int)$requestOptions->getOption( self::OPT_SHARD_OF, 1 );
		$shard = (int)$requestOptions->getOption( self::OPT_SHARD_INDEX, 0 );

		if ( $of > 1 ) {
			$qb->andWhere( 'smw_id % ' . $of . ' = ' . $shard );
		}
	}

	/**
	 * @since 2.5
	 *
	 * @param stdClass $row
	 */
	public function cleanUpTableEntriesByRow( $row ): void {
		if ( !isset( $row->smw_id ) ) {
			return;
		}

		$this->cleanUpTableEntriesById( $row->smw_id );
	}

	/**
	 * @note This method does not make any assumption about the ID state and therefore
	 * has to be validated before this method is called.
	 *
	 * @since 2.4
	 *
	 * @param int $id
	 */
	public function cleanUpTableEntriesById( $id ) {
		$this->cleanUpTableEntriesByIdList( [ $id ] );
	}

	/**
	 * Batched disposal: one IN-list DELETE per (table, column) for the whole id
	 * list, inside a single atomic transaction. Per-id cache-invalidation events
	 * and the per-id completion hook are preserved.
	 *
	 * @since 7.0.0
	 *
	 * @param int[] $ids
	 */
	public function cleanUpTableEntriesByIdList( array $ids ): void {
		$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );

		if ( $ids === [] ) {
			return;
		}

		if ( $this->onTransactionIdle ) {
			$this->connection->onTransactionCommitOrIdle( function () use ( $ids ): void {
				$this->cleanUpReferencesByIdList( $ids );
			} );
			return;
		}

		$this->cleanUpReferencesByIdList( $ids );
	}

	private function cleanUpReferencesByIdList( array $ids ): void {
		$subjects = [];
		$isRedirect = [];
		$nonRedirectIds = [];

		foreach ( $ids as $id ) {
			$subject = $this->store->getObjectIds()->getDataItemById( $id );
			$redirect = false;

			if ( $subject instanceof WikiPage ) {
				$redirect = $subject->getInterwiki() === SMW_SQL3_SMWREDIIW;

				// Use the subject without an internal 'smw-delete' iw marker
				$subject = new WikiPage(
					$subject->getDBKey(),
					$subject->getNamespace(),
					'',
					$subject->getSubobjectName()
				);
			}

			$subjects[$id] = $subject;
			$isRedirect[$id] = $redirect;

			if ( !$redirect || $this->redirectRemoval ) {
				$nonRedirectIds[] = $id;
			}

			$this->triggerCleanUpEvents( $subject );
		}

		$this->connection->beginAtomicTransaction( __METHOD__ );

		foreach ( $this->store->getPropertyTables() as $proptable ) {
			if ( $proptable->usesIdSubject() ) {
				$this->deleteByIdList( $proptable->getName(), 's_id', $ids );
			}

			if ( !$proptable->isFixedPropertyTable() ) {
				$this->deleteByIdList( $proptable->getName(), 'p_id', $ids );
			}

			$fields = $proptable->getFields( $this->store );

			// Match tables (including ftp_redi) that contain an object reference
			if ( isset( $fields['o_id'] ) ) {
				$this->deleteByIdList( $proptable->getName(), 'o_id', $ids );
			}
		}

		$this->cleanUpSecondaryReferencesByIdList( $ids, $nonRedirectIds );
		$this->connection->endAtomicTransaction( __METHOD__ );

		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();

		foreach ( $ids as $id ) {
			$hookContainer->run(
				'SMW::SQLStore::EntityReferenceCleanUpComplete',
				[ $this->store, $id, $subjects[$id], $isRedirect[$id] ]
			);
		}
	}

	private function deleteByIdList( string $table, string $field, array $ids ): void {
		if ( $ids === [] ) {
			return;
		}

		$this->connection->newDeleteQueryBuilder()
			->deleteFrom( $table )
			->where( [ $field => $ids ] )
			->caller( __METHOD__ )
			->execute();
	}

	private function cleanUpSecondaryReferencesById( $id, bool $isRedirect ): void {
		$nonRedirectIds = ( !$isRedirect || $this->redirectRemoval ) ? [ (int)$id ] : [];
		$this->cleanUpSecondaryReferencesByIdList( [ (int)$id ], $nonRedirectIds );
	}

	private function cleanUpSecondaryReferencesByIdList( array $ids, array $nonRedirectIds ): void {
		// When marked as redirect, don't remove the reference (nonRedirectIds omits it)
		$this->deleteByIdList( SQLStore::ID_TABLE, 'smw_id', $nonRedirectIds );
		$this->deleteByIdList( SQLStore::ID_AUXILIARY_TABLE, 'smw_id', $ids );
		$this->deleteByIdList( SQLStore::PROPERTY_STATISTICS_TABLE, 'p_id', $ids );
		$this->deleteByIdList( SQLStore::QUERY_LINKS_TABLE, 's_id', $ids );
		$this->deleteByIdList( SQLStore::QUERY_LINKS_TABLE, 'o_id', $ids );

		$tableExists = false;

		// Avoid Query: DELETE FROM `smw_ft_search` WHERE s_id = '92575'
		// Error: 126 Incorrect key file for table '.\mw@002d25@002d01\smw_ft_search.MYI'; ...
		try {
			if ( $this->fulltextTableUsage ) {
				$tableExists = $this->connection->tableExists( SQLStore::FT_SEARCH_TABLE, __METHOD__ );
			}
		} catch ( DBError $e ) {
			LoggerFactory::getInstance( 'smw' )->info( __METHOD__ . ' reported: ' . $e->getMessage() );
		}

		if ( $tableExists ) {
			$this->deleteByIdList( SQLStore::FT_SEARCH_TABLE, 's_id', $ids );
		}
	}

	private function triggerCleanUpEvents( $subject ): void {
		if ( !$subject instanceof WikiPage ) {
			return;
		}

		// Skip any reset for subobjects where it is expected that the base
		// subject is cleaning up all related cache entries
		if ( $subject->getSubobjectName() !== '' ) {
			return;
		}

		$context = [
			'context' => 'PropertyTableIdReferenceDisposal',
			'title' => $subject->getTitle(),
			'subject' => $subject
		];

		$this->eventDispatcher->dispatch( 'InvalidateResultCache', $context );
		$this->eventDispatcher->dispatch( 'InvalidateEntityCache', $context );
	}

}
