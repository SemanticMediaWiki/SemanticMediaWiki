<?php

namespace SMW\Services;

use JobQueueGroup;
use MediaWiki\Config\Config;
use MediaWiki\Language\Language;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\MagicWordFactory;
use MediaWiki\Parser\ParserCache;
use MediaWiki\User\Options\UserOptionsLookup;
use SearchEngineConfig;
use SMW\Connection\ConnectionManager;
use SMW\ConstraintFactory;
use SMW\DataItemFactory;
use SMW\Elastic\ElasticFactory;
use SMW\EntityCache;
use SMW\Factbox\FactboxFactory;
use SMW\Factbox\FactboxText;
use SMW\InMemoryPoolCache;
use SMW\IteratorFactory;
use SMW\Listener\EventListener\EventListeners\InvalidateEntityCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidateResultCacheEventListener;
use SMW\Localizer\Localizer;
use SMW\MediaWiki\Connection\ConnectionProvider;
use SMW\MediaWiki\FileRepoFinder;
use SMW\MediaWiki\HookDispatcher;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\ManualEntryLogger;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\Permission\TitlePermissions;
use SMW\MediaWiki\PermissionManager;
use SMW\MediaWiki\RevisionGuard;
use SMW\Property\AnnotatorFactory;
use SMW\Property\SpecificationLookup;
use SMW\PropertyLabelFinder;
use SMW\Protection\ProtectionValidator;
use SMW\Query\Processor\ParamListProcessor;
use SMW\Query\Processor\QueryCreator;
use SMW\Query\QuerySourceFactory;
use SMW\QueryFactory;
use SMW\Schema\SchemaFactory;
use SMW\Settings;
use SMW\SetupFile;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\Services\ServiceContainer;

