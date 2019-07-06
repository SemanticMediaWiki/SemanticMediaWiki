<?php

namespace SMW\Tests\Services;

use SMW\Services\ServicesFactory;

/**
 * @covers \SMW\Services\ServicesFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ServicesFactoryTest extends \PHPUnit_Framework_TestCase {

	private $ServicesFactory;

	protected function setUp() {
		parent::setUp();
		$this->servicesFactory = ServicesFactory::getInstance();
	}

	protected function tearDown() {
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
			'\SMW\SerializerFactory',
			$this->servicesFactory->newSerializerFactory()
		);
	}

	public function testCanConstructJobFactory() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\JobFactory',
			$this->servicesFactory->newJobFactory()
		);
	}

	public function testCanConstructParserFunctionFactory() {

		$parser = $this->getMockBuilder( '\Parser' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ParserFunctionFactory',
			$this->servicesFactory->newParserFunctionFactory( $parser )
		);
	}

	public function testCanConstructQuerySourceFactory() {

		$this->assertInstanceOf(
			'\SMW\Query\QuerySourceFactory',
			$this->servicesFactory->getQuerySourceFactory()
		);
	}

	public function testGetStore() {

		$this->assertInstanceOf(
			'\SMW\Store',
			$this->servicesFactory->getStore()
		);
	}

	public function testGetSettings() {

		$this->assertInstanceOf(
			'\SMW\Settings',
			$this->servicesFactory->getSettings()
		);
	}

	public function testGetConnectionManager() {

		$this->assertInstanceOf(
			'\SMW\Connection\ConnectionManager',
			$this->servicesFactory->getConnectionManager()
		);
	}

	public function testCanConstructTitleFactory() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\TitleFactory',
			$this->servicesFactory->newTitleFactory()
		);
	}

	public function testCanConstructPageCreator() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\PageCreator',
			$this->servicesFactory->newPageCreator()
		);
	}

	public function testCanConstructPageUpdater() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\PageUpdater',
			$this->servicesFactory->newPageUpdater()
		);
	}

	public function testCanConstructInTextAnnotationParser() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Parser\InTextAnnotationParser',
			$this->servicesFactory->newInTextAnnotationParser( $parserData )
		);
	}

	public function testCanConstructContentParser() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ContentParser',
			$this->servicesFactory->newContentParser( $title )
		);
	}

	public function testCanConstructMwCollaboratorFactory() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\MwCollaboratorFactory',
			$this->servicesFactory->newMwCollaboratorFactory()
		);
	}

	public function testCanConstructNamespaceExaminer() {

		$this->assertInstanceOf(
			'\SMW\NamespaceExaminer',
			$this->servicesFactory->getNamespaceExaminer()
		);
	}

	public function testCanConstructDataUpdater() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\DataUpdater',
			$this->servicesFactory->newDataUpdater( $semanticData )
		);
	}

	public function testCanConstructDataItemFactory() {

		$this->assertInstanceOf(
			'\SMW\DataItemFactory',
			$this->servicesFactory->getDataItemFactory()
		);
	}

	public function testCanConstructMaintenanceFactory() {

		$this->assertInstanceOf(
			'\SMW\Maintenance\MaintenanceFactory',
			$this->servicesFactory->newMaintenanceFactory()
		);
	}

	public function testCanConstructCacheFactory() {

		$this->assertInstanceOf(
			'\SMW\CacheFactory',
			$this->servicesFactory->newCacheFactory()
		);
	}

	public function testCanConstructIteratorFactory() {

		$this->assertInstanceOf(
			'\SMW\IteratorFactory',
			$this->servicesFactory->getIteratorFactory()
		);
	}

	public function testCanConstructDataValueFactory() {

		$this->assertInstanceOf(
			'\SMW\DataValueFactory',
			$this->servicesFactory->getDataValueFactory()
		);
	}

	public function testCanConstructPropertySpecificationLookup() {

		$this->assertInstanceOf(
			'\SMW\PropertySpecificationLookup',
			$this->servicesFactory->getPropertySpecificationLookup()
		);
	}

	public function testCanConstructHierarchyLookup() {

		$this->assertInstanceOf(
			'\SMW\HierarchyLookup',
			$this->servicesFactory->newHierarchyLookup()
		);
	}

	public function testCanConstructQueryFactory() {

		$this->assertInstanceOf(
			'\SMW\QueryFactory',
			$this->servicesFactory->getQueryFactory()
		);
	}

	public function testCanConstructPropertyLabelFinder() {

		$this->assertInstanceOf(
			'\SMW\PropertyLabelFinder',
			$this->servicesFactory->getPropertyLabelFinder()
		);
	}

	public function testCanConstructDeferredCallableUpdate() {

		$callback = function() {
			return null;
		};

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Deferred\CallableUpdate',
			$this->servicesFactory->newDeferredCallableUpdate( $callback )
		);
	}

	public function testCanConstructDeferredTransactionalCallableUpdate() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Deferred\TransactionalCallableUpdate',
			$this->servicesFactory->newDeferredTransactionalCallableUpdate( null )
		);
	}

	public function testCanConstructMediaWikiLogger() {

		$this->assertInstanceOf(
			'\Psr\Log\LoggerInterface',
			$this->servicesFactory->getMediaWikiLogger()
		);
	}

	public function testCanConstructEventDispatcher() {

		$this->assertInstanceOf(
			'\Onoi\EventDispatcher\EventDispatcher',
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
			array(),
			'\SMW\Query\Cache\ResultCache'
		];

		$provider[] = [
			'FactboxFactory',
			[],
			'SMW\Factbox\FactboxFactory'
		];

		$provider[] = [
			'PropertyAnnotatorFactory',
			[],
			'SMW\Property\AnnotatorFactory'
		];

		return $provider;
	}

}
