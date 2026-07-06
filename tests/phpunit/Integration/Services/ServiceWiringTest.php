<?php

namespace SMW\Tests\Integration\Services;

use MediaWiki\Api\ApiMain;
use MediaWiki\MediaWikiServices;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use SMW\CacheFactory;
use SMW\Connection\ConnectionManager;
use SMW\ConstraintFactory;
use SMW\DataItemFactory;
use SMW\DisplayTitleFinder;
use SMW\Elastic\ElasticFactory;
use SMW\Elastic\Jobs\FileIngestJob;
use SMW\Elastic\Jobs\IndexerRecoveryJob;
use SMW\EntityCache;
use SMW\Factbox\FactboxFactory;
use SMW\Factbox\FactboxText;
use SMW\HierarchyLookup;
use SMW\InMemoryPoolCache;
use SMW\IteratorFactory;
use SMW\Listener\EventListener\EventListeners\InvalidateEntityCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener;
use SMW\Listener\EventListener\EventListeners\InvalidateResultCacheEventListener;
use SMW\Maintenance\MaintenanceFactory;
use SMW\MediaWiki\Api\Ask;
use SMW\MediaWiki\Api\AskArgs;
use SMW\MediaWiki\Api\Browse;
use SMW\MediaWiki\Api\Info;
use SMW\MediaWiki\Api\Task;
use SMW\MediaWiki\Api\TaskFactory;
use SMW\MediaWiki\Connection\ConnectionProvider;
use SMW\MediaWiki\Job;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\Jobs\ChangePropagationClassUpdateJob;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use SMW\MediaWiki\Jobs\ChangePropagationUpdateJob;
use SMW\MediaWiki\Jobs\DeferredConstraintCheckUpdateJob;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob;
use SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob;
use SMW\MediaWiki\Jobs\ParserCachePurgeJob;
use SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob;
use SMW\MediaWiki\Jobs\RefreshJob;
use SMW\MediaWiki\Jobs\UpdateDispatcherJob;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\MediaWiki\LinkBatch;
use SMW\MediaWiki\MediaWikiNsContentReader;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\Permission\TitlePermissions;
use SMW\MediaWiki\PermissionManager;
use SMW\MediaWiki\RevisionGuard;
use SMW\MediaWiki\Specials\SpecialAdmin;
use SMW\MediaWiki\Specials\SpecialAsk;
use SMW\MediaWiki\Specials\SpecialBrowse;
use SMW\MediaWiki\Specials\SpecialConcepts;
use SMW\MediaWiki\Specials\SpecialConstraintErrorList;
use SMW\MediaWiki\Specials\SpecialFacetedSearch;
use SMW\MediaWiki\Specials\SpecialMissingRedirectAnnotations;
use SMW\MediaWiki\Specials\SpecialOWLExport;
use SMW\MediaWiki\Specials\SpecialPageProperty;
use SMW\MediaWiki\Specials\SpecialPendingTaskList;
use SMW\MediaWiki\Specials\SpecialProcessingErrorList;
use SMW\MediaWiki\Specials\SpecialProperties;
use SMW\MediaWiki\Specials\SpecialPropertyLabelSimilarity;
use SMW\MediaWiki\Specials\SpecialSearchByProperty;
use SMW\MediaWiki\Specials\SpecialTypes;
use SMW\MediaWiki\Specials\SpecialUnusedProperties;
use SMW\MediaWiki\Specials\SpecialURIResolver;
use SMW\MediaWiki\Specials\SpecialWantedProperties;
use SMW\NamespaceExaminer;
use SMW\ParserFunctionFactory;
use SMW\Property\AnnotatorFactory;
use SMW\Property\SpecificationLookup;
use SMW\PropertyLabelFinder;
use SMW\Protection\ProtectionValidator;
use SMW\Query\Processor\ParamListProcessor;
use SMW\Query\Processor\QueryCreator;
use SMW\Query\QuerySourceFactory;
use SMW\QueryFactory;
use SMW\Schema\SchemaFactory;
use SMW\SerializerFactory;
use SMW\Services\DataValueServiceFactory;
use SMW\Services\ImporterServiceFactory;
use SMW\Settings;
use SMW\SetupFile;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\Store;

