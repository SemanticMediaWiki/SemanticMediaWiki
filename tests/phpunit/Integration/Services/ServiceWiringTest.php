<?php

namespace SMW\Tests\Integration\Services;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Onoi\Cache\Cache;
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
use SMW\MediaWiki\Connection\ConnectionProvider;
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
			[ 'SMW.Cache', Cache::class ],
			[ 'SMW.EntityCache', EntityCache::class ],
			[ 'SMW.JobQueue', JobQueue::class ],
			[ 'SMW.PermissionManager', PermissionManager::class ],
			[ 'SMW.HookDispatcher', HookDispatcher::class ],
			[ 'SMW.RevisionGuard', RevisionGuard::class ],
			[ 'SMW.ConnectionManager', ConnectionManager::class ],
			[ 'SMW.SetupFile', SetupFile::class ],
			[ 'SMW.MediaWikiNsContentReader', MediaWikiNsContentReader::class ],
			[ 'SMW.ManualEntryLogger', ManualEntryLogger::class ],
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
		];
	}

}
