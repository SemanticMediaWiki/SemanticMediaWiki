<?php

namespace SMW\SQLStore;

use Onoi\Cache\Cache;
use SMW\HashBuilder;
use SMW\InMemoryPoolCache;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\SQLStore\TableBuilder\FieldType;
use SMW\Store;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class RedirectStore {

	const TABLE_NAME = 'smw_fpt_redi';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var boolean
	 */
	private $hasEqualitySupport = false;

	/**
	 * @since 2.1
	 *
	 * @param Store $store
	 * @param Cache|null $cache
	 */
	public function __construct( Store $store, Cache $cache = null ) {
		$this->store = $store;
		$this->cache = $cache;

		if ( $this->cache === null ) {
			$this->cache = InMemoryPoolCache::getInstance()->getPoolCacheById( 'sql.store.redirect.infostore' );
		}

		$this->setEqualitySupportFlag( $GLOBALS['smwgQEqualitySupport'] );
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $equalitySupport
	 */
	public function setEqualitySupportFlag( $equalitySupport ) {
		$this->hasEqualitySupport = $equalitySupport != SMW_EQ_NONE;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $title DB key
	 * @param integer $namespace
	 *
	 * @return boolean
	 */
	public function isRedirect( $title, $namespace ) {
		return $this->findRedirect( $title, $namespace ) != 0;
	}

	/**
	 * Returns an id for a redirect if no redirect is found 0 is returned
	 *
	 * @since 2.1
	 *
	 * @param string $title DB key
	 * @param integer $namespace
	 *
	 * @return integer
	 */
	public function findRedirect( $title, $namespace ) {

		$hash = $this->makeHash(
			$title,
			$namespace
		);

		if ( $this->cache->contains( $hash ) ) {
			return $this->cache->fetch( $hash );
		}

		$id = $this->select( $title, $namespace );

		$this->cache->save( $hash, $id );

		return $id;
	}

	/**
	 * @since 2.1
	 *
	 * @param integer $id
	 * @param string $title
	 * @param integer $namespace
	 */
	public function addRedirect( $id, $title, $namespace ) {

		$this->insert( $id, $title, $namespace );

		$hash = $this->makeHash(
			$title,
			$namespace
		);

		$this->cache->save( $hash, $id );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $id
	 * @param string $title
	 * @param integer $namespace
	 */
	public function updateRedirect( $id, $title, $namespace ) {

		$this->deleteRedirect( $title, $namespace );

		if ( !$this->canCreateUpdateJobs() || !$this->hasEqualitySupport ) {
			return;
		}

		// Entries that refer to old target may in fact refer to subject,
		// but we don't know which: schedule affected pages for update
		$propertyTables = $this->store->getPropertyTables();
		$connection = $this->store->getConnection( 'mw.db' );
		$jobs = [];

		foreach ( $propertyTables as $proptable ) {

			 // Can be skipped safely
			if ( $proptable->getName() == self::TABLE_NAME ) {
				continue;
			}

			$query = [
				'from' => '',
				'fields' => ''
			];

			$query['condition'] = [ 'p_id' => $id ];

			if ( $proptable->usesIdSubject() ) {
				$query['from'] .= $connection->tableName( $proptable->getName() );
				$query['from'] .= ' INNER JOIN ';
				$query['from'] .= $connection->tableName( SQLStore::ID_TABLE ) . ' ON s_id=smw_id';
				$query['fields'] = 'DISTINCT smw_title AS t,smw_namespace AS ns';
			} else {
				$query['from'] = $connection->tableName( $proptable->getName() );
				$query['fields'] = 'DISTINCT s_title AS t,s_namespace AS ns';
			}

			if ( $namespace === SMW_NS_PROPERTY && !$proptable->isFixedPropertyTable() ) {
				$this->findUpdateJobs( $connection, $query, $jobs );
			}

			foreach ( $proptable->getFields( $this->store ) as $fieldName => $fieldType ) {

				if ( $fieldType !== FieldType::FIELD_ID ) {
					continue;
				}

				$query['condition'] = [ $fieldName => $id ];
				$this->findUpdateJobs( $connection, $query, $jobs );
			}
		}

		foreach ( $jobs as $job ) {
			$job->insert();
		}
	}

	/**
	 * @since 2.1
	 *
	 * @param string $title
	 * @param integer $namespace
	 */
	public function deleteRedirect( $title, $namespace ) {

		$this->delete( $title, $namespace );

		$hash = $this->makeHash(
			$title,
			$namespace
		);

		$this->cache->delete( $hash );
	}

	private function select( $title, $namespace ) {

		$connection = $this->store->getConnection( 'mw.db' );

		$row = $connection->selectRow(
			self::TABLE_NAME,
			'o_id',
			[
				's_title' => $title,
				's_namespace' => $namespace
			],
			__METHOD__
		);

		return $row !== false && isset( $row->o_id ) ? (int)$row->o_id : 0;
	}

	private function insert( $id, $title, $namespace ) {

		$connection = $this->store->getConnection( 'mw.db' );

		$connection->insert(
			self::TABLE_NAME,
			[
				's_title' => $title,
				's_namespace' => $namespace,
				'o_id' => $id ],
			__METHOD__
		);
	}

	private function delete( $title, $namespace ) {

		$connection = $this->store->getConnection( 'mw.db' );

		$connection->delete(
			self::TABLE_NAME,
			[
				's_title' => $title,
				's_namespace' => $namespace ],
			__METHOD__
		);
	}

	private function canCreateUpdateJobs() {
		return $this->store->getOption( Store::OPT_CREATE_UPDATE_JOB, true ) && $this->store->getOption( 'smwgEnableUpdateJobs' );
	}

	private function findUpdateJobs( $connection, $query, &$jobs ) {

		$res = $connection->select(
			$query['from'],
			$query['fields'],
			$query['condition'],
			__METHOD__
		);

		foreach ( $res as $row ) {
			$title = Title::makeTitleSafe( $row->ns, $row->t );

			if ( $title !== null ) {
				$jobs[] = new UpdateJob( $title );
			}
		}

		$connection->freeResult( $res );
	}

	private function makeHash( $title, $namespace ) {
		return "$title#$namespace";
	}

}