/**
 * Wiring file for SMW's private ServiceContainer.
 *
 * Holds the Bucket-A services (no-argument, stateless) of the
 * callback-container migration. Keys are plain names (no prefix) matching
 * the former onoi container keys so that `singleton('Name')` shims are 1:1.
 *
 * Each callback receives SMW's private ServiceContainer as its first
 * argument. Dependency resolution rules:
 * - Sibling SMW service (defined in this file): `$container->getService('Name')`
 * - MediaWiki-core service: `MediaWikiServices::getInstance()->getXxx()`
 * - Bucket-B/C SMW service (factory method): `ServicesFactory::getInstance()->singleton('Name')`
 *
 * Services that take runtime arguments or are constructed fresh per use
 * (Bucket B and Bucket C) are not registered here; those remain factory
 * methods on ServicesFactory.
 *
 * @codeCoverageIgnore
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
return [

	'MainConfig' => static function ( ServiceContainer $container ): Config {
		return MediaWikiServices::getInstance()->getMainConfig();
	},

	'SearchEngineConfig' => static function ( ServiceContainer $container ): SearchEngineConfig {
		return MediaWikiServices::getInstance()->getSearchEngineConfig();
	},

	'MagicWordFactory' => static function ( ServiceContainer $container ): MagicWordFactory {
		return MediaWikiServices::getInstance()->getMagicWordFactory();
	},

	'PermissionManager' => static function ( ServiceContainer $container ): PermissionManager {
		return new PermissionManager( MediaWikiServices::getInstance()->getPermissionManager() );
	},

	'DBLoadBalancerFactory' => static function ( ServiceContainer $container ): LBFactory {
		return MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
	},

	'DBLoadBalancer' => static function ( ServiceContainer $container ): ILoadBalancer {
		return MediaWikiServices::getInstance()->getDBLoadBalancer();
	},

	'FileRepoFinder' => static function ( ServiceContainer $container ): FileRepoFinder {
		return new FileRepoFinder( MediaWikiServices::getInstance()->getRepoGroup() );
	},

	'JobQueueGroup' => static function ( ServiceContainer $container ): JobQueueGroup {
		return MediaWikiServices::getInstance()->getJobQueueGroup();
	},

	'ContentLanguage' => static function ( ServiceContainer $container ): Language {
		return MediaWikiServices::getInstance()->getContentLanguage();
	},

	'ParserCache' => static function ( ServiceContainer $container ): ParserCache {
		return MediaWikiServices::getInstance()->getParserCache();
	},

	'UserOptionsLookup' => static function ( ServiceContainer $container ): UserOptionsLookup {
		return MediaWikiServices::getInstance()->getUserOptionsLookup();
	},

	'InvalidateResultCacheEventListener' => static function ( ServiceContainer $container ): InvalidateResultCacheEventListener {
		return new InvalidateResultCacheEventListener(
			ServicesFactory::getInstance()->singleton( 'ResultCache' )
		);
	},

	'InvalidateEntityCacheEventListener' => static function ( ServiceContainer $container ): InvalidateEntityCacheEventListener {
		return new InvalidateEntityCacheEventListener(
			$container->getService( 'EntityCache' )
		);
	},

	'InvalidatePropertySpecificationLookupCacheEventListener' => static function ( ServiceContainer $container ): InvalidatePropertySpecificationLookupCacheEventListener {
		return new InvalidatePropertySpecificationLookupCacheEventListener(
			$container->getService( 'PropertySpecificationLookup' )
		);
	},

	'Settings' => static function ( ServiceContainer $container ): Settings {
		$settings = new Settings();

		$settings->setHookDispatcher(
			$container->getService( 'HookDispatcher' )
		);

		$settings->loadFromGlobals();

		return $settings;
	},

	'ConnectionManager' => static function ( ServiceContainer $container ): ConnectionManager {
		return new ConnectionManager();
	},

	'SetupFile' => static function ( ServiceContainer $container ): SetupFile {
		return new SetupFile();
	},

	'MediaWikiNsContentReader' => static function ( ServiceContainer $container ): MediaWikiNsContentReader {
		$mediaWikiNsContentReader = new MediaWikiNsContentReader();

		$mediaWikiNsContentReader->setRevisionGuard(
			$container->getService( 'RevisionGuard' )
		);

		return $mediaWikiNsContentReader;
	},

	'EntityCache' => static function ( ServiceContainer $container ): EntityCache {
		return new EntityCache(
			ServicesFactory::getInstance()->singleton( 'Cache', $GLOBALS['smwgMainCacheType'] )
		);
	},

	'JobQueue' => static function ( ServiceContainer $container ): JobQueue {
		return new JobQueue(
			ServicesFactory::getInstance()->getJobQueueGroup()
		);
	},

	'ManualEntryLogger' => static function ( ServiceContainer $container ): ManualEntryLogger {
		return new ManualEntryLogger();
	},

	'HookDispatcher' => static function ( ServiceContainer $container ): HookDispatcher {
		return new HookDispatcher();
	},

	'RevisionGuard' => static function ( ServiceContainer $container ): RevisionGuard {
		$revisionGuard = new RevisionGuard(
			MediaWikiServices::getInstance()->getRevisionLookup()
		);

		$revisionGuard->setHookDispatcher(
			$container->getService( 'HookDispatcher' )
		);

		return $revisionGuard;
	},

	'InMemoryPoolCache' => static function ( ServiceContainer $container ): InMemoryPoolCache {
		return InMemoryPoolCache::getInstance();
	},

	'PropertyAnnotatorFactory' => static function ( ServiceContainer $container ): AnnotatorFactory {
		return new AnnotatorFactory();
	},

	'ConnectionProvider' => static function ( ServiceContainer $container ): ConnectionProvider {
		$connectionProvider = new ConnectionProvider();

		$connectionProvider->setLogger(
			ServicesFactory::getInstance()->singleton( 'MediaWikiLogger' )
		);

		return $connectionProvider;
	},

	'SchemaFactory' => static function ( ServiceContainer $container ): SchemaFactory {
		return new SchemaFactory();
	},

	'ConstraintFactory' => static function ( ServiceContainer $container ): ConstraintFactory {
		return new ConstraintFactory();
	},

	'ElasticFactory' => static function ( ServiceContainer $container ): ElasticFactory {
		return new ElasticFactory();
	},

	'QueryCreator' => static function ( ServiceContainer $container ): QueryCreator {
		$settings = $container->getService( 'Settings' );

		$queryCreator = new QueryCreator(
			$container->getService( 'QueryFactory' ),
			$settings->get( 'smwgQDefaultNamespaces' ),
			$settings->get( 'smwgQDefaultLimit' )
		);

		$queryCreator->setQFeatures(
			$settings->get( 'smwgQFeatures' )
		);

		$queryCreator->setQConceptFeatures(
			$settings->get( 'smwgQConceptFeatures' )
		);

		return $queryCreator;
	},

	'ParamListProcessor' => static function ( ServiceContainer $container ): ParamListProcessor {
		return new ParamListProcessor();
	},

	'FactboxText' => static function ( ServiceContainer $container ): FactboxText {
		return new FactboxText();
	},

	'IteratorFactory' => static function ( ServiceContainer $container ): IteratorFactory {
		return new IteratorFactory();
	},

	'JobFactory' => static function ( ServiceContainer $container ): JobFactory {
		return new JobFactory();
	},

	'FactboxFactory' => static function ( ServiceContainer $container ): FactboxFactory {
		return new FactboxFactory();
	},

	'QuerySourceFactory' => static function ( ServiceContainer $container ): QuerySourceFactory {
		return new QuerySourceFactory(
			ServicesFactory::getInstance()->singleton( 'Store' ),
			$container->getService( 'Settings' )->get( 'smwgQuerySources' )
		);
	},

	'QueryFactory' => static function ( ServiceContainer $container ): QueryFactory {
		return new QueryFactory();
	},

	'DataItemFactory' => static function ( ServiceContainer $container ): DataItemFactory {
		return new DataItemFactory();
	},

	'QueryDependencyLinksStoreFactory' => static function ( ServiceContainer $container ): QueryDependencyLinksStoreFactory {
		return new QueryDependencyLinksStoreFactory();
	},

	'PropertySpecificationLookup' => static function ( ServiceContainer $container ): SpecificationLookup {
		$contentLanguage = Localizer::getInstance()->getContentLanguage();

		$propertySpecificationLookup = new SpecificationLookup(
			ServicesFactory::getInstance()->singleton( 'Store' ),
			$container->getService( 'EntityCache' )
		);

		$propertySpecificationLookup->setLanguageCode(
			$contentLanguage->getCode()
		);

		return $propertySpecificationLookup;
	},

	'ProtectionValidator' => static function ( ServiceContainer $container ): ProtectionValidator {
		$settings = $container->getService( 'Settings' );

		$protectionValidator = new ProtectionValidator(
			ServicesFactory::getInstance()->singleton( 'Store' ),
			$container->getService( 'EntityCache' ),
			$container->getService( 'PermissionManager' )
		);

		$protectionValidator->setImportPerformers(
			$settings->get( 'smwgImportPerformers' )
		);

		$protectionValidator->setEditProtectionRight(
			$settings->get( 'smwgEditProtectionRight' )
		);

		$protectionValidator->setCreateProtectionRight(
			$settings->get( 'smwgCreateProtectionRight' )
		);

		$protectionValidator->setChangePropagationProtection(
			$settings->get( 'smwgChangePropagationProtection' )
		);

		return $protectionValidator;
	},

	'TitlePermissions' => static function ( ServiceContainer $container ): TitlePermissions {
		return new TitlePermissions(
			$container->getService( 'ProtectionValidator' ),
			$container->getService( 'PermissionManager' )
		);
	},

	'PropertyLabelFinder' => static function ( ServiceContainer $container ): PropertyLabelFinder {
		$lang = Localizer::getInstance()->getLang();

		return new PropertyLabelFinder(
			ServicesFactory::getInstance()->singleton( 'Store' ),
			$lang->getPropertyLabels(),
			$lang->getCanonicalPropertyLabels(),
			$lang->getCanonicalDatatypeLabels()
		);
	},

];
