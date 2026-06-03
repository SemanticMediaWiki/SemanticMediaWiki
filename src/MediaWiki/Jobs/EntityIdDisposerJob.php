<?php

namespace SMW\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use SMW\IteratorFactory;
use SMW\Iterators\ResultIterator;
use SMW\MediaWiki\Job;
use SMW\MediaWiki\JobFactory;
use SMW\RequestOptions;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\SQLStore\QueryDependency\QueryLinksTableDisposer;
use SMW\Store;
use stdClass;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class EntityIdDisposerJob extends Job {

	/**
	 * Commit chunk size
	 */
	const CHUNK_SIZE = 200;

	/**
	 * Defines the row size for the batching process and to be processed within
	 * a single job request.
	 */
	const BATCH_ROW_SIZE = 5000;

	private ?PropertyTableIdReferenceDisposer $propertyTableIdReferenceDisposer = null;

	private ?QueryLinksTableDisposer $queryLinksTableDisposer = null;

	/**
	 * @since 2.5
	 */
	public function __construct(
		Title $title,
		array $params,
		Store $store,
		private readonly IteratorFactory $iteratorFactory,
		private readonly JobFactory $jobFactory
	) {
		parent::__construct( 'smw.entityIdDisposer', $title, $params );
		$this->setStore( $store );
		$this->removeDuplicates = true;
	}

	/**
	 * @since 2.5
	 */
	public function newOutdatedEntitiesResultIterator( ?RequestOptions $requestOptions = null ): ResultIterator {
		if ( $this->propertyTableIdReferenceDisposer === null ) {
			$this->propertyTableIdReferenceDisposer = $this->newPropertyTableIdReferenceDisposer();
		}

		return $this->propertyTableIdReferenceDisposer->newOutdatedEntitiesResultIterator( $requestOptions );
	}

	/**
	 * @since 3.2
	 */
	public function newByNamespaceInvalidEntitiesResultIterator( ?RequestOptions $requestOptions = null ): ResultIterator {
		if ( $this->propertyTableIdReferenceDisposer === null ) {
			$this->propertyTableIdReferenceDisposer = $this->newPropertyTableIdReferenceDisposer();
		}

		return $this->propertyTableIdReferenceDisposer->newByNamespaceInvalidEntitiesResultIterator( $requestOptions );
	}

	/**
	 * @since 3.1
	 */
	public function newOutdatedQueryLinksResultIterator(): ResultIterator {
		if ( $this->queryLinksTableDisposer === null ) {
			$this->queryLinksTableDisposer = $this->newQueryLinksTableDisposer();
		}

		return $this->queryLinksTableDisposer->newOutdatedQueryLinksResultIterator();
	}

	/**
	 * @since 3.1
	 */
	public function newUnassignedQueryLinksResultIterator(): ResultIterator {
		if ( $this->queryLinksTableDisposer === null ) {
			$this->queryLinksTableDisposer = $this->newQueryLinksTableDisposer();
		}

		return $this->queryLinksTableDisposer->newUnassignedQueryLinksResultIterator();
	}

	/**
	 * @since 3.1
	 *
	 * @param int|stdClass $id
	 */
	public function disposeQueryLinks( $id ): void {
		if ( $this->queryLinksTableDisposer === null ) {
			$this->queryLinksTableDisposer = $this->newQueryLinksTableDisposer();
		}

		$this->queryLinksTableDisposer->cleanUpTableEntriesById( $id );
	}

	/**
	 * @since 2.5
	 *
	 * @param int|stdClass $id
	 */
	public function dispose( $id ) {
		if ( $this->propertyTableIdReferenceDisposer === null ) {
			$this->propertyTableIdReferenceDisposer = $this->newPropertyTableIdReferenceDisposer();
		}

		if ( is_int( $id ) ) {
			return $this->propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $id );
		}

		$this->propertyTableIdReferenceDisposer->cleanUpTableEntriesByRow( $id );
	}

	/**
	 * Batched counterpart to dispose(); resolves the disposer once and removes a
	 * whole chunk of ids in IN-list deletes.
	 *
	 * @since 7.0.0
	 *
	 * @param array $rows Array of stdClass rows (with smw_id) or ints
	 */
	public function disposeList( array $rows ): void {
		if ( $this->propertyTableIdReferenceDisposer === null ) {
			$this->propertyTableIdReferenceDisposer = $this->newPropertyTableIdReferenceDisposer();
		}

		$ids = [];

		foreach ( $rows as $row ) {
			$ids[] = is_int( $row ) ? $row : (int)$row->smw_id;
		}

		$this->propertyTableIdReferenceDisposer->cleanUpTableEntriesByIdList( $ids );
	}

	/**
	 * @see Job::run
	 *
	 * @since 2.5
	 */
	public function run(): bool {
		if ( $this->hasParameter( 'id' ) ) {
			$this->dispose( $this->getParameter( 'id' ) );
		} else {
			$this->disposeOutdatedEntities();
		}

		return true;
	}

	private function disposeOutdatedEntities(): ?bool {
		// Make sure the script is only executed from the command line to avoid
		// Special:RunJobs to execute a queued job
		if ( $this->waitOnCommandLineMode() ) {
			return true;
		}

		$requestOptions = new RequestOptions();
		$requestOptions->setLimit( self::BATCH_ROW_SIZE );

		$outdatedEntitiesResultIterator = $this->newOutdatedEntitiesResultIterator(
			$requestOptions
		);

		$count = $outdatedEntitiesResultIterator->count();
		$cycle = $this->hasParameter( 'cycle' ) ? (int)$this->getParameter( 'cycle' ) : 0;

		if ( $count == 0 ) {
			return null;
		}

		// We expect more outdated entities to be contained in the ID_TABLE,
		// therefore reenter the disposal cycle.
		$entityIdDisposerJob = $this->jobFactory->newEntityIdDisposerJob(
			$this->getTitle(),
			$this->params + [ 'cycle' => ++$cycle ]
		);

		$entityIdDisposerJob->insert();

		$connection = $this->store->getConnection( 'mw.db' );

		$chunkedIterator = $this->iteratorFactory->newChunkedIterator(
			$outdatedEntitiesResultIterator,
			self::CHUNK_SIZE
		);

		foreach ( $chunkedIterator as $chunk ) {

			$transactionTicket = $connection->getEmptyTransactionTicket( __METHOD__ );

			$this->disposeList( $chunk );

			$connection->commitAndWaitForReplication( __METHOD__, $transactionTicket );
		}

		return null;
	}

	private function newPropertyTableIdReferenceDisposer(): PropertyTableIdReferenceDisposer {
		return $this->store->service( 'PropertyTableIdReferenceDisposer' );
	}

	private function newQueryLinksTableDisposer(): QueryLinksTableDisposer {
		$queryDependencyLinksStoreFactory = $this->store->service( 'QueryDependencyLinksStoreFactory' );

		return $queryDependencyLinksStoreFactory->newQueryLinksTableDisposer( $this->store );
	}

}
