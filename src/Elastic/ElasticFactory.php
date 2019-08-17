<?php

namespace SMW\Elastic;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\NullMessageReporter;
use Psr\Log\LoggerInterface;
use SMW\ApplicationFactory;
use SMW\Elastic\Admin\ElasticClientTaskHandler;
use SMW\Elastic\Admin\IndicesInfoProvider;
use SMW\Elastic\Admin\MappingsInfoProvider;
use SMW\Elastic\Admin\NodesInfoProvider;
use SMW\Elastic\Admin\SettingsInfoProvider;
use SMW\Elastic\Admin\ReplicationInfoProvider;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\Connection\LockManager;
use SMW\Elastic\Indexer\Indexer;
use SMW\Elastic\Indexer\FileIndexer;
use SMW\Elastic\Indexer\Rollover;
use SMW\Elastic\Indexer\Rebuilder;
use SMW\Elastic\Indexer\IndicatorProvider;
use SMW\Elastic\Indexer\Bulk;
use SMW\Elastic\Indexer\Replication\ReplicationStatus;
use SMW\Elastic\Indexer\Replication\CheckReplicationTask;
use SMW\Elastic\Indexer\Replication\DocumentReplicationExaminer;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Elastic\QueryEngine\QueryEngine;
use SMW\Elastic\QueryEngine\TermsLookup\CachingTermsLookup;
use SMW\Elastic\QueryEngine\TermsLookup\TermsLookup;
use SMW\Options;
use SMW\SQLStore\PropertyTableRowMapper;
use SMW\Store;
use SMW\Elastic\Connection\ConnectionProvider;
use SMW\Services\ServicesContainer;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ClassDescriptionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ConceptDescriptionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\DisjunctionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\NamespaceDescriptionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\SomePropertyInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter;
use SMW\Elastic\QueryEngine\DescriptionInterpreters\SomeValueInterpreter;
use SMW\Elastic\Lookup\ProximityPropertyValueLookup;
use SMW\Elastic\Hooks\UpdateEntityCollationComplete;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticFactory {

	/**
	 * @var Indexer
	 */
	private $indexer;

	/**
	 * @since 3.0
	 *
	 * @return Config
	 */
	public function newConfig() {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$config = new Config(
			$settings->get( 'smwgElasticsearchConfig' )
		);

		$isElasticstore = strpos( $settings->get( 'smwgDefaultStore' ), 'Elastic' ) !== false;

		$config->set(
			'elastic.enabled',
			$isElasticstore
		);

		$config->set(
			'is.elasticstore',
			$isElasticstore
		);

		$config->set(
			'endpoints',
			$settings->get( 'smwgElasticsearchEndpoints' )
		);

		$config->loadFromJSON(
			$config->readFile( $settings->get( 'smwgElasticsearchProfile' ) )
		);

		return $config;
	}

	/**
	 * @since 3.0
	 *
	 * @return ConnectionProvider
	 */
	public function newConnectionProvider() {

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
	 * @since 3.0
	 *
	 * @param Store $store
	 *
	 * @return ProximityPropertyValueLookup
	 */
	public function newProximityPropertyValueLookup( Store $store ) {
		return new ProximityPropertyValueLookup( $store );
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param MessageReporter|null $messageReporter
	 *
	 * @return Indexer
	 */
	public function newIndexer( Store $store, MessageReporter $messageReporter = null ) {

		$applicationFactory = ApplicationFactory::getInstance();

		// Construction is postponed to the point where it is needed
		$servicesContainer = new ServicesContainer(
			[
				'Rollover'    => [
					'_service' => [ $this, 'newRollover' ],
					'_type' => Rollover::class
				],
				'FileIndexer' => [ $this, 'newFileIndexer' ],
				'Bulk' => [ $this, 'newBulk' ],
			]
		);

		$indexer = new Indexer(
			$store,
			$servicesContainer
		);

		if ( $messageReporter === null ) {
			$messageReporter = new NullMessageReporter();
		}

		$indexer->setLogger(
			$applicationFactory->getMediaWikiLogger( 'smw-elastic' )
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
	public function newRollover( ElasticClient $connection ) {
		return new Rollover( $connection );
	}

	/**
	 * @since 3.0
	 *
	 * @param ElasticClient $connection
	 *
	 * @return Bulk
	 */
	public function newBulk( ElasticClient $connection ) {
		return new Bulk( $connection );
	}

	/**
	 * @since 3.0
	 *
	 * @param Indexer $indexer
	 *
	 * @return FileIndexer
	 */
	public function newFileIndexer( Indexer $indexer ) {
		return new FileIndexer( $indexer );
	}

	/**
	 * @since 3.1
	 *
	 * @param ElasticClient $connection
	 *
	 * @return ReplicationStatus
	 */
	public function newReplicationStatus( ElasticClient $connection ) {
		return new ReplicationStatus( $connection );
	}

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 *
	 * @return DocumentReplicationExaminer
	 */
	public function newDocumentReplicationExaminer( Store $store = null ) {

		$applicationFactory = ApplicationFactory::getInstance();

		if ( $store === null  ) {
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
	 * @param Store $store
	 *
	 * @return CheckReplicationTask
	 */
	public function newCheckReplicationTask( Store $store = null ) {

		$applicationFactory = ApplicationFactory::getInstance();

		if ( $store === null  ) {
			$store = $applicationFactory->getStore();
		}

		$connection = $store->getConnection( 'elastic' );
		$options = $connection->getConfig();

		$checkReplicationTask = new CheckReplicationTask(
			$store,
			$this->newDocumentReplicationExaminer( $store ),
			$applicationFactory->getEntityCache()
		);

		$checkReplicationTask->setCacheTTL(
			$options->dotGet( 'indexer.monitor.entity.replication.cache_lifetime' )
		);

		return $checkReplicationTask;
	}

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 *
	 * @return IndicatorProvider
	 */
	public function newIndicatorProvider( ElasticStore $store ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$options = $store->getConnection( 'elastic' )->getConfig();

		$indicatorProvider = new IndicatorProvider(
			$store,
			$applicationFactory->getEntityCache()
		);

		$indicatorProvider->canCheckReplication(
			$options->dotGet( 'indexer.monitor.entity.replication' )
		);

		return $indicatorProvider;
	}

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 *
	 * @return QueryEngine
	 */
	public function newQueryEngine( Store $store ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$options = $this->newConfig();

		$queryOptions = new Options(
			$options->safeGet( 'query', [] )
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
				'SomeValueInterpreter'  => [ $this, 'newSomeValueInterpreter' ]
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
			$options
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
	public function newRebuilder( Store $store ) {

		$connection = $store->getConnection( 'elastic' );

		$rebuilder = new Rebuilder(
			$connection,
			$this->newIndexer( $store ),
			new PropertyTableRowMapper( $store ),
			$this->newRollover( $connection )
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
	public function newUpdateEntityCollationComplete( Store $store, MessageReporter $messageReporter ) {
		return new UpdateEntityCollationComplete(
			$store,
			$messageReporter
		);
	}


	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 *
	 * @return ElasticClientTaskHandler
	 */
	public function newInfoTaskHandler( Store $store, $outputFormatter ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$replicationInfoProvider = new ReplicationInfoProvider(
			$outputFormatter,
			$this->newCheckReplicationTask( $store ),
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
	public function newConceptDescriptionInterpreter( ConditionBuilder $containerBuilder ) {
		return new ConceptDescriptionInterpreter(
			$containerBuilder,
			ApplicationFactory::getInstance()->newQueryParser()
		);
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return SomePropertyInterpreter
	 */
	public function newSomePropertyInterpreter( ConditionBuilder $containerBuilder ) {
		return new SomePropertyInterpreter( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return ClassDescriptionInterpreter
	 */
	public function newClassDescriptionInterpreter( ConditionBuilder $containerBuilder ) {
		return new ClassDescriptionInterpreter( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return NamespaceDescriptionInterpreter
	 */
	public function newNamespaceDescriptionInterpreter( ConditionBuilder $containerBuilder ) {
		return new NamespaceDescriptionInterpreter( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return ValueDescriptionInterpreter
	 */
	public function newValueDescriptionInterpreter( ConditionBuilder $containerBuilder ) {
		return new ValueDescriptionInterpreter( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return SomeValueInterpreter
	 */
	public function newSomeValueInterpreter( ConditionBuilder $containerBuilder ) {
		return new SomeValueInterpreter( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return ConjunctionInterpreter
	 */
	public function newConjunctionInterpreter( ConditionBuilder $containerBuilder ) {
		return new ConjunctionInterpreter( $containerBuilder );
	}

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $containerBuilder
	 *
	 * @return DisjunctionInterpreter
	 */
	public function newDisjunctionInterpreter( ConditionBuilder $containerBuilder ) {
		return new DisjunctionInterpreter( $containerBuilder );
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::SQLStore::EntityReferenceCleanUpComplete
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function onEntityReferenceCleanUpComplete( Store $store, $id, $subject, $isRedirect ) {

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
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Admin::TaskHandlerFactory
	 * @since 3.0
	 */
	public function onTaskHandlerFactory( &$taskHandlers, Store $store, $outputFormatter, $user ) {

		if ( !$store instanceof ElasticStore ) {
			return true;
		}

		$taskHandlers[] = $this->newInfoTaskHandler(
			$store,
			$outputFormatter
		);

		return true;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Api::AddTasks
	 * @since 3.1
	 */
	public function onApiTasks( &$services ) {
		$services['check-es-replication'] = [ $this, 'newCheckReplicationTask' ];
		return true;
	}

	/**
	 * @since 3.1
	 *
	 * @param DispatchContext $dispatchContext
	 */
	public function onInvalidateEntityCache( $dispatchContext ) {

		$store = ApplicationFactory::getInstance()->getStore();

		if ( !$store instanceof ElasticStore ) {
			return true;
		}

		if ( $dispatchContext->has( 'subject' ) ) {
			$subject = $dispatchContext->get( 'subject' );
		} else {
			$subject = $dispatchContext->get( 'title' );
		}

		$checkReplicationTask = $this->newCheckReplicationTask(
			$store
		);

		$checkReplicationTask->deleteReplicationTrail(
			$subject
		);
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Event::RegisterEventListeners
	 * @since 3.1
	 */
	public function onRegisterEventListeners( $eventListener ) {
		$eventListener->registerCallback( 'InvalidateEntityCache', [ $this, 'onInvalidateEntityCache' ] );

		return true;
	}

	/**
	 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::Maintenance::AfterUpdateEntityCollationComplete
	 * @since 3.1
	 */
	public function onAfterUpdateEntityCollationComplete( $store, $messageReporter ) {

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
