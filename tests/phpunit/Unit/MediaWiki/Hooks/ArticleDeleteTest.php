<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\EventDispatcher\EventDispatcher;
use SMW\MediaWiki\Hooks\ArticleDelete;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateDispatcherJob;
use SMW\SerializerFactory;
use SMW\Serializers\SemanticDataSerializer;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticleDelete
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ArticleDeleteTest extends TestCase {

	private $testEnvironment;
	private $jobFactory;
	private $eventDispatcher;
	private $serializerFactory;
	private $servicesFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment(
			[
				'smwgEnableUpdateJobs' => false,
				'smwgEnabledDeferredUpdate' => false
			]
		);

		$this->jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticDataSerializer = $this->createMock( SemanticDataSerializer::class );
		$semanticDataSerializer->method( 'serialize' )->willReturn( [] );

		$this->serializerFactory = $this->createMock( SerializerFactory::class );
		$this->serializerFactory->method( 'newSemanticDataSerializer' )
			->willReturn( $semanticDataSerializer );

		$this->servicesFactory = ApplicationFactory::getInstance();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new ArticleDelete( $store, $this->jobFactory, $this->eventDispatcher, $this->serializerFactory, $this->servicesFactory );

		$this->assertInstanceOf(
			ArticleDelete::class,
			$instance
		);
	}

	public function testProcess() {
		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$updateDispatcherJob = $this->getMockBuilder( UpdateDispatcherJob::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobFactory->expects( $this->atLeastOnce() )
			->method( 'newUpdateDispatcherJob' )
			->willReturn( $updateDispatcherJob );

		$subject = WikiPage::newFromText( __METHOD__ );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'deleteSubject' );

		$store->expects( $this->atLeastOnce() )
			->method( 'getInProperties' )
			->willReturn( [ new Property( 'Foo' ) ] );

		$store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ] );

		$instance = new ArticleDelete(
			$store,
			$this->jobFactory,
			$this->eventDispatcher,
			$this->serializerFactory,
			$this->servicesFactory
		);

		$instance->doDelete( $subject->getTitle() );
	}

}
