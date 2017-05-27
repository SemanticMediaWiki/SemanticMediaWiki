<?php

namespace SMW;

use Closure;
use Onoi\CallbackContainer\ContainerBuilder;
use Onoi\CallbackContainer\CallbackContainerFactory;
use Parser;
use ParserOutput;
use SMW\Maintenance\MaintenanceFactory;
use SMW\MediaWiki\Jobs\JobFactory;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\TitleCreator;
use SMW\Query\ProfileAnnotator\QueryProfileAnnotatorFactory;
use SMWQueryParser as QueryParser;
use Title;
use SMW\Services\SharedServicesContainer;

/**
 * Application instances access for internal and external use
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ApplicationFactory {

	/**
	 * @var ApplicationFactory
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

		if ( self::$instance !== null ) {
			self::$instance->getSettings()->clear();
		}

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
	 * @param string $serviceName
	 *
	 * @return mixed
	 */
	public function singleton( $serviceName ) {
		return call_user_func_array( array( $this->containerBuilder, 'singleton' ), func_get_args() );
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
	 *
	 * @return mixed
	 */
	public function create( $serviceName ) {
		return call_user_func_array( array( $this->containerBuilder, 'create' ), func_get_args() );
	}

	/**
	 * @since 2.0
	 *
	 * @return SerializerFactory
	 */
	public function newSerializerFactory() {
		return new SerializerFactory();
	}

	/**
	 * @since 2.0
	 *
	 * @return JobFactory
	 */
	public function newJobFactory() {
		return $this->containerBuilder->create( 'JobFactory' );
	}

	/**
	 * @since 2.1
	 *
	 * @param Parser $parser
	 *
	 * @return ParserFunctionFactory
	 */
	public function newParserFunctionFactory( Parser $parser ) {
		return new ParserFunctionFactory( $parser );
	}

	/**
	 * @since 2.2
	 *
	 * @return MaintenanceFactory
	 */
	public function newMaintenanceFactory() {
		return new MaintenanceFactory();
	}

	/**
	 * @since 2.2
	 *
	 * @return CacheFactory
	 */
	public function newCacheFactory() {
		return $this->containerBuilder->create( 'CacheFactory', $this->getSettings()->get( 'smwgCacheType' ) );
	}

	/**
	 * @since 2.2
	 *
	 * @return CacheFactory
	 */
	public function getCacheFactory() {
		return $this->containerBuilder->singleton( 'CacheFactory', $this->getSettings()->get( 'smwgCacheType' ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param string|null $source
	 *
	 * @return QuerySourceFactory
	 */
	public function getQuerySourceFactory( $source = null ) {
		return $this->containerBuilder->singleton( 'QuerySourceFactory' );
	}

	/**
	 * @since 2.0
	 *
	 * @return Store
	 */
	public function getStore( $store = null ) {
		return $this->containerBuilder->singleton( 'Store', $store );
	}

	/**
	 * @since 2.0
	 *
	 * @return Settings
	 */
	public function getSettings() {
		return $this->containerBuilder->singleton( 'Settings' );
	}

	/**
	 * @since 2.0
	 *
	 * @return TitleCreator
	 */
	public function newTitleCreator() {
		return $this->containerBuilder->create( 'TitleCreator', $this->newPageCreator() );
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
	 *
	 * @return PageUpdater
	 */
	public function newPageUpdater() {

		$pageUpdater = $this->containerBuilder->create(
			'PageUpdater',
			$this->getStore()->getConnection( 'mw.db' ),
			$this->newTransactionalDeferredCallableUpdate()
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
	 *
	 * @return IteratorFactory
	 */
	public function getIteratorFactory() {
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
	 * @since 2.0
	 *
	 * @return InTextAnnotationParser
	 */
	public function newInTextAnnotationParser( ParserData $parserData ) {

		$mwCollaboratorFactory = $this->newMwCollaboratorFactory();

		$linksProcessor = $this->containerBuilder->create( 'LinksProcessor' );

		$linksProcessor->isStrictMode(
			$this->getSettings()->get( 'smwgEnabledInTextAnnotationParserStrictMode' )
		);

		$inTextAnnotationParser = new InTextAnnotationParser(
			$parserData,
			$linksProcessor,
			$mwCollaboratorFactory->newMagicWordsFinder(),
			$mwCollaboratorFactory->newRedirectTargetFinder()
		);

		// 2.5+ Changed modus operandi
		$linksInValues = $this->getSettings()->get( 'smwgLinksInValues' );

		$inTextAnnotationParser->enabledLinksInValues(
			$linksInValues === true ? SMW_LINV_PCRE : $linksInValues
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
	 * @return ContentParser
	 */
	public function newContentParser( Title $title ) {
		return $this->containerBuilder->create( 'ContentParser', $title );
	}

	/**
	 * @since 2.1
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return StoreUpdater
	 */
	public function newStoreUpdater( SemanticData $semanticData ) {
		return new StoreUpdater( $this->getStore(), $semanticData );
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
	 *
	 * @return NamespaceExaminer
	 */
	public function getNamespaceExaminer() {
		return $this->containerBuilder->create( 'NamespaceExaminer' );
	}

	/**
	 * @since 2.4
	 *
	 * @return PropertySpecificationLookup
	 */
	public function getPropertySpecificationLookup() {
		return $this->containerBuilder->singleton( 'PropertySpecificationLookup' );
	}

	/**
	 * @since 2.4
	 *
	 * @return PropertyHierarchyLookup
	 */
	public function newPropertyHierarchyLookup() {
		return $this->containerBuilder->create( 'PropertyHierarchyLookup' );
	}

	/**
	 * @since 2.5
	 *
	 * @return PropertyLabelFinder
	 */
	public function getPropertyLabelFinder() {
		return $this->containerBuilder->singleton( 'PropertyLabelFinder' );
	}

	/**
	 * @since 2.4
	 *
	 * @return CachedPropertyValuesPrefetcher
	 */
	public function getCachedPropertyValuesPrefetcher() {
		return $this->containerBuilder->singleton( 'CachedPropertyValuesPrefetcher' );
	}

	/**
	 * @since 2.4
	 *
	 * @return MediaWikiNsContentReader
	 */
	public function getMediaWikiNsContentReader() {
		return $this->containerBuilder->singleton( 'MediaWikiNsContentReader' );
	}

	/**
	 * @since 2.4
	 *
	 * @return InMemoryPoolCache
	 */
	public function getInMemoryPoolCache() {
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
	 * @param Closure $callback
	 *
	 * @return DeferredCallableUpdate
	 */
	public function newDeferredCallableUpdate( Closure $callback = null ) {

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
			$GLOBALS['wgCommandLineMode']
		);

		return $deferredCallableUpdate;
	}

	/**
	 * @since 3.0
	 *
	 * @param Closure $callback
	 *
	 * @return TransactionalDeferredCallableUpdate
	 */
	public function newTransactionalDeferredCallableUpdate( Closure $callback = null ) {

		$transactionalDeferredCallableUpdate = $this->containerBuilder->create(
			'TransactionalDeferredCallableUpdate',
			$callback,
			$this->getStore()->getConnection( 'mw.db' )
		);

		$transactionalDeferredCallableUpdate->isDeferrableUpdate(
			$this->getSettings()->get( 'smwgEnabledDeferredUpdate' )
		);

		$transactionalDeferredCallableUpdate->setLogger(
			$this->getMediaWikiLogger()
		);

		$transactionalDeferredCallableUpdate->isCommandLineMode(
			$GLOBALS['wgCommandLineMode']
		);

		return $transactionalDeferredCallableUpdate;
	}

	/**
	 * @deprecated since 2.5, use QueryFactory::newQueryParser
	 * @since 2.1
	 *
	 * @return QueryParser
	 */
	public function newQueryParser() {
		return $this->getQueryFactory()->newQueryParser();
	}

	/**
	 * @since 2.5
	 *
	 * @return DataItemFactory
	 */
	public function getDataItemFactory() {
		return $this->containerBuilder->singleton( 'DataItemFactory' );
	}

	/**
	 * @since 2.5
	 *
	 * @return QueryFactory
	 */
	public function getQueryFactory() {
		return $this->containerBuilder->singleton( 'QueryFactory' );
	}

	/**
	 * @since 2.5
	 *
	 * @return LoggerInterface
	 */
	public function getMediaWikiLogger() {
		return $this->containerBuilder->singleton( 'MediaWikiLogger' );
	}

	private static function newContainerBuilder( CallbackContainerFactory $callbackContainerFactory, $servicesFileDir ) {

		$containerBuilder = $callbackContainerFactory->newCallbackContainerBuilder();

		$containerBuilder->registerCallbackContainer( new SharedServicesContainer() );
		$containerBuilder->registerFromFile( $servicesFileDir . '/' . 'MediaWikiServices.php' );
		$containerBuilder->registerFromFile( $servicesFileDir . '/' . 'ImporterServices.php' );

		//	$containerBuilder = $callbackContainerFactory->newLoggableContainerBuilder(
		//		$containerBuilder,
		//		$callbackContainerFactory->newBacktraceSniffer( 10 ),
		//		$callbackContainerFactory->newCallFuncMemorySniffer()
		//	);
		//	$containerBuilder->setLogger( $containerBuilder->singleton( 'MediaWikiLogger' ) );

		return $containerBuilder;
	}

}