/**
 * Verifies that every SMW service registered on MediaWiki's global
 * `ServiceContainer` resolves to the expected type.
 *
 * @coversNothing
 * @group SMW
 * @group SMWExtension
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ServiceWiringTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider serviceProvider
	 */
	public function testServiceResolvesToExpectedType( string $serviceName, string $expectedType ): void {
		$service = MediaWikiServices::getInstance()->getService( $serviceName );

		$this->assertInstanceOf( $expectedType, $service );
	}

	public function serviceProvider(): array {
		return [
			[ 'SMW.Settings', Settings::class ],
			[ 'SMW.Store', Store::class ],
			[ 'SMW.EntityCache', EntityCache::class ],
			[ 'SMW.JobQueue', JobQueue::class ],
			[ 'SMW.LinkBatch', LinkBatch::class ],
			[ 'SMW.PermissionManager', PermissionManager::class ],
			[ 'SMW.RevisionGuard', RevisionGuard::class ],
			[ 'SMW.ConnectionManager', ConnectionManager::class ],
			[ 'SMW.SetupFile', SetupFile::class ],
			[ 'SMW.MediaWikiNsContentReader', MediaWikiNsContentReader::class ],
			[ 'SMW.InMemoryPoolCache', InMemoryPoolCache::class ],
			[ 'SMW.PropertyAnnotatorFactory', AnnotatorFactory::class ],
			[ 'SMW.ConnectionProvider', ConnectionProvider::class ],
			[ 'SMW.SchemaFactory', SchemaFactory::class ],
			[ 'SMW.ConstraintFactory', ConstraintFactory::class ],
			[ 'SMW.ElasticFactory', ElasticFactory::class ],
			[ 'SMW.QueryCreator', QueryCreator::class ],
			[ 'SMW.ParamListProcessor', ParamListProcessor::class ],
			[ 'SMW.FactboxText', FactboxText::class ],
			[ 'SMW.IteratorFactory', IteratorFactory::class ],
			[ 'SMW.JobFactory', JobFactory::class ],
			[ 'SMW.TaskFactory', TaskFactory::class ],
			[ 'SMW.FactboxFactory', FactboxFactory::class ],
			[ 'SMW.QuerySourceFactory', QuerySourceFactory::class ],
			[ 'SMW.QueryFactory', QueryFactory::class ],
			[ 'SMW.DataItemFactory', DataItemFactory::class ],
			[ 'SMW.QueryDependencyLinksStoreFactory', QueryDependencyLinksStoreFactory::class ],
			[ 'SMW.PropertySpecificationLookup', SpecificationLookup::class ],
			[ 'SMW.ProtectionValidator', ProtectionValidator::class ],
			[ 'SMW.TitlePermissions', TitlePermissions::class ],
			[ 'SMW.PropertyLabelFinder', PropertyLabelFinder::class ],
			[ 'SMW.InvalidateResultCacheEventListener', InvalidateResultCacheEventListener::class ],
			[ 'SMW.InvalidateEntityCacheEventListener', InvalidateEntityCacheEventListener::class ],
			[ 'SMW.InvalidatePropertySpecificationLookupCacheEventListener', InvalidatePropertySpecificationLookupCacheEventListener::class ],
			[ 'SMW.SerializerFactory', SerializerFactory::class ],
			[ 'SMW.ParserFunctionFactory', ParserFunctionFactory::class ],
			[ 'SMW.MaintenanceFactory', MaintenanceFactory::class ],
			[ 'SMW.CacheFactory', CacheFactory::class ],
			[ 'SMW.PageCreator', PageCreator::class ],
			[ 'SMW.MwCollaboratorFactory', MwCollaboratorFactory::class ],
			[ 'SMW.NamespaceExaminer', NamespaceExaminer::class ],
			[ 'SMW.DataValueServiceFactory', DataValueServiceFactory::class ],
			[ 'SMW.ImporterServiceFactory', ImporterServiceFactory::class ],
			[ 'SMW.HierarchyLookup', HierarchyLookup::class ],
			[ 'SMW.DisplayTitleFinder', DisplayTitleFinder::class ],
		];
	}

	/**
	 * Resolves each SMW JobClasses command through MediaWiki's JobFactory to
	 * prove the ObjectFactory spec (and any 'services' array attached to it)
	 * wires successfully.
	 *
	 * @dataProvider jobCommandProvider
	 */
	public function testJobCommandResolvesToExpectedType( string $command, string $expectedType ): void {
		$title = Title::makeTitle( NS_MAIN, 'ServiceWiringTest' );

		$job = MediaWikiServices::getInstance()->getJobFactory()->newJob(
			$command,
			$title,
			[]
		);

		$this->assertInstanceOf( $expectedType, $job );
		$this->assertInstanceOf( Job::class, $job );
	}

	public function jobCommandProvider(): array {
		return [
			[ 'smw.update', UpdateJob::class ],
			[ 'smw.refresh', RefreshJob::class ],
			[ 'smw.updateDispatcher', UpdateDispatcherJob::class ],
			[ 'smw.fulltextSearchTableUpdate', FulltextSearchTableUpdateJob::class ],
			[ 'smw.entityIdDisposer', EntityIdDisposerJob::class ],
			[ 'smw.propertyStatisticsRebuild', PropertyStatisticsRebuildJob::class ],
			[ 'smw.fulltextSearchTableRebuild', FulltextSearchTableRebuildJob::class ],
			[ 'smw.changePropagationDispatch', ChangePropagationDispatchJob::class ],
			[ 'smw.changePropagationUpdate', ChangePropagationUpdateJob::class ],
			[ 'smw.changePropagationClassUpdate', ChangePropagationClassUpdateJob::class ],
			[ 'smw.deferredConstraintCheckUpdateJob', DeferredConstraintCheckUpdateJob::class ],
			[ 'smw.elasticIndexerRecovery', IndexerRecoveryJob::class ],
			[ 'smw.elasticFileIngest', FileIngestJob::class ],
			[ 'smw.parserCachePurgeJob', ParserCachePurgeJob::class ],
		];
	}

	/**
	 * Resolves each SMW APIModules entry through ApiMain's module manager so
	 * the ObjectFactory spec (and any 'services' array attached to it) wires
	 * successfully and produces the expected module class.
	 *
	 * @dataProvider apiModuleProvider
	 */
	public function testApiModuleResolvesToExpectedType( string $moduleName, string $expectedType ): void {
		$apiMain = new ApiMain( new FauxRequest( [ 'action' => $moduleName ], true ), true );

		$module = $apiMain->getModuleManager()->getModule( $moduleName );

		$this->assertInstanceOf( $expectedType, $module );
	}

	public function apiModuleProvider(): array {
		return [
			[ 'smwinfo', Info::class ],
			[ 'smwtask', Task::class ],
			[ 'smwbrowse', Browse::class ],
			[ 'ask', Ask::class ],
			[ 'askargs', AskArgs::class ],
		];
	}

	/**
	 * Resolves each SMW SpecialPages entry through MediaWiki's
	 * SpecialPageFactory so the ObjectFactory spec (and any 'services' array
	 * attached to it) wires successfully and produces the expected
	 * SpecialPage class.
	 *
	 * @dataProvider specialPageProvider
	 */
	public function testSpecialPageResolvesToExpectedType( string $specialPageName, string $expectedType ): void {
		$specialPage = MediaWikiServices::getInstance()->getSpecialPageFactory()->getPage( $specialPageName );

		$this->assertInstanceOf( $expectedType, $specialPage );
	}

	public function specialPageProvider(): array {
		return [
			[ 'ExportRDF', SpecialOWLExport::class ],
			[ 'SMWAdmin', SpecialAdmin::class ],
			[ 'PendingTaskList', SpecialPendingTaskList::class ],
			[ 'Ask', SpecialAsk::class ],
			[ 'FacetedSearch', SpecialFacetedSearch::class ],
			[ 'Browse', SpecialBrowse::class ],
			[ 'Concepts', SpecialConcepts::class ],
			[ 'PageProperty', SpecialPageProperty::class ],
			[ 'SearchByProperty', SpecialSearchByProperty::class ],
			[ 'PropertyLabelSimilarity', SpecialPropertyLabelSimilarity::class ],
			[ 'ProcessingErrorList', SpecialProcessingErrorList::class ],
			[ 'MissingRedirectAnnotations', SpecialMissingRedirectAnnotations::class ],
			[ 'ConstraintErrorList', SpecialConstraintErrorList::class ],
			[ 'Types', SpecialTypes::class ],
			[ 'URIResolver', SpecialURIResolver::class ],
			[ 'Properties', SpecialProperties::class ],
			[ 'UnusedProperties', SpecialUnusedProperties::class ],
			[ 'WantedProperties', SpecialWantedProperties::class ],
		];
	}

}
