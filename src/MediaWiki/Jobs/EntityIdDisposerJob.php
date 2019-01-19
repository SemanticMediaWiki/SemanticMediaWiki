<?php

namespace SMW\MediaWiki\Jobs;

use SMW\MediaWiki\Job;
use Hooks;
use SMW\ApplicationFactory;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\SQLStore\SQLStore;
use Title;

/**
 * @license GNU GPL v2+
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
	 * @var PropertyTableIdReferenceDisposer
	 */
	private $propertyTableIdReferenceDisposer;

	/**
	 * @var QueryLinksTableDisposer
	 */
	private $queryLinksTableDisposer;

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = [] ) {
		parent::__construct( 'smw.entityIdDisposer', $title, $params );
		$this->removeDuplicates = true;
	}

	/**
	 * @since 2.5
	 *
	 * @return ResultIterator
	 */
	public function newOutdatedEntitiesResultIterator() {

		if ( $this->propertyTableIdReferenceDisposer === null ) {
			$this->propertyTableIdReferenceDisposer = $this->newPropertyTableIdReferenceDisposer();
		}

		return $this->propertyTableIdReferenceDisposer->newOutdatedEntitiesResultIterator();
	}

	/**
	 * @since 3.1
	 *
	 * @return ResultIterator
	 */
	public function newOutdatedQueryLinksResultIterator() {

		if ( $this->queryLinksTableDisposer === null ) {
			$this->queryLinksTableDisposer = $this->newQueryLinksTableDisposer();
		}

		return $this->queryLinksTableDisposer->newOutdatedQueryLinksResultIterator();
	}

	/**
	 * @since 3.1
	 *
	 * @return ResultIterator
	 */
	public function newUnassignedQueryLinksResultIterator() {

		if ( $this->queryLinksTableDisposer === null ) {
			$this->queryLinksTableDisposer = $this->newQueryLinksTableDisposer();
		}

		return $this->queryLinksTableDisposer->newUnassignedQueryLinksResultIterator();
	}

	/**
	 * @since 3.1
	 *
	 * @param integer|stdClass $id
	 */
	public function disposeQueryLinks( $id ) {

		if ( $this->queryLinksTableDisposer === null ) {
			$this->queryLinksTableDisposer = $this->newQueryLinksTableDisposer();
		}

		$this->queryLinksTableDisposer->cleanUpTableEntriesById( $id );
	}

	/**
	 * @since 2.5
	 *
	 * @param integer|stdClass $id
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
	 * @see Job::run
	 *
	 * @since 2.5
	 */
	public function run() {

		if ( $this->hasParameter( 'id' ) ) {
			$this->dispose( $this->getParameter( 'id' ) );
		} else {
			$this->dispose_all( $this->newOutdatedEntitiesResultIterator() );
		}

		return true;
	}

	private function dispose_all( $outdatedEntitiesResultIterator ) {

		// Make sure the script is only executed from the command line to avoid
		// Special:RunJobs to execute a queued job
		if ( $this->waitOnCommandLineMode() ) {
			return true;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$connection = $applicationFactory->getStore()->getConnection( 'mw.db' );

		$chunkedIterator = $applicationFactory->getIteratorFactory()->newChunkedIterator(
			$outdatedEntitiesResultIterator,
			self::CHUNK_SIZE
		);

		foreach ( $chunkedIterator as $chunk ) {

			$transactionTicket = $connection->getEmptyTransactionTicket( __METHOD__ );

			foreach ( $chunk as $row ) {
				$this->dispose( $row );
			}

			$connection->commitAndWaitForReplication( __METHOD__, $transactionTicket );
		}
	}

	private function newPropertyTableIdReferenceDisposer() {
		return ApplicationFactory::getInstance()->getStore()->service( 'PropertyTableIdReferenceDisposer' );
	}

	private function newQueryLinksTableDisposer() {

		$store = ApplicationFactory::getInstance()->getStore();
		$queryDependencyLinksStoreFactory = $store->service( 'QueryDependencyLinksStoreFactory' );

		return $queryDependencyLinksStoreFactory->newQueryLinksTableDisposer( $store );
	}

}
