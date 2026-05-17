<?php

namespace SMW\Tests\Integration\Services;

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
use SMW\Services\DataValueServiceFactory;
use SMW\Settings;
use SMW\SetupFile;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\Tests\SMWIntegrationTestCase;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LBFactory;

/**
 * Verifies that every Bucket-A service registered through
 * `src/Services/ServiceWiring.php` resolves from the global MediaWiki
 * service container under the `SMW.<Name>` key and yields the expected type.
 *
 * @coversNothing
 * @group SMW
 * @group SMWExtension
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ServiceWiringTest extends SMWIntegrationTestCase {

	/**
	 * @dataProvider serviceProvider
	 */
	public function testServiceResolvesToExpectedType( string $serviceName, string $expectedType ) {
		$service = MediaWikiServices::getInstance()->getService( $serviceName );

		$this->assertInstanceOf( $expectedType, $service );
	}

	public function serviceProvider(): array {
		return [
			[ 'SMW.MainConfig', Config::class ],
			[ 'SMW.SearchEngineConfig', SearchEngineConfig::class ],
			[ 'SMW.MagicWordFactory', MagicWordFactory::class ],
			[ 'SMW.PermissionManager', PermissionManager::class ],
			[ 'SMW.DBLoadBalancerFactory', LBFactory::class ],
			[ 'SMW.DBLoadBalancer', ILoadBalancer::class ],
			[ 'SMW.FileRepoFinder', FileRepoFinder::class ],
			[ 'SMW.JobQueueGroup', JobQueueGroup::class ],
			[ 'SMW.ContentLanguage', Language::class ],
			[ 'SMW.ParserCache', ParserCache::class ],
			[ 'SMW.UserOptionsLookup', UserOptionsLookup::class ],
			[ 'SMW.InvalidateResultCacheEventListener', InvalidateResultCacheEventListener::class ],
			[ 'SMW.InvalidateEntityCacheEventListener', InvalidateEntityCacheEventListener::class ],
			[ 'SMW.InvalidatePropertySpecificationLookupCacheEventListener', InvalidatePropertySpecificationLookupCacheEventListener::class ],
			[ 'SMW.Settings', Settings::class ],
			[ 'SMW.ConnectionManager', ConnectionManager::class ],
			[ 'SMW.SetupFile', SetupFile::class ],
			[ 'SMW.NamespaceExaminer', NamespaceExaminer::class ],
			[ 'SMW.MediaWikiNsContentReader', MediaWikiNsContentReader::class ],
			[ 'SMW.EntityCache', EntityCache::class ],
			[ 'SMW.JobQueue', JobQueue::class ],
			[ 'SMW.ManualEntryLogger', ManualEntryLogger::class ],
			[ 'SMW.HookDispatcher', HookDispatcher::class ],
			[ 'SMW.RevisionGuard', RevisionGuard::class ],
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
			[ 'SMW.DataValueServiceFactory', DataValueServiceFactory::class ],
			[ 'SMW.QueryDependencyLinksStoreFactory', QueryDependencyLinksStoreFactory::class ],
			[ 'SMW.PropertySpecificationLookup', SpecificationLookup::class ],
			[ 'SMW.ProtectionValidator', ProtectionValidator::class ],
			[ 'SMW.TitlePermissions', TitlePermissions::class ],
			[ 'SMW.PropertyLabelFinder', PropertyLabelFinder::class ],
		];
	}

}
