<?php

namespace SMW\Services;

use Onoi\Cache\Cache;
use Onoi\CallbackContainer\CallbackContainerFactory;
use Onoi\CallbackContainer\ContainerBuilder;
use Onoi\EventDispatcher\EventDispatcher;
use ParserOutput;
use Psr\Log\LoggerInterface;
use SMW\CacheFactory;
use SMW\Connection\ConnectionManager;
use SMW\ContentParser;
use SMW\DataItemFactory;
use SMW\DataUpdater;
use SMW\DataValueFactory;
use SMW\DeferredTransactionalCallableUpdate;
use SMW\EntityCache;
use SMW\Factbox\FactboxText;
use SMW\Listener\EventListener\EventHandler;
use SMW\HierarchyLookup;
use SMW\InMemoryPoolCache;
use SMW\InTextAnnotationParser;
use SMW\IteratorFactory;
use SMW\Maintenance\MaintenanceFactory;
use SMW\MediaWiki\Deferred\CallableUpdate;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\PageUpdater;
use SMW\MediaWiki\TitleFactory;
use SMW\MediaWiki\HookDispatcher;
use SMW\NamespaceExaminer;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Preference\PreferenceExaminer;
use SMW\SQLStore\RedirectStore;
use SMW\ParserData;
use SMW\ParserFunctionFactory;
use SMW\PostProcHandler;
use SMW\Property\AnnotatorFactory;
use SMW\PropertyLabelFinder;
use SMW\PropertySpecificationLookup;
use SMW\Query\QuerySourceFactory;
use SMW\QueryFactory;
use SMW\Schema\SchemaFactory;
use SMW\SemanticData;
use SMW\SerializerFactory;
use SMW\Settings;
use SMW\Site;
use SMW\Store;
use SMWQueryParser as QueryParser;
use Title;

