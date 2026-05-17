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
use SMW\NamespaceExaminer;
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

/**
 * Global MediaWiki service wiring for Semantic MediaWiki.
 *
 * Holds the Bucket-A services (no-argument, stateless) of the
 * callback-container migration, registered under the `SMW.<Name>` prefix.
 *
 * Services that take runtime arguments or are constructed fresh per use
 * (Bucket B and Bucket C) are not registered here; those remain factory
 * methods. A dependency on such a service is resolved through
 * `ServicesFactory`, which keeps working during the migration via the onoi
 * container and afterwards via a compatibility shim.
 *
 * @codeCoverageIgnore
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
return [

	'SMW.MainConfig' => static function ( MediaWikiServices $services ): Config {
		return $services->getMainConfig();
	},

	'SMW.SearchEngineConfig' => static function ( MediaWikiServices $services ): SearchEngineConfig {
		return $services->getSearchEngineConfig();
	},

	'SMW.MagicWordFactory' => static function ( MediaWikiServices $services ): MagicWordFactory {
		return $services->getMagicWordFactory();
	},

	'SMW.PermissionManager' => static function ( MediaWikiServices $services ): PermissionManager {
		return new PermissionManager( $services->getPermissionManager() );
	},

	'SMW.DBLoadBalancerFactory' => static function ( MediaWikiServices $services ): LBFactory {
		return $services->getDBLoadBalancerFactory();
	},

	'SMW.DBLoadBalancer' => static function ( MediaWikiServices $services ): ILoadBalancer {
		return $services->getDBLoadBalancer();
	},

	'SMW.FileRepoFinder' => static function ( MediaWikiServices $services ): FileRepoFinder {
		return new FileRepoFinder( $services->getRepoGroup() );
	},

	'SMW.JobQueueGroup' => static function ( MediaWikiServices $services ): JobQueueGroup {
		return $services->getJobQueueGroup();
	},

	'SMW.ContentLanguage' => static function ( MediaWikiServices $services ): Language {
		return $services->getContentLanguage();
	},

	'SMW.ParserCache' => static function ( MediaWikiServices $services ): ParserCache {
		return $services->getParserCache();
	},

	'SMW.UserOptionsLookup' => static function ( MediaWikiServices $services ): UserOptionsLookup {
		return $services->getUserOptionsLookup();
	},

	'SMW.InvalidateResultCacheEventListener' => static function ( MediaWikiServices $services ): InvalidateResultCacheEventListener {
		return new InvalidateResultCacheEventListener(
			ServicesFactory::getInstance()->singleton( 'ResultCache' )
		);
	},

	'SMW.InvalidateEntityCacheEventListener' => static function ( MediaWikiServices $services ): InvalidateEntityCacheEventListener {
		return new InvalidateEntityCacheEventListener(
			$services->getService( 'SMW.EntityCache' )
		);
	},

	'SMW.InvalidatePropertySpecificationLookupCacheEventListener' => static function ( MediaWikiServices $services ): InvalidatePropertySpecificationLookupCacheEventListener {
		return new InvalidatePropertySpecificationLookupCacheEventListener(
			$services->getService( 'SMW.PropertySpecificationLookup' )
		);
	},

	'SMW.Settings' => static function ( MediaWikiServices $services ): Settings {
		$settings = new Settings();

		$settings->setHookDispatcher(
			$services->getService( 'SMW.HookDispatcher' )
		);

		$settings->loadFromGlobals();

		return $settings;
	},

	'SMW.ConnectionManager' => static function ( MediaWikiServices $services ): ConnectionManager {
		return new ConnectionManager();
	},

	'SMW.SetupFile' => static function ( MediaWikiServices $services ): SetupFile {
		return new SetupFile();
	},

	'SMW.NamespaceExaminer' => static function ( MediaWikiServices $services ): NamespaceExaminer {
		$settings = $services->getService( 'SMW.Settings' );
		$namespaceInfo = $services->getNamespaceInfo();

		$namespaceExaminer = new NamespaceExaminer(
			$settings->get( 'smwgNamespacesWithSemanticLinks' )
		);

		$namespaceExaminer->setValidNamespaces(
			$namespaceInfo->getValidNamespaces()
		);

		return $namespaceExaminer;
	},

	'SMW.MediaWikiNsContentReader' => static function ( MediaWikiServices $services ): MediaWikiNsContentReader {
		$mediaWikiNsContentReader = new MediaWikiNsContentReader();

		$mediaWikiNsContentReader->setRevisionGuard(
			$services->getService( 'SMW.RevisionGuard' )
		);

		return $mediaWikiNsContentReader;
	},

	'SMW.EntityCache' => static function ( MediaWikiServices $services ): EntityCache {
		return new EntityCache(
			ServicesFactory::getInstance()->singleton( 'Cache', $GLOBALS['smwgMainCacheType'] )
		);
	},

	'SMW.JobQueue' => static function ( MediaWikiServices $services ): JobQueue {
		return new JobQueue(
			$services->getJobQueueGroup()
		);
	},

	'SMW.ManualEntryLogger' => static function ( MediaWikiServices $services ): ManualEntryLogger {
		return new ManualEntryLogger();
	},

	'SMW.HookDispatcher' => static function ( MediaWikiServices $services ): HookDispatcher {
		return new HookDispatcher();
	},

	'SMW.RevisionGuard' => static function ( MediaWikiServices $services ): RevisionGuard {
		$revisionGuard = new RevisionGuard(
			$services->getRevisionLookup()
		);

		$revisionGuard->setHookDispatcher(
			$services->getService( 'SMW.HookDispatcher' )
		);

		return $revisionGuard;
	},

	'SMW.InMemoryPoolCache' => static function ( MediaWikiServices $services ): InMemoryPoolCache {
		return InMemoryPoolCache::getInstance();
	},

	'SMW.PropertyAnnotatorFactory' => static function ( MediaWikiServices $services ): AnnotatorFactory {
		return new AnnotatorFactory();
	},

	'SMW.ConnectionProvider' => static function ( MediaWikiServices $services ): ConnectionProvider {
		$connectionProvider = new ConnectionProvider();

		$connectionProvider->setLogger(
			ServicesFactory::getInstance()->singleton( 'MediaWikiLogger' )
		);

		return $connectionProvider;
	},

	'SMW.SchemaFactory' => static function ( MediaWikiServices $services ): SchemaFactory {
		return new SchemaFactory();
	},

	'SMW.ConstraintFactory' => static function ( MediaWikiServices $services ): ConstraintFactory {
		return new ConstraintFactory();
	},

	'SMW.ElasticFactory' => static function ( MediaWikiServices $services ): ElasticFactory {
		return new ElasticFactory();
	},

	'SMW.QueryCreator' => static function ( MediaWikiServices $services ): QueryCreator {
		$settings = $services->getService( 'SMW.Settings' );

		$queryCreator = new QueryCreator(
			$services->getService( 'SMW.QueryFactory' ),
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

	'SMW.ParamListProcessor' => static function ( MediaWikiServices $services ): ParamListProcessor {
		return new ParamListProcessor();
	},

	'SMW.FactboxText' => static function ( MediaWikiServices $services ): FactboxText {
		return new FactboxText();
	},

	'SMW.IteratorFactory' => static function ( MediaWikiServices $services ): IteratorFactory {
		return new IteratorFactory();
	},

	'SMW.JobFactory' => static function ( MediaWikiServices $services ): JobFactory {
		return new JobFactory();
	},

	'SMW.FactboxFactory' => static function ( MediaWikiServices $services ): FactboxFactory {
		return new FactboxFactory();
	},

	'SMW.QuerySourceFactory' => static function ( MediaWikiServices $services ): QuerySourceFactory {
		return new QuerySourceFactory(
			ServicesFactory::getInstance()->singleton( 'Store' ),
			$services->getService( 'SMW.Settings' )->get( 'smwgQuerySources' )
		);
	},

	'SMW.QueryFactory' => static function ( MediaWikiServices $services ): QueryFactory {
		return new QueryFactory();
	},

	'SMW.DataItemFactory' => static function ( MediaWikiServices $services ): DataItemFactory {
		return new DataItemFactory();
	},

	'SMW.DataValueServiceFactory' => static function ( MediaWikiServices $services ): DataValueServiceFactory {
		// DataValueServiceFactory is constructed from the onoi callback
		// container (it requires a ContainerBuilder and seeds the
		// datavalues wiring file via registerFromFile). Resolve it through
		// ServicesFactory, which owns that container during the migration.
		return ServicesFactory::getInstance()->singleton( 'DataValueServiceFactory' );
	},

	'SMW.QueryDependencyLinksStoreFactory' => static function ( MediaWikiServices $services ): QueryDependencyLinksStoreFactory {
		return new QueryDependencyLinksStoreFactory();
	},

	'SMW.PropertySpecificationLookup' => static function ( MediaWikiServices $services ): SpecificationLookup {
		$contentLanguage = Localizer::getInstance()->getContentLanguage();

		$propertySpecificationLookup = new SpecificationLookup(
			ServicesFactory::getInstance()->singleton( 'Store' ),
			$services->getService( 'SMW.EntityCache' )
		);

		$propertySpecificationLookup->setLanguageCode(
			$contentLanguage->getCode()
		);

		return $propertySpecificationLookup;
	},

	'SMW.ProtectionValidator' => static function ( MediaWikiServices $services ): ProtectionValidator {
		$settings = $services->getService( 'SMW.Settings' );

		$protectionValidator = new ProtectionValidator(
			ServicesFactory::getInstance()->singleton( 'Store' ),
			$services->getService( 'SMW.EntityCache' ),
			$services->getService( 'SMW.PermissionManager' )
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

	'SMW.TitlePermissions' => static function ( MediaWikiServices $services ): TitlePermissions {
		return new TitlePermissions(
			$services->getService( 'SMW.ProtectionValidator' ),
			$services->getService( 'SMW.PermissionManager' )
		);
	},

	'SMW.PropertyLabelFinder' => static function ( MediaWikiServices $services ): PropertyLabelFinder {
		$lang = Localizer::getInstance()->getLang();

		return new PropertyLabelFinder(
			ServicesFactory::getInstance()->singleton( 'Store' ),
			$lang->getPropertyLabels(),
			$lang->getCanonicalPropertyLabels(),
			$lang->getCanonicalDatatypeLabels()
		);
	},

];
