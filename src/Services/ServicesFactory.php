<?php

namespace SMW\Services;

use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Onoi\Cache\Cache;
use Onoi\CallbackContainer\CallbackContainerBuilder;
use Onoi\CallbackContainer\CallbackContainerFactory;
use Onoi\EventDispatcher\EventDispatcher;
use Psr\Log\LoggerInterface;
use SMW\CacheFactory;
use SMW\Connection\ConnectionManager;
use SMW\ContentParser;
use SMW\DataItemFactory;
use SMW\DataUpdater;
use SMW\DataValueFactory;
use SMW\EntityCache;
use SMW\Factbox\FactboxText;
use SMW\HierarchyLookup;
use SMW\InMemoryPoolCache;
use SMW\IteratorFactory;
use SMW\Listener\EventListener\EventHandler;
use SMW\Maintenance\MaintenanceFactory;
use SMW\MediaWiki\Deferred\CallableUpdate;
use SMW\MediaWiki\Deferred\TransactionalCallableUpdate;
use SMW\MediaWiki\HookDispatcher;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\PageUpdater;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Preference\PreferenceExaminer;
use SMW\MediaWiki\TitleFactory;
use SMW\NamespaceExaminer;
use SMW\Parser\InTextAnnotationParser;
use SMW\ParserData;
use SMW\ParserFunctionFactory;
use SMW\PostProcHandler;
use SMW\Property\ChangePropagationNotifier;
use SMW\Property\SpecificationLookup;
use SMW\PropertyLabelFinder;
use SMW\Query\Parser as QueryParser;
use SMW\Query\QuerySourceFactory;
use SMW\QueryFactory;
use SMW\SemanticData;
use SMW\SerializerFactory;
use SMW\Settings;
use SMW\Site;
use SMW\Store;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * Application instances access for internal and external use
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ServicesFactory {

	/**
	 * @var ServicesFactory|null
	 */
	private static $instance = null;

	/**
	 * @since 2.0
	 */
	public function __construct(
		private readonly ?CallbackContainerBuilder $callbackContainerBuilder = null,
		private $servicesFileDir = '',
	) {
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

		$callbackContainerBuilder = self::newCallbackContainerBuilder(
			new CallbackContainerFactory(),
			$servicesFileDir
		);

		self::$instance = new self( $callbackContainerBuilder, $servicesFileDir );
		return self::$instance;
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
		$this->callbackContainerBuilder->registerObject( $objectName, $objectSignature );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $file
	 */
	public function registerFromFile( $file ) {
		$this->callbackContainerBuilder->registerFromFile( $file );
	}

	/**
	 * @private
	 *
	 * @note Services called via this function are for internal use only and
	 * not to be relied upon for external access.
	 *
	 *
	 * @param string $serviceName
	 * @param mixed ...$args
	 *
	 * @return mixed
	 */
	public function singleton( $serviceName, ...$args ) {
		return $this->callbackContainerBuilder->singleton( $serviceName, ...$args );
	}

	/**
	 * @private
	 *
	 * @note Services called via this function are for internal use only and
	 * not to be relied upon for external access.
	 *
	 * @since 2.5
	 *
	 * @param string $serviceName
	 * @param mixed ...$args
	 *
	 * @return mixed
	 */
	public function create( $serviceName, ...$args ) {
		return $this->callbackContainerBuilder->create( $serviceName, ...$args );
	}

	/**
	 * @since 3.2
	 *
	 * @param User|null $user
	 *
	 * @return PermissionExaminer
	 */
	public function newPermissionExaminer( ?User $user = null ): PermissionExaminer {
		return new PermissionExaminer( $this->callbackContainerBuilder->create( 'PermissionManager' ), $user );
	}

	/**
	 * @since 3.2
	 *
	 * @param User|null $user
	 *
	 * @return PreferenceExaminer
	 */
	public function newPreferenceExaminer( ?User $user = null ): PreferenceExaminer {
		return $this->callbackContainerBuilder->create( 'PreferenceExaminer', $user );
	}

	/**
	 * @since 2.0
	 */
	public function newSerializerFactory(): SerializerFactory {
		return new SerializerFactory();
	}

	/**
	 * @since 2.0
	 */
	public function newJobFactory(): JobFactory {
		return $this->callbackContainerBuilder->create( 'JobFactory' );
	}

	/**
	 * @since 2.1
	 */
	public function newParserFunctionFactory(): ParserFunctionFactory {
		return new ParserFunctionFactory();
	}

	/**
	 * @since 2.2
	 */
	public function newMaintenanceFactory(): MaintenanceFactory {
		return new MaintenanceFactory();
	}

	/**
	 * @since 2.2
	 */
	public function newCacheFactory(): CacheFactory {
		return $this->callbackContainerBuilder->create( 'CacheFactory', $this->getSettings()->get( 'smwgMainCacheType' ) );
	}

	/**
	 * @since 2.2
	 */
	public function getCacheFactory(): CacheFactory {
		return $this->callbackContainerBuilder->singleton( 'CacheFactory', $this->getSettings()->get( 'smwgMainCacheType' ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param string|null $source
	 *
	 * @return QuerySourceFactory
	 */
	public function getQuerySourceFactory( $source = null ): QuerySourceFactory {
		return $this->callbackContainerBuilder->singleton( 'QuerySourceFactory' );
	}

	/**
	 * @since 2.0
	 *
	 * @return Store
	 */
	public function getStore( $store = null ): Store {
		return $this->callbackContainerBuilder->singleton( 'Store', $store );
	}

	/**
	 * @since 2.0
	 *
	 * @return Settings
	 */
	public function getSettings(): Settings {
		return $this->callbackContainerBuilder->singleton( 'Settings' );
	}

	/**
	 * @since 3.0
	 *
	 * @return ConnectionManager
	 */
	public function getConnectionManager(): ConnectionManager {
		return $this->callbackContainerBuilder->singleton( 'ConnectionManager' );
	}

	/**
	 * @since 3.1
	 *
	 * @return EventDispatcher
	 */
	public function getEventDispatcher(): EventDispatcher {
		return EventHandler::getInstance()->getEventDispatcher();
	}

	/**
	 * @since 3.2
	 *
	 * @return HookDispatcher
	 */
	public function getHookDispatcher(): HookDispatcher {
		return $this->callbackContainerBuilder->singleton( 'HookDispatcher' );
	}

	/**
	 * @since 2.0
	 */
	public function newTitleFactory(): TitleFactory {
		return $this->callbackContainerBuilder->create( 'TitleFactory', $this->newPageCreator() );
	}

	/**
	 * @since 2.0
	 *
	 * @return PageCreator
	 */
	public function newPageCreator() {
		return $this->callbackContainerBuilder->create( 'PageCreator' );
	}

	/**
	 * @since 2.5
	 */
	public function newPageUpdater(): PageUpdater {
		$pageUpdater = $this->callbackContainerBuilder->create(
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
	public function getIteratorFactory(): IteratorFactory {
		return $this->callbackContainerBuilder->singleton( 'IteratorFactory' );
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
		return $this->callbackContainerBuilder->singleton( 'Cache', $cacheType );
	}

	/**
	 * @since 3.1
	 */
	public function getEntityCache(): EntityCache {
		return $this->callbackContainerBuilder->singleton( 'EntityCache' );
	}

	/**
	 * @since 2.0
	 *
	 * @return InTextAnnotationParser
	 */
	public function newInTextAnnotationParser( ParserData $parserData ) {
		$mwCollaboratorFactory = $this->newMwCollaboratorFactory();

		$linksProcessor = $this->callbackContainerBuilder->create( 'LinksProcessor' );
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
		return $this->callbackContainerBuilder->create( 'ParserData', $title, $parserOutput );
	}

	/**
	 * @since 2.0
	 *
	 * @param Title $title
	 *
	 * @return ContentParser
	 */
	public function newContentParser( Title $title ): ContentParser {
		return $this->callbackContainerBuilder->create( 'ContentParser', $title );
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

		$changePropagationNotifier = new ChangePropagationNotifier(
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
	public function getNamespaceExaminer(): NamespaceExaminer {
		return $this->callbackContainerBuilder->create( 'NamespaceExaminer' );
	}

	/**
	 * @since 2.4
	 */
	public function getPropertySpecificationLookup(): SpecificationLookup {
		return $this->callbackContainerBuilder->singleton( 'PropertySpecificationLookup' );
	}

	/**
	 * @since 2.4
	 */
	public function newHierarchyLookup(): HierarchyLookup {
		return $this->callbackContainerBuilder->create( 'HierarchyLookup' );
	}

	/**
	 * @since 2.5
	 */
	public function getPropertyLabelFinder(): PropertyLabelFinder {
		return $this->callbackContainerBuilder->singleton( 'PropertyLabelFinder' );
	}

	/**
	 * @since 2.4
	 */
	public function getMediaWikiNsContentReader(): MediaWikiNsContentReader {
		return $this->callbackContainerBuilder->singleton( 'MediaWikiNsContentReader' );
	}

	/**
	 * @since 2.4
	 */
	public function getInMemoryPoolCache(): InMemoryPoolCache {
		return $this->callbackContainerBuilder->singleton( 'InMemoryPoolCache' );
	}

	/**
	 * @since 2.5
	 *
	 * @return ILoadBalancer
	 */
	public function getLoadBalancer() {
		return $this->callbackContainerBuilder->singleton( 'DBLoadBalancer' );
	}

	/**
	 * @since 2.4
	 *
	 * @param callable|null $callback
	 */
	public function newDeferredCallableUpdate( ?callable $callback = null ): CallableUpdate {
		$deferredCallableUpdate = $this->callbackContainerBuilder->create(
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
	 * @param callable|null $callback
	 */
	public function newDeferredTransactionalCallableUpdate( ?callable $callback = null ): TransactionalCallableUpdate {
		$deferredTransactionalUpdate = $this->callbackContainerBuilder->create(
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
	public function getDataItemFactory(): DataItemFactory {
		return $this->callbackContainerBuilder->singleton( 'DataItemFactory' );
	}

	/**
	 * @since 2.5
	 */
	public function getQueryFactory(): QueryFactory {
		return $this->callbackContainerBuilder->singleton( 'QueryFactory' );
	}

	/**
	 * @since 2.5
	 */
	public function getMediaWikiLogger( $channel = 'smw' ): LoggerInterface {
		return $this->callbackContainerBuilder->singleton( 'MediaWikiLogger', $channel, $GLOBALS['smwgDefaultLoggerRole'] );
	}

	/**
	 * @since 3.0
	 */
	public function getJobQueue(): JobQueue {
		return $this->callbackContainerBuilder->singleton( 'JobQueue' );
	}

	private static function newCallbackContainerBuilder( CallbackContainerFactory $callbackContainerFactory, $servicesFileDir ) {
		$callbackContainerBuilder = $callbackContainerFactory->newCallbackContainerBuilder();

		$callbackContainerBuilder->registerCallbackContainer( new SharedServicesContainer() );
		$callbackContainerBuilder->registerFromFile( $servicesFileDir . '/' . 'mediawiki.php' );
		$callbackContainerBuilder->registerFromFile( $servicesFileDir . '/' . 'importer.php' );
		$callbackContainerBuilder->registerFromFile( $servicesFileDir . '/' . 'events.php' );
		$callbackContainerBuilder->registerFromFile( $servicesFileDir . '/' . 'cache.php' );

		// $callbackContainerBuilder = $callbackContainerFactory->newLoggableContainerBuilder(
		//		$callbackContainerBuilder,
		//		$callbackContainerFactory->newBacktraceSniffer( 10 ),
		//		$callbackContainerFactory->newCallFuncMemorySniffer()
		//	);
		//	$callbackContainerBuilder->setLogger( $callbackContainerBuilder->singleton( 'MediaWikiLogger' ) );

		return $callbackContainerBuilder;
	}

	public function newPostProcHandler( ParserOutput $parserOutput ): PostProcHandler {
		return $this->create( 'PostProcHandler', $parserOutput );
	}

	/**
	 * @since 4.1.1
	 */
	public function getFactboxText(): FactboxText {
		return $this->callbackContainerBuilder->singleton( 'FactboxText' );
	}

}
