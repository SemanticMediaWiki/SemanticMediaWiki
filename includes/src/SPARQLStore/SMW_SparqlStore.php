<?php

use SMW\SPARQLStore\QueryEngine\QueryEngine;
use SMW\SPARQLStore\QueryEngine\QueryConditionBuilder;
use SMW\SPARQLStore\QueryEngine\ResultListConverter;

use SMW\SPARQLStore\RedirectLookup;
use SMW\SPARQLStore\TurtleTriplesBuilder;

use SMW\SemanticData;
use SMW\DIWikiPage;

use SMWDataItem as DataItem;

/**
 * SPARQL implementation of SMW's storage abstraction layer.
 *
 * @author Markus Krötzsch
 *
 * @file
 * @ingroup SMWStore
 */

/**
 * Storage access class for using SMW's SPARQL database for keeping semantic
 * data. The store keeps an underlying base store running for completeness.
 * This might become optional in the future.
 *
 * @since 1.6
 *
 * @ingroup SMWStore
 */
class SMWSparqlStore extends SMWStore {

	/**
	 * Class to be used as an underlying base store. This can be changed in
	 * LocalSettings.php (after enableSemantics()) to use another base
	 * store.
	 *
	 * @var string
	 * @since 1.8
	 */
	static public $baseStoreClass = 'SMWSQLStore3';

	/**
	 * Underlying store to use for basic read operations.
	 *
	 * @var SMWStore
	 * @since 1.8
	 */
	protected $baseStore;

	/**
	 * @var SMWSparqlDatabase
	 * @since 1.9.2
	 */
	protected $sparqlDatabase = null;

	/**
	 * Constructor.
	 *
	 * @since 1.8
	 */
	public function __construct( SMWStore $baseStore = null ) {
		$this->baseStore = $baseStore;

		if ( $this->baseStore === null ) {
			$this->baseStore = new self::$baseStoreClass();
		}
	}

	/**
	 * @see SMWStore::getSemanticData()
	 * @since 1.8
	 */
	public function getSemanticData( SMWDIWikiPage $subject, $filter = false ) {
		return $this->baseStore->getSemanticData( $subject, $filter );
	}

	/**
	 * @see SMWStore::getPropertyValues()
	 * @since 1.8
	 */
	public function getPropertyValues( $subject, SMWDIProperty $property, $requestoptions = null ) {
		return $this->baseStore->getPropertyValues( $subject, $property, $requestoptions);
	}

	/**
	 * @see SMWStore::getPropertySubjects()
	 * @since 1.8
	 */
	public function getPropertySubjects( SMWDIProperty $property, $value, $requestoptions = null ) {
		return $this->baseStore->getPropertySubjects( $property, $value, $requestoptions );
	}

	/**
	 * @see SMWStore::getAllPropertySubjects()
	 * @since 1.8
	 */
	public function getAllPropertySubjects( SMWDIProperty $property, $requestoptions = null ) {
		return $this->baseStore->getAllPropertySubjects( $property, $requestoptions );
	}

	/**
	 * @see SMWStore::getProperties()
	 * @since 1.8
	 */
	public function getProperties( SMWDIWikiPage $subject, $requestoptions = null ) {
		return $this->baseStore->getProperties( $subject, $requestoptions );
	}

	/**
	 * @see SMWStore::getInProperties()
	 * @since 1.8
	 */
	public function getInProperties( SMWDataItem $object, $requestoptions = null ) {
		return $this->baseStore->getInProperties( $object, $requestoptions );
	}

	/**
	 * @see SMWStore::deleteSubject()
	 * @since 1.6
	 */
	public function deleteSubject( Title $subject ) {
		$this->doSparqlDataDelete( DIWikiPage::newFromTitle( $subject ) );
		$this->baseStore->deleteSubject( $subject );
	}

