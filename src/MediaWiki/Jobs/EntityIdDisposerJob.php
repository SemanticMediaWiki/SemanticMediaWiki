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
		return $this->newPropertyTableIdReferenceDisposer()->newOutdatedEntitiesResultIterator();
	}

	/**
	 * @since 2.5
	 *
	 * @param integer|stdClass $id
	 */
	public function dispose( $id ) {

		$propertyTableIdReferenceDisposer = $this->newPropertyTableIdReferenceDisposer();

		if ( is_int( $id ) ) {
			return $propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $id );
		}

		$propertyTableIdReferenceDisposer->cleanUpTableEntriesByRow( $id );
	}

	/**
	 * @see Job::run
	 *
	 * @since 2.5
	 */
	public function run() {

		$propertyTableIdReferenceDisposer = $this->newPropertyTableIdReferenceDisposer();

		// MW 1.29+ Avoid transaction collisions during Job execution
		$propertyTableIdReferenceDisposer->waitOnTransactionIdle();

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

		$applicationFactory = ApplicationFactory::getInstance();
		$store = $applicationFactory->getStore();

		// Expect access to the SQL table structure therefore enforce the
		// SQLStore that provides those methods
		if ( !is_a( $store, SQLStore::class ) ) {
			$store = $applicationFactory->getStore( '\SMW\SQLStore\SQLStore' );
		}

		return new PropertyTableIdReferenceDisposer( $store );
	}

}
