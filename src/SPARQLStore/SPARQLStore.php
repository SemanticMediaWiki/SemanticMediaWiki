<?php

namespace SMW\SPARQLStore;

use MediaWiki\MediaWikiServices;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Serializer\TurtleSerializer;
use SMW\Options;
use SMW\SemanticData;
use SMW\SPARQLStore\Exception\HttpEndpointConnectionException;
use SMW\SQLStore\Rebuilder\Rebuilder;
use SMW\Store;
use SMW\Utils\CliMsgFormatter;
use SMWDataItem as DataItem;
use SMWExporter as Exporter;
use SMWQuery as Query;
use Title;

/**
 * Storage and query access point for a SPARQL supported RepositoryConnector to
 * enable SMW to communicate with a SPARQL endpoint.
 *
 * The store uses a base store to update certain aspects of the data that is not
 * yet modelled and supported by a RepositoryConnector, which may become optional
 * in future.
 *
 * @license GPL-2.0-or-later
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
	public static $baseStoreClass = '\SMW\SQLStore\SQLStore';

	/**
	 * Underlying store to use for basic read operations.
	 * Public since 5.0. (https://github.com/SemanticMediaWiki/SemanticMediaWiki/pull/5749)
	 *
	 * @since 1.8
	 * @var Store
	 */
	public $baseStore;

	/**
	 * @since 1.8
	 *
	 * @param Store|null $baseStore
	 */
	public function __construct( ?Store $baseStore = null ) {
		$this->factory = new SPARQLStoreFactory( $this );
		$this->baseStore = $baseStore;

		if ( $this->baseStore === null ) {
			$this->baseStore = $this->factory->getBaseStore( self::$baseStoreClass );
		}

		$this->connectionManager = $this->factory->getConnectionManager();
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
		return $this->baseStore->getPropertyValues( $subject, $property, $requestoptions );
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
		$oldExpResource = Exporter::getInstance()->newExpElement( $oldWikiPage );
		$newExpResource = Exporter::getInstance()->newExpElement( $newWikiPage );
		$namespaces = [ $oldExpResource->getNamespaceId() => $oldExpResource->getNamespace() ];
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
		$connection = $this->getConnection( 'sparql' );

		if (
			$connection->shouldPing() &&
			$connection->ping( RepositoryConnection::UPDATE_ENDPOINT ) === false ) {
			throw new HttpEndpointConnectionException(
				$connection->getEndpoint( RepositoryConnection::UPDATE_ENDPOINT ),
				$connection->getLastErrorCode()
			);
		}

		$replicationDataTruncator = $this->factory->newReplicationDataTruncator();
		$semanticData = $replicationDataTruncator->doTruncate( $semanticData );

		$turtleTriplesBuilder = $this->factory->newTurtleTriplesBuilder();

		$this->doSparqlFlatDataUpdate( $semanticData, $turtleTriplesBuilder );

		foreach ( $semanticData->getSubSemanticData() as $subSemanticData ) {
			$subSemanticData = $replicationDataTruncator->doTruncate( $subSemanticData );
			$this->doSparqlFlatDataUpdate( $subSemanticData, $turtleTriplesBuilder );
		}

		// wfDebugLog( 'smw', ' InMemoryPoolCache: ' . json_encode( \SMW\InMemoryPoolCache::getInstance()->getStats() ) );

		// Reset internal cache
		$turtleTriplesBuilder->reset();
	}

	/**
	 * @param SemanticData $semanticData
	 * @param TurtleTriplesBuilder $turtleTriplesBuilder
	 */
	private function doSparqlFlatDataUpdate( SemanticData $semanticData, TurtleTriplesBuilder $turtleTriplesBuilder ) {
		$turtleTriplesBuilder->doBuildTriplesFrom( $semanticData );

		if ( !$turtleTriplesBuilder->hasTriples() ) {
			return;
		}

		if ( $semanticData->getSubject()->getSubobjectName() === '' ) {
			$this->doSparqlDataDelete( $semanticData->getSubject() );
		}

		foreach ( $turtleTriplesBuilder->getChunkedTriples() as $chunkedTriples ) {
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
	 * @return bool
	 */
	public function doSparqlDataDelete( DataItem $dataItem ) {
		$extraNamespaces = [];
		$exporter = Exporter::getInstance();

		$expResource = $exporter->newExpElement( $dataItem );
		$resourceUri = TurtleSerializer::getTurtleNameForExpElement( $expResource );

		if ( $expResource instanceof ExpNsResource ) {
			$extraNamespaces = [ $expResource->getNamespaceId() => $expResource->getNamespace() ];
		}

		$masterPageProperty = $exporter->newExpNsResourceById( 'swivt', 'masterPage' );
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
		// Use a fallback QueryEngine in case the QueryEndpoint is inaccessible
		if ( !$this->hasQueryEndpoint() ) {
			return $this->baseStore->getQueryResult( $query );
		}

		$result = null;
		$start = microtime( true );

		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		if (
			$hookContainer->run(
				'SMW::Store::BeforeQueryResultLookupComplete',
				[ $this, $query, &$result, $this->factory->newMasterQueryEngine() ]
			)
		) {
			$result = $this->fetchQueryResult( $query );
		}

		$hookContainer->run( 'SMW::Store::AfterQueryResultLookupComplete', [ $this, &$result ] );

		$query->setOption( Query::PROC_QUERY_TIME, microtime( true ) - $start );

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
	 * @see Store::service
	 *
	 * {@inheritDoc}
	 */
	public function service( $service, ...$args ) {
		return $this->baseStore->service( $service, ...$args );
	}

	/**
	 * @see Store::setup()
	 * @since 1.8
	 */
	public function setup( $options = true ) {
		$this->baseStore->setMessageReporter( $this->messageReporter );

		$cliMsgFormatter = new CliMsgFormatter();

		$repositoryConnector = $this->getConnection( 'sparql' );
		$repositoryClient = $repositoryConnector->getRepositoryClient();

		if ( $options instanceof Options && $options->get( 'verbose' ) ) {

			if (
				$options->has( SMW_EXTENSION_SCHEMA_UPDATER ) &&
				$options->get( SMW_EXTENSION_SCHEMA_UPDATER ) ) {
				$this->messageReporter->reportMessage( $cliMsgFormatter->section( 'Sematic MediaWiki', 3, '=' ) );
				$this->messageReporter->reportMessage( "\n" . $cliMsgFormatter->head() );

				// Only output the head once hence for any succeeding processing
				// remove the marker.
				$options->set( SMW_EXTENSION_SCHEMA_UPDATER, false );
			}

			$this->messageReporter->reportMessage(
				$cliMsgFormatter->section( 'RDF store setup' )
			);

			$this->messageReporter->reportMessage(
				"\n" . $cliMsgFormatter->twoCols( "Query engine:", 'SMWSPARQLStore' )
			);

			$type = $repositoryClient->getName();
			$version = $repositoryConnector->getVersion();

			$this->messageReporter->reportMessage(
				$cliMsgFormatter->twoCols( "Repository connector (type/version):", "$type ($version)" )
			);
		}

		$this->baseStore->setup( $options );
	}

	/**
	 * @see Store::drop()
	 * @since 1.6
	 */
	public function drop( $verbose = true ) {
		$this->baseStore->setMessageReporter( $this->messageReporter );
		$this->baseStore->drop( $verbose );
		$this->getConnection()->deleteAll();
	}

	/**
	 * @see Store::refreshData()
	 * @since 1.8
	 */
	public function refreshData( &$index, $count, $namespaces = false, $usejobs = true ): Rebuilder {
		return $this->baseStore->refreshData( $index, $count, $namespaces, $usejobs );
	}

	/**
	 * @since 2.5
	 *
	 * @return PropertyTableInfoFetcher
	 */
	public function getPropertyTableInfoFetcher() {
		return $this->baseStore->getPropertyTableInfoFetcher();
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
	 * @since 2.4
	 */
	public function getPropertyTableIdReferenceFinder() {
		return $this->baseStore->getPropertyTableIdReferenceFinder();
	}

	/**
	 * @since  1.9.2
	 */
	public function clear() {
		$this->baseStore->clear();
	}

	/**
	 * @since 3.0
	 *
	 * @param string|null $type
	 *
	 * @return array
	 */
	public function getInfo( $type = null ) {
		$respositoryConnetion = $this->getConnection( 'sparql' );
		$repositoryClient = $respositoryConnetion->getRepositoryClient();

		if ( $type === 'store' ) {
			return [ 'SPARQLStore', $repositoryClient->getName() ];
		}

		$connection = $this->getConnection( 'mw.db' );

		if ( $type === 'db' ) {
			return $connection->getInfo();
		}

		return [
			'SPARQLStore' => $connection->getInfo() + [ $repositoryClient->getName() => $respositoryConnetion->getVersion() ]
		];
	}

	/**
	 * @since 2.1
	 *
	 * @param string $type
	 *
	 * @return mixed
	 */
	public function getConnection( $type = 'sparql' ) {
		return parent::getConnection( $type );
	}

	private function hasQueryEndpoint() {
		return $this->getConnection( 'sparql' )->getRepositoryClient()->getQueryEndpoint() !== false;
	}

}