	/**
	 * @see SMWStore::changeTitle()
	 * @since 1.6
	 */
	public function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {
		$oldWikiPage = SMWDIWikiPage::newFromTitle( $oldtitle );
		$newWikiPage = SMWDIWikiPage::newFromTitle( $newtitle );
		$oldExpResource = SMWExporter::getDataItemExpElement( $oldWikiPage );
		$newExpResource = SMWExporter::getDataItemExpElement( $newWikiPage );
		$namespaces = array( $oldExpResource->getNamespaceId() => $oldExpResource->getNamespace() );
		$namespaces[$newExpResource->getNamespaceId()] = $newExpResource->getNamespace();
		$oldUri = SMWTurtleSerializer::getTurtleNameForExpElement( $oldExpResource );
		$newUri = SMWTurtleSerializer::getTurtleNameForExpElement( $newExpResource );

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
	 * @see SMWStore::doDataUpdate()
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
	 * @since 1.9.3
	 *
	 * @param DataItem $dataItem
	 *
	 * @return boolean
	 */
	public function doSparqlDataDelete( DataItem $dataItem ) {

		$extraNamespaces = array();

		$expResource = SMWExporter::getDataItemExpElement( $dataItem );
		$resourceUri = SMWTurtleSerializer::getTurtleNameForExpElement( $expResource );

		if ( $expResource instanceof SMWExpNsResource ) {
			$extraNamespaces = array( $expResource->getNamespaceId() => $expResource->getNamespace() );
		}

		$masterPageProperty = SMWExporter::getSpecialNsResource( 'swivt', 'masterPage' );
		$masterPagePropertyUri = SMWTurtleSerializer::getTurtleNameForExpElement( $masterPageProperty );

		$success = $this->getSparqlDatabase()->deleteContentByValue( $masterPagePropertyUri, $resourceUri, $extraNamespaces );

		if ( $success ) {
			return $this->getSparqlDatabase()->delete( "$resourceUri ?p ?o", "$resourceUri ?p ?o", $extraNamespaces );
		}

		return false;
	}

	/**
	 * @see SMWStore::getQueryResult()
	 * @since 1.6
	 */
	public function getQueryResult( SMWQuery $query ) {

		$queryEngine = new QueryEngine(
			$this->getSparqlDatabase(),
			new QueryConditionBuilder(),
			new ResultListConverter( $this )
		);

		return $queryEngine
			->setIgnoreQueryErrors( $GLOBALS['smwgIgnoreQueryErrors'] )
			->setSortingSupport( $GLOBALS['smwgQSortingSupport'] )
			->setRandomSortingSupport( $GLOBALS['smwgQRandSortingSupport'] )
			->getQueryResult( $query );
	}

	/**
	 * @see SMWStore::getPropertiesSpecial()
	 * @since 1.8
	 */
	public function getPropertiesSpecial( $requestoptions = null ) {
		return $this->baseStore->getPropertiesSpecial( $requestoptions );
	}

	/**
	 * @see SMWStore::getUnusedPropertiesSpecial()
	 * @since 1.8
	 */
	public function getUnusedPropertiesSpecial( $requestoptions = null ) {
		return $this->baseStore->getUnusedPropertiesSpecial( $requestoptions );
	}

	/**
	 * @see SMWStore::getWantedPropertiesSpecial()
	 * @since 1.8
	 */
	public function getWantedPropertiesSpecial( $requestoptions = null ) {
		return $this->baseStore->getWantedPropertiesSpecial( $requestoptions );
	}

	/**
	 * @see SMWStore::getStatistics()
	 * @since 1.8
	 */
	public function getStatistics() {
		return $this->baseStore->getStatistics();
	}

	/**
	 * @see SMWStore::setup()
	 * @since 1.8
	 */
	public function setup( $verbose = true ) {
		$this->baseStore->setup( $verbose );
	}

	/**
	 * @see SMWStore::drop()
	 * @since 1.6
	 */
	public function drop( $verbose = true ) {
		$this->baseStore->drop( $verbose );
		$this->getSparqlDatabase()->deleteAll();
	}

	/**
	 * @see SMWStore::refreshData()
	 * @since 1.8
	 */
	public function refreshData( &$index, $count, $namespaces = false, $usejobs = true ) {
		return $this->baseStore->refreshData( $index, $count, $namespaces, $usejobs );
	}

	/**
	 * @since  1.9.2
	 *
	 * @param SMWSparqlDatabase $sparqlDatabase
	 */
	public function setSparqlDatabase( SMWSparqlDatabase $sparqlDatabase ) {
		$this->sparqlDatabase = $sparqlDatabase;
		return $this;
	}

	/**
	 * @since  1.9.2
	 *
	 * @return SMWSparqlDatabase
	 */
	public function getSparqlDatabase() {

		if ( $this->sparqlDatabase === null ) {
			$this->sparqlDatabase = smwfGetSparqlDatabase();
		}

		return $this->sparqlDatabase;
	}

	/**
	 * @since  1.9.3
	 *
	 * @return Database
	 */
	public function getDatabase() {
		return $this->baseStore->getDatabase();
	}

}
