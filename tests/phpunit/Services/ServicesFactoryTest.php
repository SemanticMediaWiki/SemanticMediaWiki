<?php

namespace SMW\Tests\Services;

use MediaWiki\Parser\Parser;
use MediaWiki\Title\Title;
use Onoi\EventDispatcher\EventDispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\CacheFactory;
use SMW\Connection\ConnectionManager;
use SMW\ContentParser;
use SMW\DataItemFactory;
use SMW\DataItems\WikiPage;
use SMW\DataUpdater;
use SMW\DataValueFactory;
use SMW\Factbox\FactboxFactory;
use SMW\HierarchyLookup;
use SMW\IteratorFactory;
use SMW\Maintenance\MaintenanceFactory;
use SMW\MediaWiki\Deferred\CallableUpdate;
use SMW\MediaWiki\Deferred\TransactionalCallableUpdate;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\PageUpdater;
use SMW\MediaWiki\TitleFactory;
use SMW\NamespaceExaminer;
use SMW\Parser\InTextAnnotationParser;
use SMW\ParserData;
use SMW\ParserFunctionFactory;
use SMW\Property\AnnotatorFactory;
use SMW\Property\SpecificationLookup;
use SMW\PropertyLabelFinder;
use SMW\Query\Cache\ResultCache;
use SMW\Query\QuerySourceFactory;
use SMW\QueryFactory;
use SMW\SemanticData;
use SMW\SerializerFactory;
use SMW\Services\ServicesFactory;
use SMW\Settings;
use SMW\Store;

