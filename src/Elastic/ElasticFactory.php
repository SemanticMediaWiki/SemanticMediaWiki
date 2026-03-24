<?php

namespace SMW\Elastic;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\NullMessageReporter;
use SMW\Elastic\Admin\ElasticClientTaskHandler;
use SMW\Elastic\Admin\IndicesInfoProvider;
use SMW\Elastic\Admin\MappingsInfoProvider;
use SMW\Elastic\Admin\NodesInfoProvider;
use SMW\Elastic\Admin\ReplicationInfoProvider;
use SMW\Elastic\Admin\SettingsInfoProvider;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\Connection\ConnectionProvider;
use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\Connection\LockManager;
use SMW\Elastic\Hooks\UpdateEntityCollationComplete;
use SMW\Elastic\Indexer\Attachment\FileAttachment;
use SMW\Elastic\Indexer\Attachment\FileHandler;
use SMW\Elastic\Indexer\Bulk;
use SMW\Elastic\Indexer\DocumentCreator;
use SMW\Elastic\Indexer\FileIndexer;
use SMW\Elastic\Indexer\Indexer;
use SMW\Elastic\Indexer\Rebuilder\Rebuilder;
use SMW\Elastic\Indexer\Rebuilder\Rollover;
use SMW\Elastic\Indexer\Replication\DocumentReplicationExaminer;
use SMW\Elastic\Indexer\Replication\ReplicationCheck;
use SMW\Elastic\Indexer\Replication\ReplicationStatus;
use SMW\Elastic\Lookup\ProximityPropertyValueLookup;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\DisjunctionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\SomePropertyInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\SomeValueInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter;
use SMW\Elastic\QueryEngine\QueryEngine;
use SMW\Elastic\QueryEngine\TermsLookup\CachingTermsLookup;
use SMW\Elastic\QueryEngine\TermsLookup\TermsLookup;
use SMW\Options;
use SMW\Services\ServicesContainer;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticFactory {

	private ?Indexer $indexer = null;

	/**
	 * @since 3.2
	 *
	 * @return Hooks
	 */
	public function newHooks(): Hooks {
		return new Hooks( $this );
	}

	/**
	 * @since 3.0
	 *
	 * @return Config
	 */
	public function newConfig(): Config {
		$settings = ApplicationFactory::getInstance()->getSettings();

		$config = new Config(
			$settings->get( 'smwgElasticsearchConfig' )
		);

		$config->set(
			Config::DEFAULT_STORE,
			$settings->get( 'smwgDefaultStore' )
		);

		$config->set(
			Config::ELASTIC_ENDPOINTS,
			$settings->get( 'smwgElasticsearchEndpoints' )
		);

		$config->set(
			Config::ELASTIC_CREDENTIALS,
			$settings->get( 'smwgElasticsearchCredentials' )
		);

		$config->loadFromJSON(
			$config->readFile( $settings->get( 'smwgElasticsearchProfile' ) )
		);

		$config->reassignDeprectedKeys();

		return $config;
	}

	/**
	 * @since 3.0
	 *
	 * @return ConnectionProvider
	 */
	public function newConnectionProvider(): ConnectionProvider {
		$applicationFactory = ApplicationFactory::getInstance();

		$connectionProvider = new ConnectionProvider(
			new LockManager( $applicationFactory->getCache() ),
			$this->newConfig()
		);

		$connectionProvider->setLogger(
			$applicationFactory->getMediaWikiLogger( 'smw-elastic' )
		);

		return $connectionProvider;
	}

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 *
	 * @return DocumentCreator
	 */
	public function newDocumentCreator( Store $store ): DocumentCreator {
		$config = $store->getConnection( 'elastic' )->getConfig();

		$documentCreator = new DocumentCreator( $store );

		$documentCreator->setCompatibilityMode(
			$config->dotGet( 'indexer.data.sqlstore_compatibility' )
		);

		return $documentCreator;
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 *
	 * @return ProximityPropertyValueLookup
	 */
	public function newProximityPropertyValueLookup( Store $store ): ProximityPropertyValueLookup {
		return new ProximityPropertyValueLookup( $store );
	}

	/**
	 * @since 3.2
	 *
	 * @param ElasticClient $connection
	 *
	 * @return Installer
	 */
	public function newInstaller( ElasticClient $connection ): Installer {
		return new Installer( $this->newRollover( $connection ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param Store|null $store
	 * @param MessageReporter|null $messageReporter
	 *
	 * @return Indexer
	 */
	public function newIndexer( ?Store $store = null, ?MessageReporter $messageReporter = null ): Indexer {
		$applicationFactory = ApplicationFactory::getInstance();

		if ( $store === null ) {
			$store = $applicationFactory->getStore();
		}

		$connection = $store->getConnection( 'elastic' );

		$indexer = new Indexer(
			$store,
			$this->newBulk( $connection )
		);

		if ( $messageReporter === null ) {
			$messageReporter = new NullMessageReporter();
		}

		$indexer->setLogger(
			$applicationFactory->getMediaWikiLogger( 'smw-elastic' )
		);

		$indexer->setRevisionGuard(
			$applicationFactory->singleton( 'RevisionGuard' )
		);

		$indexer->setMessageReporter(
			$messageReporter
		);

		return $indexer;
	}

	/**
	 * @since 3.0
	 *
	 * @param ElasticClient $connection
	 *
	 * @return Rollover
	 */
	public function newRollover( ElasticClient $connection ): Rollover {
		return new Rollover( $connection );
	}

	/**
	 * @since 3.0
	 *
	 * @param ElasticClient $connection
	 *
	 * @return Bulk
	 */
	public function newBulk( ElasticClient $connection ): Bulk {
		return new Bulk( $connection );
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param Indexer $indexer
	 *
	 * @return FileIndexer
	 */
	public function newFileIndexer( Store $store, Indexer $indexer ): FileIndexer {
		$applicationFactory = ApplicationFactory::getInstance();

		$logger = $applicationFactory->getMediaWikiLogger( 'smw-elastic' );
		$connection = $store->getConnection( 'elastic' );

		// Don't use the `ElasticStore` instance otherwise we index fields
		// recursively since the annotation for attachment information can only
		// happen after the ES ingest processor has been run.
		$fileAttachment = new FileAttachment(
			$applicationFactory->getStore( SQLStore::class ),
			$indexer,
			$this->newBulk( $connection )
		);

		$fileAttachment->setLogger(
			$logger
		);

		$fileHandler = new FileHandler(
			$applicationFactory->create( 'FileRepoFinder' )
		);

		$fileHandler->setLogger(
			$logger
		);

		$fileIndexer = new FileIndexer(
			$store,
			$applicationFactory->getEntityCache(),
			$fileHandler,
			$fileAttachment
		);

		$fileIndexer->setRevisionGuard(
			$applicationFactory->singleton( 'RevisionGuard' )
		);

		$fileIndexer->setLogger(
			$logger
		);

		return $fileIndexer;
	}

	/**
	 * @since 3.1
	 *
	 * @param ElasticClient $connection
	 *
	 * @return ReplicationStatus
	 */
	public function newReplicationStatus( ElasticClient $connection ): ReplicationStatus {
		return new ReplicationStatus( $connection );
	}

	/**
	 * @since 3.1
	 *
	 * @param Store|null $store
	 *
	 * @return DocumentReplicationExaminer
	 */
	public function newDocumentReplicationExaminer( ?Store $store = null ): DocumentReplicationExaminer {
		$applicationFactory = ApplicationFactory::getInstance();

		if ( $store === null ) {
			$store = $applicationFactory->getStore();
		}

		$documentReplicationExaminer = new DocumentReplicationExaminer(
			$store,
			$this->newReplicationStatus( $store->getConnection( 'elastic' ) )
		);

		return $documentReplicationExaminer;
	}

	/**
	 * @since 3.1
	 *
	 * @param Store|null $store
	 *
	 * @return ReplicationCheck
	 */
	public function newReplicationCheck( ?Store $store = null ): ReplicationCheck {
		$applicationFactory = ApplicationFactory::getInstance();

		if ( $store === null ) {
			$store = $applicationFactory->getStore();
		}

		$connection = $store->getConnection( 'elastic' );
		$config = $connection->getConfig();

		$replicationCheck = new ReplicationCheck(
			$store,
			$this->newDocumentReplicationExaminer( $store ),
			$applicationFactory->getEntityCache()
		);

		$replicationCheck->setCacheTTL(
			$config->dotGet( 'indexer.monitor.entity.replication.cache_lifetime' )
		);

		return $replicationCheck;
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 *
	 * @return QueryEngine
	 */
	public function newQueryEngine( Store $store ): QueryEngine {
		$applicationFactory = ApplicationFactory::getInstance();
		$config = $store->getConnection( 'elastic' )->getConfig();

		$queryOptions = new Options(
			$config->safeGet( 'query', [] )
		);

		$termsLookup = new CachingTermsLookup(
			new TermsLookup( $store, $queryOptions ),
			$applicationFactory->getCache()
		);

		$servicesContainer = new ServicesContainer(
			[
				'ConceptDescriptionInterpreter' => [ $this, 'newConceptDescriptionInterpreter' ],
				'SomePropertyInterpreter' => [ $this, 'newSomePropertyInterpreter' ],
				'ClassDescriptionInterpreter' => [ $this, 'newClassDescriptionInterpreter' ],
				'NamespaceDescriptionInterpreter' => [ $this, 'newNamespaceDescriptionInterpreter' ],
				'ValueDescriptionInterpreter' => [ $this, 'newValueDescriptionInterpreter' ],
				'ConjunctionInterpreter' => [ $this, 'newConjunctionInterpreter' ],
				'DisjunctionInterpreter' => [ $this, 'newDisjunctionInterpreter' ],
				'SomeValueInterpreter' => [ $this, 'newSomeValueInterpreter' ]
			]
		);

		$conditionBuilder = new ConditionBuilder(
			$store,
			$termsLookup,
			$applicationFactory->newHierarchyLookup(),
			$servicesContainer
		);

		$conditionBuilder->setOptions( $queryOptions );

		$queryEngine = new QueryEngine(
			$store,
			$conditionBuilder,
			$config
		);

		$queryEngine->setLogger(
			$applicationFactory->getMediaWikiLogger( 'smw-elastic' )
		);

		return $queryEngine;
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 *
	 * @return Rebuilder
	 */
	public function newRebuilder( Store $store ): Rebuilder {
		$connection = $store->getConnection( 'elastic' );
		$indexer = $this->newIndexer( $store );

		$rebuilder = new Rebuilder(
			$connection,
			$indexer,
			$this->newFileIndexer( $store, $indexer ),
			$this->newDocumentCreator( $store ),
			$this->newInstaller( $connection )
		);

		return $rebuilder;
	}

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param MessageReporter $messageReporter
	 *
	 * @return UpdateEntityCollationComplete
	 */
	public function newUpdateEntityCollationComplete( Store $store, MessageReporter $messageReporter ): UpdateEntityCollationComplete {
		$updateEntityCollationComplete = new UpdateEntityCollationComplete(
			$store
		);

		$updateEntityCollationComplete->setMessageReporter(
			$messageReporter
		);

		return $updateEntityCollationComplete;
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 *
	 * @return ElasticClientTaskHandler
	 */
	public function newInfoTaskHandler( Store $store, $outputFormatter ): ElasticClientTaskHandler {
		$applicationFactory = ApplicationFactory::getInstance();

		$replicationInfoProvider = new ReplicationInfoProvider(
			$outputFormatter,
			$this->newReplicationCheck( $store ),
			$applicationFactory->getEntityCache()
		);

		$taskHandlers = [
			new SettingsInfoProvider( $outputFormatter ),
			new MappingsInfoProvider( $outputFormatter ),
			new IndicesInfoProvider( $outputFormatter ),
			new NodesInfoProvider( $outputFormatter ),
			$replicationInfoProvider
		];

		return new ElasticClientTaskHandler( $outputFormatter, $taskHandlers );
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return ConceptDescriptionInterpreter
	 */
	public function newConceptDescriptionInterpreter( ConditionBuilder $containerBuilder ): ConceptDescriptionInterpreter {
		return new ConceptDescriptionInterpreter(
			$containerBuilder,
			ApplicationFactory::getInstance()->getQueryFactory()->newQueryParser()
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return SomePropertyInterpreter
	 */
	public function newSomePropertyInterpreter( ConditionBuilder $containerBuilder ): SomePropertyInterpreter {
		return new SomePropertyInterpreter( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return ClassDescriptionInterpreter
	 */
	public function newClassDescriptionInterpreter( ConditionBuilder $containerBuilder ): ClassDescriptionInterpreter {
		return new ClassDescriptionInterpreter( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return NamespaceDescriptionInterpreter
	 */
	public function newNamespaceDescriptionInterpreter( ConditionBuilder $containerBuilder ): NamespaceDescriptionInterpreter {
		return new NamespaceDescriptionInterpreter( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return ValueDescriptionInterpreter
	 */
	public function newValueDescriptionInterpreter( ConditionBuilder $containerBuilder ): ValueDescriptionInterpreter {
		return new ValueDescriptionInterpreter( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return SomeValueInterpreter
	 */
	public function newSomeValueInterpreter( ConditionBuilder $containerBuilder ): SomeValueInterpreter {
		return new SomeValueInterpreter( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return ConjunctionInterpreter
	 */
	public function newConjunctionInterpreter( ConditionBuilder $containerBuilder ): ConjunctionInterpreter {
		return new ConjunctionInterpreter( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return DisjunctionInterpreter
	 */
	public function newDisjunctionInterpreter( ConditionBuilder $containerBuilder ): DisjunctionInterpreter {
		return new DisjunctionInterpreter( $containerBuilder );
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::SQLStore::EntityReferenceCleanUpComplete
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function onEntityReferenceCleanUpComplete( Store $store, $id, $subject, $isRedirect ): bool {
		if ( !$store instanceof ElasticStore || $store->getConnection( 'elastic' ) instanceof DummyClient ) {
			return true;
		}

		if ( $this->indexer === null ) {
			$this->indexer = $this->newIndexer( $store );
		}

		$this->indexer->setOrigin( __METHOD__ );
		$this->indexer->delete( [ $id ] );

		return true;
	}

	/**
	 * @since 3.1
	 *
	 * @param DispatchContext $dispatchContext
	 */
	public function onInvalidateEntityCache( $dispatchContext ): bool {
		$store = ApplicationFactory::getInstance()->getStore();

		if ( !$store instanceof ElasticStore ) {
			return true;
		}

		if ( $dispatchContext->has( 'subject' ) ) {
			$subject = $dispatchContext->get( 'subject' );
		} else {
			$subject = $dispatchContext->get( 'title' );
		}

		$replicationCheck = $this->newReplicationCheck(
			$store
		);

		$replicationCheck->deleteReplicationTrail(
			$subject
		);

		return true;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Event::RegisterEventListeners
	 * @since 3.1
	 */
	public function onRegisterEventListeners( $eventListener ): bool {
		$eventListener->registerCallback( 'InvalidateEntityCache', [ $this, 'onInvalidateEntityCache' ] );

		return true;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Maintenance::AfterUpdateEntityCollationComplete
	 * @since 3.1
	 */
	public function onAfterUpdateEntityCollationComplete( $store, MessageReporter $messageReporter ): bool {
		if (
			( $connection = $store->getConnection( 'elastic' ) ) === null ||
			$connection instanceof DummyClient ) {
			return true;
		}

		$rebuilder = $this->newRebuilder(
			$store
		);

		$rebuilder->setMessageReporter(
			$messageReporter
		);

		$updateEntityCollationComplete = $this->newUpdateEntityCollationComplete(
			$store,
			$messageReporter
		);

		$updateEntityCollationComplete->runUpdate(
			$rebuilder
		);

		return true;
	}

}
