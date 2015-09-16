<?php

namespace SMW\SPARQLStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\Store;
use SMWDataItem as DataItem;
use SMWExpNsResource as ExpNsResource;
use SMWExporter as Exporter;
use SMWQuery as Query;
use SMWTurtleSerializer as TurtleSerializer;
use SMW\MediaWiki\Jobs\UpdateJob;
use Title;

/**
 * Storage and query access point for a SPARQL supported RepositoryConnector to
 * enable SMW to communicate with a SPARQL endpoint.
 *
 * The store uses a base store to update certain aspects of the data that is not
 * yet modelled and supported by a RepositoryConnector, which may become optional
 * in future.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class SPARQLStore extends Store {

	/**
	 * @var SPARQLStoreFactory
	 */
	private $factory;

	/**
	 * Class to be used as an underlying base store. This can be changed in
	 * LocalSettings.php (after enableSemantics()) to use another base
	 * store.
	 *
	 * @since 1.8
	 * @var string
	 */
	static public $baseStoreClass = 'SMWSQLStore3';

	/**
	 * Underlying store to use for basic read operations.
	 *
	 * @since 1.8
	 * @var Store
	 */
	private $baseStore;

	/**
	 * @since 1.8
	 *
	 * @param  Store $baseStore
	 */
	public function __construct( Store $baseStore = null ) {
		$this->factory = new SPARQLStoreFactory( $this );
		$this->baseStore = $baseStore;

		if ( $this->baseStore === null ) {
			$this->baseStore = $this->factory->newBaseStore( self::$baseStoreClass );
		}
	}

	/**
	 * @see Store::getSemanticData()
	 * @since 1.8
	 */
	public function getSemanticData( DIWikiPage $subject, $filter = false ) {
		return $this->baseStore->getSemanticData( $subject, $filter );
	}

	/**
	 * @see Store::getPropertyValues()
	 * @since 1.8
	 */
	public function getPropertyValues( $subject, DIProperty $property, $requestoptions = null ) {
		return $this->baseStore->getPropertyValues( $subject, $property, $requestoptions);
	}

	/**
	 * @see Store::getPropertySubjects()
	 * @since 1.8
	 */
	public function getPropertySubjects( DIProperty $property, $value, $requestoptions = null ) {
		return $this->baseStore->getPropertySubjects( $property, $value, $requestoptions );
	}

	/**
	 * @see Store::getAllPropertySubjects()
	 * @since 1.8
	 */
	public function getAllPropertySubjects( DIProperty $property, $requestoptions = null ) {
		return $this->baseStore->getAllPropertySubjects( $property, $requestoptions );
	}

	/**
	 * @see Store::getProperties()
	 * @since 1.8
	 */
	public function getProperties( DIWikiPage $subject, $requestoptions = null ) {
		return $this->baseStore->getProperties( $subject, $requestoptions );
	}

	/**
	 * @see Store::getInProperties()
	 * @since 1.8
	 */
	public function getInProperties( DataItem $object, $requestoptions = null ) {
		return $this->baseStore->getInProperties( $object, $requestoptions );
	}

	/**
	 * @see Store::deleteSubject()
	 * @since 1.6
	 */
	public function deleteSubject( Title $subject ) {
		$this->doSparqlDataDelete( DIWikiPage::newFromTitle( $subject ) );
		$this->baseStore->deleteSubject( $subject );
	}

	/**
	 * @see Store::changeTitle()
	 * @since 1.6
	 */
	public function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {

		$oldWikiPage = DIWikiPage::newFromTitle( $oldtitle );
		$newWikiPage = DIWikiPage::newFromTitle( $newtitle );
		$oldExpResource = Exporter::getInstance()->getDataItemExpElement( $oldWikiPage );
		$newExpResource = Exporter::getInstance()->getDataItemExpElement( $newWikiPage );
		$namespaces = array( $oldExpResource->getNamespaceId() => $oldExpResource->getNamespace() );
		$namespaces[$newExpResource->getNamespaceId()] = $newExpResource->getNamespace();
		$oldUri = TurtleSerializer::getTurtleNameForExpElement( $oldExpResource );
		$newUri = TurtleSerializer::getTurtleNameForExpElement( $newExpResource );

		// do this only here, so Imported from is not moved too early
		$this->baseStore->changeTitle(
			$oldtitle,
			$newtitle,
			$pageid,
			$redirid
		);

		$sparqlDatabase = $this->getConnection();
		$sparqlDatabase->insertDelete( "?s ?p $newUri", "?s ?p $oldUri", "?s ?p $oldUri", $namespaces );

		if ( $oldtitle->getNamespace() === SMW_NS_PROPERTY ) {
			$sparqlDatabase->insertDelete( "?s $newUri ?o", "?s $oldUri ?o", "?s $oldUri ?o", $namespaces );
		}

		/**
		 * @since 2.3 Moved UpdateJob to the base-store to ensurethat both stores
		 * operate similar when dealing with redirects
		 *
		 * @note Note that we cannot change oldUri to newUri in triple subjects,
		 * since some triples change due to the move.
		 */

		// #566 $redirid == 0 indicates a `move` not a redirect action
		if ( $redirid == 0 ) {
			$this->doSparqlDataDelete( $oldWikiPage );
		}
	}

	/**
	 * Update the Sparql back-end.
	 *
	 * This method can be called independently to force an update of the Sparql
	 * database. In general it is suggested to use updateData to carry out a
	 * synchronized update of the base and Sparql store.
	 *
	 * @since 2.0
	 *
	 * @param SemanticData $semanticData
	 */
	public function doSparqlDataUpdate( SemanticData $semanticData ) {

		$this->doSparqlFlatDataUpdate( $semanticData );

		foreach( $semanticData->getSubSemanticData() as $subSemanticData ) {
			 $this->doSparqlFlatDataUpdate( $subSemanticData );
		}

		//wfDebugLog( 'smw', ' InMemoryPoolCache: ' . json_encode( \SMW\InMemoryPoolCache::getInstance()->getStats() ) );

		// Reset internal cache
		TurtleTriplesBuilder::reset();
	}

	/**
	 * Update the Sparql back-end, without taking any subobject data into account.
	 *
	 * @param SemanticData $semanticData
	 */
	private function doSparqlFlatDataUpdate( SemanticData $semanticData ) {

		$turtleTriplesBuilder = new TurtleTriplesBuilder(
			$semanticData,
			new RedirectLookup( $this->getConnection() )
		);

		$turtleTriplesBuilder->setTriplesChunkSize( 80 );

		if ( !$turtleTriplesBuilder->hasTriplesForUpdate() ) {
			return;
		}

		if ( $semanticData->getSubject()->getSubobjectName() === '' ) {
			$this->doSparqlDataDelete( $semanticData->getSubject() );
		}

		foreach( $turtleTriplesBuilder->getChunkedTriples() as $chunkedTriples ) {
			$this->getConnection()->insertData(
				$chunkedTriples,
				$turtleTriplesBuilder->getPrefixes()
			);
		}
	}

	/**
	 * @see Store::doDataUpdate()
	 * @since 1.6
	 */
	protected function doDataUpdate( SemanticData $semanticData ) {
		$this->baseStore->doDataUpdate( $semanticData );
		$this->doSparqlDataUpdate( $semanticData );
	}

	/**
	 * Delete a dataitem from the Sparql back-end together with all data that is
	 * associated resources
	 *
	 * @since 2.0
	 *
	 * @param DataItem $dataItem
	 *
	 * @return boolean
	 */
	public function doSparqlDataDelete( DataItem $dataItem ) {

		$extraNamespaces = array();

		$expResource = Exporter::getInstance()->getDataItemExpElement( $dataItem );
		$resourceUri = TurtleSerializer::getTurtleNameForExpElement( $expResource );

		if ( $expResource instanceof ExpNsResource ) {
			$extraNamespaces = array( $expResource->getNamespaceId() => $expResource->getNamespace() );
		}

		$masterPageProperty = Exporter::getInstance()->getSpecialNsResource( 'swivt', 'masterPage' );
		$masterPagePropertyUri = TurtleSerializer::getTurtleNameForExpElement( $masterPageProperty );

		$success = $this->getConnection()->deleteContentByValue( $masterPagePropertyUri, $resourceUri, $extraNamespaces );

		if ( $success ) {
			return $this->getConnection()->delete( "$resourceUri ?p ?o", "$resourceUri ?p ?o", $extraNamespaces );
		}

		return false;
	}

	/**
	 * @note Move hooks to the base class in 3.*
	 *
	 * @see Store::getQueryResult
	 * @since 1.6
	 */
	public function getQueryResult( Query $query ) {

		$result = null;

		if ( wfRunHooks( 'SMW::Store::BeforeQueryResultLookupComplete', array( $this, $query, &$result ) ) ) {
			$result = $this->fetchQueryResult( $query );
		}

		wfRunHooks( 'SMW::Store::AfterQueryResultLookupComplete', array( $this, &$result ) );

		return $result;
	}

	protected function fetchQueryResult( Query $query ) {
		return $this->factory->newMasterQueryEngine()->getQueryResult( $query );
	}

	/**
	 * @see Store::getPropertiesSpecial()
	 * @since 1.8
	 */
	public function getPropertiesSpecial( $requestoptions = null ) {
		return $this->baseStore->getPropertiesSpecial( $requestoptions );
	}

	/**
	 * @see Store::getUnusedPropertiesSpecial()
	 * @since 1.8
	 */
	public function getUnusedPropertiesSpecial( $requestoptions = null ) {
		return $this->baseStore->getUnusedPropertiesSpecial( $requestoptions );
	}

	/**
	 * @see Store::getWantedPropertiesSpecial()
	 * @since 1.8
	 */
	public function getWantedPropertiesSpecial( $requestoptions = null ) {
		return $this->baseStore->getWantedPropertiesSpecial( $requestoptions );
	}

	/**
	 * @see Store::getStatistics()
	 * @since 1.8
	 */
	public function getStatistics() {
		return $this->baseStore->getStatistics();
	}

	/**
	 * @see Store::refreshConceptCache()
	 * @since 1.8
	 */
	public function refreshConceptCache( Title $concept ) {
		return $this->baseStore->refreshConceptCache( $concept );
	}

	/**
	 * @see Store::deleteConceptCache()
	 * @since 1.8
	 */
	public function deleteConceptCache( $concept ) {
		return $this->baseStore->deleteConceptCache( $concept );
	}

	/**
	 * @see Store::getConceptCacheStatus()
	 * @since 1.8
	 */
	public function getConceptCacheStatus( $concept ) {
		return $this->baseStore->getConceptCacheStatus( $concept );
	}

	/**
	 * @see Store::setup()
	 * @since 1.8
	 */
	public function setup( $verbose = true ) {
		$this->baseStore->setup( $verbose );
	}

	/**
	 * @see Store::drop()
	 * @since 1.6
	 */
	public function drop( $verbose = true ) {
		$this->baseStore->drop( $verbose );
		$this->getConnection()->deleteAll();
	}

	/**
	 * @see Store::refreshData()
	 * @since 1.8
	 */
	public function refreshData( &$index, $count, $namespaces = false, $usejobs = true ) {
		return $this->baseStore->refreshData( $index, $count, $namespaces, $usejobs );
	}

	/**
	 * @since 2.0
	 */
	public function getPropertyTables() {
		return $this->baseStore->getPropertyTables();
	}

	/**
	 * @since 2.3
	 */
	public function getObjectIds() {
		return $this->baseStore->getObjectIds();
	}

	/**
	 * @since  1.9.2
	 */
	public function clear() {
		$this->baseStore->clear();
	}

	/**
	 * @since 2.1
	 *
	 * @param boolean $status
	 */
	public function setUpdateJobsEnabledState( $status ) {
		$this->baseStore->setUpdateJobsEnabledState( $status );
	}

	/**
	 * @since 2.1
	 *
	 * @param string $connectionTypeId
	 *
	 * @return mixed
	 */
	public function getConnection( $connectionTypeId = 'sparql' ) {

		if ( $this->connectionManager === null ) {
			$this->setConnectionManager( $this->factory->newConnectionManager() );
		}

		return parent::getConnection( $connectionTypeId );
	}

}