/**
 * @covers \SMW\Services\ServicesFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class ServicesFactoryTest extends TestCase {

	private ServicesFactory $servicesFactory;

	protected function setUp(): void {
		parent::setUp();
		$this->servicesFactory = ServicesFactory::getInstance();
	}

	protected function tearDown(): void {
		$this->servicesFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ServicesFactory::class,
			ServicesFactory::getInstance()
		);
	}

	public function testCanConstructSerializerFactory() {
		$this->assertInstanceOf(
			SerializerFactory::class,
			$this->servicesFactory->newSerializerFactory()
		);
	}

	public function testCanConstructJobFactory() {
		$this->assertInstanceOf(
			JobFactory::class,
			$this->servicesFactory->newJobFactory()
		);
	}

	public function testCanConstructParserFunctionFactory() {
		$parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ParserFunctionFactory::class,
			$this->servicesFactory->newParserFunctionFactory( $parser )
		);
	}

	public function testCanConstructQuerySourceFactory() {
		$this->assertInstanceOf(
			QuerySourceFactory::class,
			$this->servicesFactory->getQuerySourceFactory()
		);
	}

	public function testGetStore() {
		$this->assertInstanceOf(
			Store::class,
			$this->servicesFactory->getStore()
		);
	}

	public function testGetSettings() {
		$this->assertInstanceOf(
			Settings::class,
			$this->servicesFactory->getSettings()
		);
	}

	public function testGetConnectionManager() {
		$this->assertInstanceOf(
			ConnectionManager::class,
			$this->servicesFactory->getConnectionManager()
		);
	}

	public function testCanConstructTitleFactory() {
		$this->assertInstanceOf(
			TitleFactory::class,
			$this->servicesFactory->newTitleFactory()
		);
	}

	public function testCanConstructPageCreator() {
		$this->assertInstanceOf(
			PageCreator::class,
			$this->servicesFactory->newPageCreator()
		);
	}

	public function testCanConstructPageUpdater() {
		$this->assertInstanceOf(
			PageUpdater::class,
			$this->servicesFactory->newPageUpdater()
		);
	}

	public function testCanConstructInTextAnnotationParser() {
		$parserData = $this->getMockBuilder( ParserData::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			InTextAnnotationParser::class,
			$this->servicesFactory->newInTextAnnotationParser( $parserData )
		);
	}

	public function testCanConstructContentParser() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ContentParser::class,
			$this->servicesFactory->newContentParser( $title )
		);
	}

	public function testCanConstructMwCollaboratorFactory() {
		$this->assertInstanceOf(
			MwCollaboratorFactory::class,
			$this->servicesFactory->newMwCollaboratorFactory()
		);
	}

	public function testCanConstructNamespaceExaminer() {
		$this->assertInstanceOf(
			NamespaceExaminer::class,
			$this->servicesFactory->getNamespaceExaminer()
		);
	}

	public function testCanConstructDataUpdater() {
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$this->assertInstanceOf(
			DataUpdater::class,
			$this->servicesFactory->newDataUpdater( $semanticData )
		);
	}

	public function testCanConstructDataItemFactory() {
		$this->assertInstanceOf(
			DataItemFactory::class,
			$this->servicesFactory->getDataItemFactory()
		);
	}

	public function testCanConstructMaintenanceFactory() {
		$this->assertInstanceOf(
			MaintenanceFactory::class,
			$this->servicesFactory->newMaintenanceFactory()
		);
	}

	public function testCanConstructCacheFactory() {
		$this->assertInstanceOf(
			CacheFactory::class,
			$this->servicesFactory->newCacheFactory()
		);
	}

	public function testCanConstructIteratorFactory() {
		$this->assertInstanceOf(
			IteratorFactory::class,
			$this->servicesFactory->getIteratorFactory()
		);
	}

	public function testCanConstructDataValueFactory() {
		$this->assertInstanceOf(
			DataValueFactory::class,
			$this->servicesFactory->getDataValueFactory()
		);
	}

	public function testCanConstructPropertySpecificationLookup() {
		$this->assertInstanceOf(
			SpecificationLookup::class,
			$this->servicesFactory->getPropertySpecificationLookup()
		);
	}

	public function testCanConstructHierarchyLookup() {
		$this->assertInstanceOf(
			HierarchyLookup::class,
			$this->servicesFactory->newHierarchyLookup()
		);
	}

	public function testCanConstructQueryFactory() {
		$this->assertInstanceOf(
			QueryFactory::class,
			$this->servicesFactory->getQueryFactory()
		);
	}

	public function testCanConstructPropertyLabelFinder() {
		$this->assertInstanceOf(
			PropertyLabelFinder::class,
			$this->servicesFactory->getPropertyLabelFinder()
		);
	}

	public function testCanConstructDeferredCallableUpdate() {
		$callback = static function () {
			return null;
		};

		$this->assertInstanceOf(
			CallableUpdate::class,
			$this->servicesFactory->newDeferredCallableUpdate( $callback )
		);
	}

	public function testCanConstructDeferredTransactionalCallableUpdate() {
		$this->assertInstanceOf(
			TransactionalCallableUpdate::class,
			$this->servicesFactory->newDeferredTransactionalCallableUpdate( null )
		);
	}

	public function testCanConstructMediaWikiLogger() {
		$this->assertInstanceOf(
			LoggerInterface::class,
			$this->servicesFactory->getMediaWikiLogger()
		);
	}

	public function testCanConstructEventDispatcher() {
		$this->assertInstanceOf(
			EventDispatcher::class,
			$this->servicesFactory->getEventDispatcher()
		);
	}

	public function testCanConstructJobQueue() {
		$this->assertInstanceOf(
			'\SMW\MediaWiki\JobQueue',
			$this->servicesFactory->getJobQueue()
		);
	}

	/**
	 * @dataProvider callbackContainerProvider
	 */
	public function testCanConstructFromCallbackContainer( $service, $arguments, $expected ) {
		array_unshift( $arguments, $service );

		$this->assertInstanceOf(
			$expected,
			call_user_func_array( [ $this->servicesFactory, 'create' ], $arguments )
		);
	}

	public function callbackContainerProvider() {
		$provider[] = [
			'ResultCache',
			[],
			ResultCache::class
		];

		$provider[] = [
			'FactboxFactory',
			[],
			FactboxFactory::class
		];

		$provider[] = [
			'PropertyAnnotatorFactory',
			[],
			AnnotatorFactory::class
		];

		return $provider;
	}

}
