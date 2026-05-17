<?php

namespace SMW\Tests\Integration\Services;

use JobQueueGroup;
use MediaWiki\Config\Config;
use MediaWiki\Language\Language;
use MediaWiki\Parser\MagicWordFactory;
use MediaWiki\Parser\ParserCache;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWikiIntegrationTestCase;
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
 * Verifies that every Bucket-A service registered through
 * `src/Services/ServiceWiring.php` resolves from SMW's private
 * ServiceContainer and yields the expected type.
 *
 * @coversNothing
 * @group SMW
 * @group SMWExtension
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ServiceWiringTest extends MediaWikiIntegrationTestCase {

	private ServiceContainer $container;

	protected function setUp(): void {
		parent::setUp();

		$this->container = new ServiceContainer();
		$this->container->loadWiringFiles( [
			dirname( __DIR__, 4 ) . '/src/Services/ServiceWiring.php',
		] );
	}

	/**
	 * @dataProvider serviceProvider
	 */
	public function testServiceResolvesToExpectedType( string $serviceName, string $expectedType ): void {
		$service = $this->container->getService( $serviceName );

		$this->assertInstanceOf( $expectedType, $service );
	}

	public function serviceProvider(): array {
		return [
			[ 'MainConfig', Config::class ],
			[ 'SearchEngineConfig', SearchEngineConfig::class ],
			[ 'MagicWordFactory', MagicWordFactory::class ],
			[ 'PermissionManager', PermissionManager::class ],
			[ 'DBLoadBalancerFactory', LBFactory::class ],
			[ 'DBLoadBalancer', ILoadBalancer::class ],
			[ 'FileRepoFinder', FileRepoFinder::class ],
			[ 'JobQueueGroup', JobQueueGroup::class ],
			[ 'ContentLanguage', Language::class ],
			[ 'ParserCache', ParserCache::class ],
			[ 'UserOptionsLookup', UserOptionsLookup::class ],
			[ 'InvalidateResultCacheEventListener', InvalidateResultCacheEventListener::class ],
			[ 'InvalidateEntityCacheEventListener', InvalidateEntityCacheEventListener::class ],
			[ 'InvalidatePropertySpecificationLookupCacheEventListener', InvalidatePropertySpecificationLookupCacheEventListener::class ],
			[ 'Settings', Settings::class ],
			[ 'ConnectionManager', ConnectionManager::class ],
			[ 'SetupFile', SetupFile::class ],
			[ 'MediaWikiNsContentReader', MediaWikiNsContentReader::class ],
			[ 'EntityCache', EntityCache::class ],
			[ 'JobQueue', JobQueue::class ],
			[ 'ManualEntryLogger', ManualEntryLogger::class ],
			[ 'HookDispatcher', HookDispatcher::class ],
			[ 'RevisionGuard', RevisionGuard::class ],
			[ 'InMemoryPoolCache', InMemoryPoolCache::class ],
			[ 'PropertyAnnotatorFactory', AnnotatorFactory::class ],
			[ 'ConnectionProvider', ConnectionProvider::class ],
			[ 'SchemaFactory', SchemaFactory::class ],
			[ 'ConstraintFactory', ConstraintFactory::class ],
			[ 'ElasticFactory', ElasticFactory::class ],
			[ 'QueryCreator', QueryCreator::class ],
			[ 'ParamListProcessor', ParamListProcessor::class ],
			[ 'FactboxText', FactboxText::class ],
			[ 'IteratorFactory', IteratorFactory::class ],
			[ 'JobFactory', JobFactory::class ],
			[ 'FactboxFactory', FactboxFactory::class ],
			[ 'QuerySourceFactory', QuerySourceFactory::class ],
			[ 'QueryFactory', QueryFactory::class ],
			[ 'DataItemFactory', DataItemFactory::class ],
			[ 'QueryDependencyLinksStoreFactory', QueryDependencyLinksStoreFactory::class ],
			[ 'PropertySpecificationLookup', SpecificationLookup::class ],
			[ 'ProtectionValidator', ProtectionValidator::class ],
			[ 'TitlePermissions', TitlePermissions::class ],
			[ 'PropertyLabelFinder', PropertyLabelFinder::class ],
		];
	}

}
