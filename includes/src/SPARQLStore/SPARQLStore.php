<?php

namespace SMW\SPARQLStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SPARQLStore\QueryEngine\QueryConditionBuilder;
use SMW\SPARQLStore\QueryEngine\QueryEngine;
use SMW\SPARQLStore\QueryEngine\QueryResultFactory;
use SMW\Store;
use SMWDataItem as DataItem;
use SMWExpNsResource as ExpNsResource;
use SMWExporter as Exporter;
use SMWQuery as Query;
use SMWTurtleSerializer as TurtleSerializer;
use SMWUpdateJob;
use Title;

/**
 * Storage access class for using SMW's SPARQL database for keeping semantic
 * data. The store keeps an underlying base store running for completeness.
 * This might become optional in the future.
 *
 * @ingroup Store
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class SPARQLStore extends Store {

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
	protected $baseStore;

	/**
	 * @since 1.9.2
	 * @var GenericHttpDatabaseConnector
	 */
	protected $sparqlDatabase = null;

	/**
	 * @since 1.8
	 *
	 * @param  Store $baseStore
	 */
	public function __construct( Store $baseStore = null ) {
		$this->baseStore = $baseStore;

		if ( $this->baseStore === null ) {
			$this->baseStore = new self::$baseStoreClass();
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
		$oldExpResource = Exporter::getDataItemExpElement( $oldWikiPage );
		$newExpResource = Exporter::getDataItemExpElement( $newWikiPage );
		$namespaces = array( $oldExpResource->getNamespaceId() => $oldExpResource->getNamespace() );
		$namespaces[$newExpResource->getNamespaceId()] = $newExpResource->getNamespace();
		$oldUri = TurtleSerializer::getTurtleNameForExpElement( $oldExpResource );
		$newUri = TurtleSerializer::getTurtleNameForExpElement( $newExpResource );

		$this->baseStore->changeTitle( $oldtitle, $newtitle, $pageid, $redirid ); // do this only here, so Imported from is not moved too early

		$sparqlDatabase = $this->getSparqlDatabase();
		$sparqlDatabase->insertDelete( "?s ?p $newUri", "?s ?p $oldUri", "?s ?p $oldUri", $namespaces );
		if ( $oldtitle->getNamespace() == SMW_NS_PROPERTY ) {
			$sparqlDatabase->insertDelete( "?s $newUri ?o", "?s $oldUri ?o", "?s $oldUri ?o", $namespaces );
		}
		// Note that we cannot change oldUri to newUri in triple subjects,
		// since some triples change due to the move. Use SMWUpdateJob.
		$newUpdate = new SMWUpdateJob( $newtitle );
		$newUpdate->run();
		if ( $redirid != 0 ) { // update/create redirect page data
			$oldUpdate = new SMWUpdateJob( $oldtitle );
			$oldUpdate->run();
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
	}

	/**
	 * Update the Sparql back-end, without taking any subobject data into account.
	 *
	 * @param SemanticData $semanticData
	 */
	private function doSparqlFlatDataUpdate( SemanticData $semanticData ) {

		$turtleTriplesBuilder = new TurtleTriplesBuilder(
			$semanticData,
			new RedirectLookup( $this->getSparqlDatabase() )
		);

		if ( !$turtleTriplesBuilder->hasTriplesForUpdate() ) {
			return;
		}

		$this->doSparqlDataDelete( $semanticData->getSubject() );

		$this->getSparqlDatabase()->insertData(
			$turtleTriplesBuilder->getTriples(),
			$turtleTriplesBuilder->getPrefixes()
		);
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

		$expResource = Exporter::getDataItemExpElement( $dataItem );
		$resourceUri = TurtleSerializer::getTurtleNameForExpElement( $expResource );

		if ( $expResource instanceof ExpNsResource ) {
			$extraNamespaces = array( $expResource->getNamespaceId() => $expResource->getNamespace() );
		}

		$masterPageProperty = Exporter::getSpecialNsResource( 'swivt', 'masterPage' );
		$masterPagePropertyUri = TurtleSerializer::getTurtleNameForExpElement( $masterPageProperty );

		$success = $this->getSparqlDatabase()->deleteContentByValue( $masterPagePropertyUri, $resourceUri, $extraNamespaces );

		if ( $success ) {
			return $this->getSparqlDatabase()->delete( "$resourceUri ?p ?o", "$resourceUri ?p ?o", $extraNamespaces );
		}

		return false;
	}

	/**
	 * @see Store::getQueryResult()
	 * @since 1.6
	 */
	public function getQueryResult( Query $query ) {

		$queryEngine = new QueryEngine(
			$this->getSparqlDatabase(),
			new QueryConditionBuilder(),
			new QueryResultFactory( $this )
		);

		return $queryEngine
			->setIgnoreQueryErrors( $GLOBALS['smwgIgnoreQueryErrors'] )
			->setSortingSupport( $GLOBALS['smwgQSortingSupport'] )
			->setRandomSortingSupport( $GLOBALS['smwgQRandSortingSupport'] )
			->getQueryResult( $query );
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
		$this->getSparqlDatabase()->deleteAll();
	}

	/**
	 * @see Store::refreshData()
	 * @since 1.8
	 */
	public function refreshData( &$index, $count, $namespaces = false, $usejobs = true ) {
		return $this->baseStore->refreshData( $index, $count, $namespaces, $usejobs );
	}

	/**
	 * @since  1.9.2
	 *
	 * @param GenericHttpDatabaseConnector $sparqlDatabase
	 */
	public function setSparqlDatabase( GenericHttpDatabaseConnector $sparqlDatabase ) {
		$this->sparqlDatabase = $sparqlDatabase;
		return $this;
	}

	/**
	 * @since 2.0
	 */
	public function getPropertyTables() {
		return $this->baseStore->getPropertyTables();
	}

	/**
	 * @since  1.9.2
	 *
	 * @return GenericHttpDatabaseConnector
	 */
	public function getSparqlDatabase() {

		if ( $this->sparqlDatabase === null ) {
			$this->sparqlDatabase = smwfGetSparqlDatabase();
		}

		return $this->sparqlDatabase;
	}

	/**
	 * @since  2.0
	 *
	 * @return Database
	 */
	public function getDatabase() {
		return $this->baseStore->getDatabase();
	}

	/**
	 * @since 2.0
	 */
	public function clear() {
		$this->baseStore->clear();
	}

}
