<?php

namespace SMW\SQLStore\QueryDependency;

use Psr\Log\LoggerAwareTrait;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class DependencyLinksValidator {

	use LoggerAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var boolean
	 */
	private $checkDependencies = false;

	/**
	 * @var []
	 */
	private $checkedDependencies = [];

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $checkDependencies
	 */
	public function canCheckDependencies( $checkDependencies ) {
		$this->checkDependencies = (bool)$checkDependencies;
	}

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getCheckedDependencies() {
		return $this->checkedDependencies;
	}

	/**
	 * The question to be answered by this method is whether any embedded query
	 * for subject X contains dependencies (those entities that are part of a query
	 * either as result subject, property, condition, or printrequest) that were
	 * updated recently or not . "Updated recently" refers to those entities that
	 * have a more recent `smw_touched` than the query that holds the reference.
	 *
	 * Subject X
	 *   -> contains Query Y (last touched 12:00)
	 *      -> contains result subject Foo (last touched 12:05)
	 *         -> since Foo is "younger" it could contain new/altered assignments
	 *             -> presents the likelihood of being outdated therefore makes
	 *                it a (or contain) archaic dependency to X/Y
	 *
	 * @since 3.1
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return boolean
	 */
	public function hasArchaicDependencies( DIWikiPage $subject ) {

		$this->checkedDependencies = [];

		if ( $this->checkDependencies === false ) {
			return false;
		}

		$proptables = $this->store->getPropertyTables();
		$propertyTableInfoFetcher = $this->store->getPropertyTableInfoFetcher();

		$tableid = $propertyTableInfoFetcher->findTableIdForProperty(
			new DIProperty( '_ASK' )
		);

		if ( !isset( $proptables[$tableid] ) ) {
			return false;
		}

		$connection = $this->store->getConnection( 'mw.db' );

		// Find queries related to the subject, and its touched
		//
		// SELECT v.smw_id,v.smw_touched FROM "smw_fpt_ask"
		// INNER JOIN smw_object_ids AS p ON ((s_id=p.smw_id))
		// INNER JOIN smw_object_ids AS v ON ((o_id=v.smw_id))
		// WHERE p.smw_hash = 'xxx' AND (p.smw_iw!=':smw') AND (p.smw_iw!=':smw-delete')
		$id_table = $connection->tableName( SQLStore::ID_TABLE );

		$rows = $connection->select(
			[ $proptables[$tableid]->getName(), $id_table . ' AS p', $id_table . ' AS v' ],
			[
				'v.smw_id', 'v.smw_subobject', 'v.smw_touched'
			],
			[
				'p.smw_hash' => $subject->getSha1(),
				'p.smw_iw!=' . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED ),
				'p.smw_iw!=' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW ),
			],
			__METHOD__,
			[
			//	'ORDER BY' => 'v.smw_touched'
			],
			[
				$id_table . ' AS p' => [ 'INNER JOIN', [ 's_id=p.smw_id' ] ],
				$id_table . ' AS v' => [ 'INNER JOIN', [ 'o_id=v.smw_id' ] ]
			]
		);

		$list = [];
		$touched = 0;

		// Find the latest touched and related IDs
		foreach ( $rows as $k => $row ) {

			if ( $row->smw_touched > $touched ) {
				$touched = $row->smw_touched;
			}

			$list[] = $row->smw_id;
			$this->checkedDependencies[] = $row->smw_subobject;
		}

		if ( $list === [] ) {
			return false;
		}

		// Check the links table for a list of entities associated with selected
		// queries for any reference object that has a more recent touched than
		// the query (meaning something changed an entity reference with the query
		// holding an outdated reference === containing an "older" view of the
		// data)
		//
		// SELECT smw_id FROM "smw_object_ids"
		// INNER JOIN smw_query_links AS p ON ((p.o_id=smw_id))
		// WHERE p.s_id = '18341' AND (smw_touched > '2019-01-08 17:45:03')
		// LIMIT 1
		$links_table = $connection->tableName( SQLStore::QUERY_LINKS_TABLE );

		$row = $connection->selectRow(
			[ SQLStore::ID_TABLE, $links_table . ' AS p' ],
			[
				'smw_id'
			],
			[
				'p.s_id' => $list,
				'smw_touched > ' . $connection->addQuotes( $touched ),
			],
			__METHOD__,
			[],
			[
				$links_table . ' AS p' => [ 'INNER JOIN', [ 'p.o_id=smw_id' ] ],
			]
		);

		// Could we match? If yes, an outdated reference was detected and we use
		// this is as a state for precautionary measure to declare a "archaic"
		// dependency state (the actual state of being or not is not important,
		// only the likelihood of being outdated is enough) to force queries to
		// be re-evaluated by allowing the parser cache to be evicted and commands
		// that embedded queries are refreshed and its links table where it may
		// fetch "assumed" outdated dependencies.
		//
		// We don't have any interest in any specific entities that may have been
		// the trigger only that there is a probability to require an update for
		// an embedded query by comparing the `touched` of dependent entities.
		return $row !== false;
	}

}