/**
 * Application instances access for internal and external use
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ServicesFactory {

	/**
	 * @var ServicesFactory
	 */
	private static $instance = null;

	/**
	 * @var ContainerBuilder
	 */
	private $containerBuilder;

	/**
	 * @var string
	 */
	private $servicesFileDir = '';

	/**
	 * @since 2.0
	 *
	 * @param ContainerBuilder|null $containerBuilder
	 * @param string $servicesFileDir
	 */
	public function __construct( ContainerBuilder $containerBuilder = null, $servicesFileDir = '' ) {
		$this->containerBuilder = $containerBuilder;
		$this->servicesFileDir = $servicesFileDir;
	}

	/**
	 * This method returns the global instance of the application factory.
	 *
	 * Reliance on global state is needed at entry points into SMW such as
	 * hook handlers, special pages and jobs, since there we tend to not
	 * have control over the object lifecycle. Pragmatically we might also
	 * want to use this when refactoring legacy code that already has the
	 * global state dependency. For new code very special justification is
	 * required to rely on global state.
	 *
	 * @since 2.0
	 *
	 * @return self
	 */
	public static function getInstance() {

		if ( self::$instance !== null ) {
			return self::$instance;
		}

		$servicesFileDir = $GLOBALS['smwgServicesFileDir'];

		$containerBuilder = self::newContainerBuilder(
			new CallbackContainerFactory(),
			$servicesFileDir
		);

		return self::$instance = new self( $containerBuilder, $servicesFileDir );
	}

	/**
	 * @since 2.0
	 */
	public static function clear() {
		self::$instance = null;
	}

	/**
	 * @since 2.0
	 *
	 * @param string $objectName
	 * @param callable|array $objectSignature
	 */
	public function registerObject( $objectName, $objectSignature ) {
		$this->containerBuilder->registerObject( $objectName, $objectSignature );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $file
	 */
	public function registerFromFile( $file ) {
		$this->containerBuilder->registerFromFile( $file );
	}

	/**
	 * @private
	 *
	 * @note Services called via this function are for internal use only and
	 * not to be relied upon for external access.
	 *
	 *
	 * @param string $service
	 *
	 * @return mixed
	 */
	public function singleton( ...$service ) {
		return $this->containerBuilder->singleton( ...$service );
	}

	/**
	 * @private
	 *
	 * @note Services called via this function are for internal use only and
	 * not to be relied upon for external access.
	 *
	 * @since 2.5
	 *
	 * @param string $service
	 *
	 * @return mixed
	 */
	public function create( ...$service ) {
		return $this->containerBuilder->create( ...$service );
	}

	/**
	 * @since 3.2
	 *
	 * @param User|null $user
	 *
	 * @return PermissionExaminer
	 */
	public function newPermissionExaminer( \User $user = null ) : PermissionExaminer {
		return new PermissionExaminer( $this->containerBuilder->create( 'PermissionManager' ), $user );
	}

	/**
	 * @since 3.2
	 *
	 * @param User|null $user
	 *
	 * @return PreferenceExaminer
	 */
	public function newPreferenceExaminer( \User $user = null ) : PreferenceExaminer {
		return $this->containerBuilder->create( 'PreferenceExaminer', $user );
	}

	/**
	 * @since 2.0
	 */
	public function newSerializerFactory() : SerializerFactory {
		return new SerializerFactory();
	}

	/**
	 * @since 2.0
	 */
	public function newJobFactory() : JobFactory {
		return $this->containerBuilder->create( 'JobFactory' );
	}

	/**
	 * @since 2.1
	 */
	public function newParserFunctionFactory() : ParserFunctionFactory {
		return new ParserFunctionFactory();
	}

	/**
	 * @since 2.2
	 */
	public function newMaintenanceFactory() : MaintenanceFactory {
		return new MaintenanceFactory();
	}

	/**
	 * @since 2.2
	 */
	public function newCacheFactory() : CacheFactory {
		return $this->containerBuilder->create( 'CacheFactory', $this->getSettings()->get( 'smwgMainCacheType' ) );
	}

	/**
	 * @since 2.2
	 */
	public function getCacheFactory() : CacheFactory {
		return $this->containerBuilder->singleton( 'CacheFactory', $this->getSettings()->get( 'smwgMainCacheType' ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param string|null $source
	 *
	 * @return QuerySourceFactory
	 */
	public function getQuerySourceFactory( $source = null ) : QuerySourceFactory {
		return $this->containerBuilder->singleton( 'QuerySourceFactory' );
	}

	/**
	 * @since 2.0
	 *
	 * @return Store
	 */
	public function getStore( $store = null ) : Store {
		return $this->containerBuilder->singleton( 'Store', $store );
	}

	/**
	 * @since 2.0
	 *
	 * @return Settings
	 */
	public function getSettings() : Settings {
		return $this->containerBuilder->singleton( 'Settings' );
	}

	/**
	 * @since 3.0
	 *
	 * @return ConnectionManager
	 */
	public function getConnectionManager() : ConnectionManager {
		return $this->containerBuilder->singleton( 'ConnectionManager' );
	}

	/**
	 * @since 3.1
	 *
	 * @return EventDispatcher
	 */
	public function getEventDispatcher() : EventDispatcher {
		return EventHandler::getInstance()->getEventDispatcher();
	}

	/**
	 * @since 3.2
	 *
	 * @return HookDispatcher
	 */
	public function getHookDispatcher() : HookDispatcher {
		return $this->containerBuilder->singleton( 'HookDispatcher' );
	}

	/**
	 * @since 2.0
	 */
	public function newTitleFactory() : TitleFactory {
		return $this->containerBuilder->create( 'TitleFactory', $this->newPageCreator() );
	}

	/**
	 * @since 2.0
	 *
	 * @return PageCreator
	 */
	public function newPageCreator() {
		return $this->containerBuilder->create( 'PageCreator' );
	}

	/**
	 * @since 2.5
	 */
	public function newPageUpdater() : PageUpdater {

		$pageUpdater = $this->containerBuilder->create(
			'PageUpdater',
			$this->getStore()->getConnection( 'mw.db' ),
			$this->newDeferredTransactionalCallableUpdate()
		);

		$pageUpdater->setLogger(
			$this->getMediaWikiLogger()
		);

		// https://phabricator.wikimedia.org/T154427
		// It is unclear what changed in MW 1.29 but it has been observed that
		// executing a HTMLCacheUpdate from within an transaction can lead to a
		// "ErrorException ... 1 buffered job ... HTMLCacheUpdateJob never
		// inserted" hence disable the update functionality
		$pageUpdater->isHtmlCacheUpdate(
			false
		);

		return $pageUpdater;
	}

	/**
	 * @since 2.5
	 */
	public function getIteratorFactory() : IteratorFactory {
		return $this->containerBuilder->singleton( 'IteratorFactory' );
	}

	/**
	 * @since 2.5
	 *
	 * @return DataValueFactory
	 */
	public function getDataValueFactory() {
		return DataValueFactory::getInstance();
	}

	/**
	 * @since 2.0
	 *
	 * @return Cache
	 */
	public function getCache( $cacheType = null ) {
		return $this->containerBuilder->singleton( 'Cache', $cacheType );
	}

	/**
	 * @since 3.1
	 */
	public function getEntityCache() : EntityCache {
		return $this->containerBuilder->singleton( 'EntityCache' );
	}

	/**
	 * @since 2.0
	 *
	 * @return InTextAnnotationParser
	 */
	public function newInTextAnnotationParser( ParserData $parserData ) {

		$mwCollaboratorFactory = $this->newMwCollaboratorFactory();

		$linksProcessor = $this->containerBuilder->create( 'LinksProcessor' );
		$settings = $this->getSettings();

		$linksProcessor->isStrictMode(
			$settings->isFlagSet( 'smwgParserFeatures', SMW_PARSER_STRICT )
		);

		$inTextAnnotationParser = new InTextAnnotationParser(
			$parserData,
			$linksProcessor,
			$mwCollaboratorFactory->newMagicWordsFinder(),
			$mwCollaboratorFactory->newRedirectTargetFinder()
		);

		$inTextAnnotationParser->isLinksInValues(
			$settings->isFlagSet( 'smwgParserFeatures', SMW_PARSER_LINV )
		);

		$inTextAnnotationParser->showErrors(
			$settings->isFlagSet( 'smwgParserFeatures', SMW_PARSER_INL_ERROR )
		);

		$inTextAnnotationParser->setHookDispatcher(
			$this->getHookDispatcher()
		);

		return $inTextAnnotationParser;
	}

	/**
	 * @since 2.0
	 *
	 * @return ParserData
	 */
	public function newParserData( Title $title, ParserOutput $parserOutput ) {
		return $this->containerBuilder->create( 'ParserData', $title, $parserOutput );
	}

	/**
	 * @since 2.0
	 *
	 * @param Title $title
	 *
	 * @return ContentParser
	 */
	public function newContentParser( Title $title ) : ContentParser {
		return $this->containerBuilder->create( 'ContentParser', $title );
	}

	/**
	 * @since 2.1
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return DataUpdater
	 */
	public function newDataUpdater( SemanticData $semanticData ) {

		$settings = $this->getSettings();

		$changePropagationNotifier = new \SMW\Property\ChangePropagationNotifier(
			$this->getStore(),
			$this->newSerializerFactory()
		);

		$changePropagationNotifier->setPropertyList(
			$settings->get( 'smwgChangePropagationWatchlist' )
		);

		$changePropagationNotifier->isCommandLineMode(
			Site::isCommandLineMode()
		);

		$dataUpdater = new DataUpdater(
			$this->getStore(),
			$semanticData,
			$changePropagationNotifier
		);

		$dataUpdater->isCommandLineMode(
			Site::isCommandLineMode()
		);

		$dataUpdater->setLogger(
			$this->getMediaWikiLogger()
		);

		$dataUpdater->setEventDispatcher(
			$this->getEventDispatcher()
		);

		$dataUpdater->setRevisionGuard(
			$this->singleton( 'RevisionGuard' )
		);

		return $dataUpdater;
	}

	/**
	 * @since 2.1
	 *
	 * @return MwCollaboratorFactory
	 */
	public function newMwCollaboratorFactory() {
		return new MwCollaboratorFactory( $this );
	}

	/**
	 * @since 2.1
	 */
	public function getNamespaceExaminer() : NamespaceExaminer {
		return $this->containerBuilder->create( 'NamespaceExaminer' );
	}

	/**
	 * @since 2.4
	 */
	public function getPropertySpecificationLookup() : PropertySpecificationLookup {
		return $this->containerBuilder->singleton( 'PropertySpecificationLookup' );
	}

	/**
	 * @since 2.4
	 */
	public function newHierarchyLookup() : HierarchyLookup {
		return $this->containerBuilder->create( 'HierarchyLookup' );
	}

	/**
	 * @since 2.5
	 */
	public function getPropertyLabelFinder() : PropertyLabelFinder {
		return $this->containerBuilder->singleton( 'PropertyLabelFinder' );
	}

	/**
	 * @since 2.4
	 */
	public function getMediaWikiNsContentReader() : MediaWikiNsContentReader {
		return $this->containerBuilder->singleton( 'MediaWikiNsContentReader' );
	}

	/**
	 * @since 2.4
	 */
	public function getInMemoryPoolCache() : InMemoryPoolCache {
		return $this->containerBuilder->singleton( 'InMemoryPoolCache' );
	}

	/**
	 * @since 2.5
	 *
	 * @return \createBalancer
	 */
	public function getLoadBalancer() {
		return $this->containerBuilder->singleton( 'DBLoadBalancer' );
	}

	/**
	 * @since 2.4
	 *
	 * @param callable $callback
	 */
	public function newDeferredCallableUpdate( callable $callback = null ) : CallableUpdate {

		$deferredCallableUpdate = $this->containerBuilder->create(
			'DeferredCallableUpdate',
			$callback
		);

		$deferredCallableUpdate->isDeferrableUpdate(
			$this->getSettings()->get( 'smwgEnabledDeferredUpdate' )
		);

		$deferredCallableUpdate->setLogger(
			$this->getMediaWikiLogger()
		);

		$deferredCallableUpdate->isCommandLineMode(
			Site::isCommandLineMode()
		);

		return $deferredCallableUpdate;
	}

	/**
	 * @since 3.0
	 *
	 * @param callable $callback
	 */
	public function newDeferredTransactionalCallableUpdate( callable $callback = null ) : DeferredTransactionalCallableUpdate {

		$deferredTransactionalUpdate = $this->containerBuilder->create(
			'DeferredTransactionalCallableUpdate',
			$callback,
			$this->getStore()->getConnection( 'mw.db' )
		);

		$deferredTransactionalUpdate->isDeferrableUpdate(
			$this->getSettings()->get( 'smwgEnabledDeferredUpdate' )
		);

		$deferredTransactionalUpdate->setLogger(
			$this->getMediaWikiLogger()
		);

		$deferredTransactionalUpdate->isCommandLineMode(
			Site::isCommandLineMode()
		);

		return $deferredTransactionalUpdate;
	}

	/**
	 * @deprecated since 2.5, use QueryFactory::newQueryParser
	 * @since 2.1
	 *
	 * @return QueryParser
	 */
	public function newQueryParser( $queryFeatures = false ) {
		return $this->getQueryFactory()->newQueryParser( $queryFeatures );
	}

	/**
	 * @since 2.5
	 */
	public function getDataItemFactory() : DataItemFactory {
		return $this->containerBuilder->singleton( 'DataItemFactory' );
	}

	/**
	 * @since 2.5
	 */
	public function getQueryFactory() : QueryFactory {
		return $this->containerBuilder->singleton( 'QueryFactory' );
	}

	/**
	 * @since 2.5
	 */
	public function getMediaWikiLogger( $channel = 'smw' ) : LoggerInterface {
		return $this->containerBuilder->singleton( 'MediaWikiLogger', $channel, $GLOBALS['smwgDefaultLoggerRole'] );
	}

	/**
	 * @since 3.0
	 */
	public function getJobQueue() : JobQueue {
		return $this->containerBuilder->singleton( 'JobQueue' );
	}

	private static function newContainerBuilder( CallbackContainerFactory $callbackContainerFactory, $servicesFileDir ) {

		$containerBuilder = $callbackContainerFactory->newCallbackContainerBuilder();

		$containerBuilder->registerCallbackContainer( new SharedServicesContainer() );
		$containerBuilder->registerFromFile( $servicesFileDir . '/' . 'mediawiki.php' );
		$containerBuilder->registerFromFile( $servicesFileDir . '/' . 'importer.php' );
		$containerBuilder->registerFromFile( $servicesFileDir . '/' . 'events.php' );
		$containerBuilder->registerFromFile( $servicesFileDir . '/' . 'cache.php' );

		//	$containerBuilder = $callbackContainerFactory->newLoggableContainerBuilder(
		//		$containerBuilder,
		//		$callbackContainerFactory->newBacktraceSniffer( 10 ),
		//		$callbackContainerFactory->newCallFuncMemorySniffer()
		//	);
		//	$containerBuilder->setLogger( $containerBuilder->singleton( 'MediaWikiLogger' ) );

		return $containerBuilder;
	}

	public function newPostProcHandler( ParserOutput $parserOutput ) : PostProcHandler {
		return $this->create( 'PostProcHandler', $parserOutput );
	}

	/**
	 * @since 4.1.1
	 */
	public function getFactboxText() : FactboxText {
		return $this->containerBuilder->singleton( 'FactboxText' );
	}

}
