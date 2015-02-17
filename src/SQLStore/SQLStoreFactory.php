<?php

namespace SMW\SQLStore;

use Doctrine\DBAL\Connection;
use SMW\SQLStore\QueryEngine\ConceptCache;
use SMWSQLStore3;
use SMWSQLStore3QueryEngine;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SQLStoreFactory {

	/**
	 * @var SMWSQLStore3
	 */
	private $store;

	/**
	 * @var Connection|null
	 */
	private $dbalConnection = null;

	public function __construct( SMWSQLStore3 $store ) {
		$this->store = $store;
	}

	public function newSalveQueryEngine() {
		return new SMWSQLStore3QueryEngine(
			$this->store,
			wfGetDB( DB_SLAVE ),
			$this->newTemporaryIdTableCreator()
		);
	}

	public function newMasterQueryEngine() {
		return new SMWSQLStore3QueryEngine(
			$this->store,
			wfGetDB( DB_SLAVE ),
			$this->newTemporaryIdTableCreator()
		);
	}

	private function newTemporaryIdTableCreator() {
		return new TemporaryIdTableCreator( $GLOBALS['wgDBtype'] );
	}

	public function newSlaveConceptCache() {
		return new ConceptCache(
			$this->newSalveQueryEngine(),
			$this->store
		);
	}

	/**
	 * @since 2.2
	 *
	 * @return UsageStatisticsListLookup
	 */
	public function newUsageStatisticsListLookup() {
		return new UsageStatisticsListLookup( $this->store );
	}

	private function getConnection() {
		if ( $this->dbalConnection === null ) {
			$builder = new ConnectionBuilder( $GLOBALS );
			$this->dbalConnection = $builder->newConnection();
		}

		return $this->dbalConnection;
	}

}
