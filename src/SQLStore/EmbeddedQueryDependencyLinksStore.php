<?php

namespace SMW\SQLStore;

use SMW\Store;
use SMWQueryResult as QueryResult;
use SMWSQLStore3;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\HashBuilder;
use SMW\SemanticData;
use SMW\ApplicationFactory;
use SMW\EventHandler;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\ThingDescription;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class EmbeddedQueryDependencyLinksStore {

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * @var Database
	 */
	private $connection = null;

	/**
	 * @var boolean
	 */
	private $dependencyLinksTrackingState = true;

	/**
	 * @since 2.3
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
		$this->connection = $this->store->getConnection( 'mw.db' );
	}

	/**
	 * @since 2.3
	 *
	 * @return boolean
	 */
	public function canTrackDependencyLinks() {
		return $this->dependencyLinksTrackingState;
	}

	/**
	 * @since 2.3
	 *
	 * @param boolean $dependencyLinksTrackingState
	 */
	public function setDependencyLinksTrackingState( $dependencyLinksTrackingState ) {
		$this->dependencyLinksTrackingState = (bool)$dependencyLinksTrackingState;
	}

	/**
	 * @since 2.3
	 *
	 * @param CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator
	 */
	public function purgeOutdatedTargetLinks( CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator ) {

		if ( !$this->canTrackDependencyLinks() ) {
			return null;
		}

		$diff = $compositePropertyTableDiffIterator->getOrderedDiffByTable( 'smw_fpt_ask' );

		// Remove any dependency for queries that are no longer used
		if ( isset( $diff['smw_fpt_ask']['delete'] ) ) {

			$deleteIdList = array();

			foreach ( $diff['smw_fpt_ask']['delete'] as $delete ) {
				$deleteIdList[] = $delete['o_id'];
			}

			wfDebugLog( 'smw' , __METHOD__ . ' remove ' . implode( ',', $deleteIdList ) . "\n" );

			$this->connection->delete(
				SMWSQLStore3::QUERY_LINKS_TABLE,
				array(
					's_id' => $deleteIdList
				),
				__METHOD__
			);
		}

		// Dispatch any event registered earlier during the QueryResult processing
		// that didn't match a sid
		EventHandler::getInstance()->getEventDispatcher()->dispatch(
			'deferred.embedded.query.dep.update'
		);
	}

	/**
	 * Finds a partial list (given limit and offset) of registered subjects that
	 * that represent a dependency on something like a subject in a query list,
	 * a property, or a printrequest.
	 *
	 * `s_id` contains the subject id that links to the query that fulfills one
	 * of the conditions cited above.
	 *
	 * Prefetched Ids are turned into a hash list that can later be split into
	 * chunks to work either in online or batch mode without creating a huge memory
	 * foothold.
	 *
	 * @note Select a list is crucial for performance as any selectRow would /
	 * single Id select would strain the system on large list connected to a
	 * query
	 *
	 * @since 2.3
	 *
	 * @param array $idlist
	 * @param integer $limit
	 * @param integer $offset
	 *
	 * @return array
	 */
	public function findPartialEmbeddedQueryTargetLinksHashListFor( array $idlist, $limit, $offset ) {

		if ( $idlist === array() || !$this->canTrackDependencyLinks() ) {
			return array();
		}

		$options = array(
			'LIMIT'    => $limit,
			'OFFSET'   => $offset,
			'GROUP BY' => 's_id',
			'ORDER BY' => 's_id'
		);

		$rows = $this->connection->select(
			SMWSQLStore3::QUERY_LINKS_TABLE,
			array( 's_id' ),
			array(
				'o_id' => $idlist
			),
			__METHOD__,
			$options
		);

		$targetLinksIdList = array();

		foreach ( $rows as $row ) {
			$targetLinksIdList[] = $row->s_id;
		}

		if ( $targetLinksIdList === array() ) {
			return array();
		}

		return $this->store->getObjectIds()->getDataItemPoolHashListFor(
			$targetLinksIdList
		);
	}

	/**
	 * @since 2.3
	 *
	 * @param QueryResult $result
	 */
	public function addDependenciesFromQueryResult( $result ) {

		if ( !$this->canTrackDependencyLinks() || !$result instanceof QueryResult ) {
			return null;
		}

		if ( $result->getQuery() === null || $result->getQuery()->getSubject() === null ) {
			return null;
		}

		$subject = $result->getQuery()->getSubject();

		$dependencyList = array(
			$subject
		);

		// Find entities described by the query
		$this->doResolveDependenciesFromDescription(
			$dependencyList,
			$result->getQuery()->getDescription()
		);

		$this->doResolveDependenciesFromPrintRequest(
			$dependencyList,
			$result->getQuery()->getDescription()->getPrintRequests()
		);

		$dependencyList = array_merge(
			$dependencyList,
			$result->getResults()
		);

		$result->reset();

		$hash = $result->getQuery()->getQueryId();
		$sid = $this->getIdForSubject( $subject, $hash );

		if ( $sid > 0 ) {
			return $this->updateDependencyList( $sid, $dependencyList );
		}

		// SID is unknown because the storage update/process has not been finalized
		// hence an event is registered and triggered once the update process
		// is being completed

		// PHP 5.3 compatibility
		$embeddedQueryResultLinksUpdater = $this;

		EventHandler::getInstance()->addCallbackListener( 'deferred.embedded.query.dep.update', function() use ( $embeddedQueryResultLinksUpdater, $dependencyList, $subject, $hash ) {

			wfDebugLog( 'smw', __METHOD__ . ' deferred.embedded.query.dep.update for ' . $hash . "\n" );

			$embeddedQueryResultLinksUpdater->updateDependencyList(
				$embeddedQueryResultLinksUpdater->getIdForSubject( $subject, $hash ),
				$dependencyList
			);
		} );
	}

	/**
	 * @since 2.3
	 *
	 * @param integer $sid
	 * @param array $dependencyList
	 */
	public function updateDependencyList( $sid, array $dependencyList ) {

		// Before an insert, delete all matches for the criteria which is cheaper
		// then doing an individual upsert or selectRow
		$this->connection->delete(
			SMWSQLStore3::QUERY_LINKS_TABLE,
			array(
				's_id' => $sid
			),
			__METHOD__
		);

		$inserts = array();

		foreach ( $dependencyList as $dependency ) {

			$oid = $this->getIdForSubject( $dependency );

			$inserts[ $sid . $oid ] = array(
				's_id' => $sid,
				'o_id' => $oid
			);
		}

		if ( $inserts === array() ) {
			return null;
		}

		// MW's multi-array insert needs a numeric dimensional array but the key
		// was used with a hash to avoid duplicate entries hence the re-copy
		$inserts = array_values( $inserts );

		wfDebugLog( 'smw' , __METHOD__ . ' insert ' . count( $inserts ) . ' to ' . $sid . "\n" );

		$this->connection->insert(
			SMWSQLStore3::QUERY_LINKS_TABLE,
			$inserts,
			__METHOD__
		);
	}

	private function doResolveDependenciesFromDescription( &$subjects, $description ) {

		if ( $description instanceof ValueDescription && $description->getDataItem() instanceof DIWikiPage ) {
			$subjects[] = $description->getDataItem();
		}

		if ( $description instanceof ConceptDescription ) {
			$subjects[] = $description->getConcept();
			$this->doResolveDependenciesFromDescription(
				$subjects,
				$this->getConceptDescription( $description->getConcept() )
			);
		}

		if ( $description instanceof ClassDescription ) {
			foreach ( $description->getCategories() as $category ) {
				$subjects[] = $category;
			}
		}

		if ( $description instanceof SomeProperty ) {
			$this->doResolveDependenciesFromDescription( $subjects, $description->getDescription() );
			$subjects[] = $description->getProperty()->getDiWikiPage();
		}

		if ( $description instanceof Conjunction || $description instanceof Disjunction ) {
			foreach ( $description->getDescriptions() as $description ) {
				$this->doResolveDependenciesFromDescription( $subjects, $description );
			}
		}
	}

	private function doResolveDependenciesFromPrintRequest( &$subjects, array $printRequests ) {

		foreach ( $printRequests as $printRequest ) {
			$data = $printRequest->getData();

			if ( $data instanceof \SMWPropertyValue ) {
				$subjects[] = $data->getDataItem()->getDiWikiPage();
			}

			// Category
			if ( $data instanceof \Title ) {
				$subjects[] = DIWikiPage::newFromTitle( $data );
			}
		}
	}

	public function getIdForSubject( DIWikiPage $subject, $subobjectName = '' ) {
		return $this->store->getObjectIds()->getSMWPageID(
			$subject->getDBkey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subobjectName,
			false
		);
	}

	private function getConceptDescription( DIWikiPage $concept ) {

		$value = $this->store->getSemanticData( $concept )->getPropertyValues(
			new DIProperty( '_CONC' )
		);

		if ( $value === null || $value === array() ) {
			return new ThingDescription();
		}

		$value = end( $value );

		return ApplicationFactory::getInstance()->newQueryParser()->getQueryDescription(
			$value->getConceptQuery()
		);
	}

}
