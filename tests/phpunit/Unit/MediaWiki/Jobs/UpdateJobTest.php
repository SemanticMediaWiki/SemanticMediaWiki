<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\DAO\WikiAwareEntity;
use MediaWiki\Title\Title;
use ParserOutput;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\DataItems\Blob;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Jobs\ContentParserFactory;
use SMW\MediaWiki\Jobs\PageUpdaterFactory;
use SMW\MediaWiki\Jobs\ParserDataFactory;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\PageUpdater;
use SMW\Parser\ContentParser;
use SMW\ParserData;
use SMW\SerializerFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Settings;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Jobs\UpdateJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class UpdateJobTest extends TestCase {

	private TestEnvironment $testEnvironment;
	private $semanticDataFactory;
	private $semanticDataSerializer;
	private Settings $settings;
	private $pageCreator;
	private $pageUpdaterFactory;
	private $serializerFactory;
	private $contentParser;
	private $contentParserFactory;
	private $parserData;
	private $parserDataFactory;
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( [
			'smwgMainCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false,
			'smwgEnabledDeferredUpdate' => false,
			'smwgDVFeatures' => '',
		] );

		$this->semanticDataFactory = $this->testEnvironment->getUtilityFactory()->newSemanticDataFactory();
		$this->semanticDataSerializer = ApplicationFactory::getInstance()->newSerializerFactory()->newSemanticDataSerializer();

		$this->settings = Settings::newFromArray( [ 'smwgEnableUpdateJobs' => false ] );

		$this->pageCreator = $this->createMock( PageCreator::class );

		$pageUpdater = $this->createMock( PageUpdater::class );
		$this->pageUpdaterFactory = $this->createMock( PageUpdaterFactory::class );
		$this->pageUpdaterFactory->method( 'newPageUpdater' )->willReturn( $pageUpdater );

		// Use a real SerializerFactory; the SEMANTIC_DATA / CHANGE_PROP
		// paths need to round-trip real data through its deserializer.
		$this->serializerFactory = new SerializerFactory(
			$this->createMock( Store::class )
		);

		$this->contentParser = $this->createMock( ContentParser::class );
		$this->contentParserFactory = $this->createMock( ContentParserFactory::class );
		$this->contentParserFactory->method( 'newContentParser' )->willReturn( $this->contentParser );

		$this->parserData = $this->createMock( ParserData::class );
		// updateStore() reaches `$parserData->getSemanticData()->setOption(...)`,
		// so a non-null SemanticData is needed for chains that reach updateStore.
		$this->parserData->method( 'getSemanticData' )
			->willReturn( $this->semanticDataFactory->newEmptySemanticData( 'UpdateJobTestSubject' ) );
		$this->parserDataFactory = $this->createMock( ParserDataFactory::class );
		$this->parserDataFactory->method( 'newParserData' )->willReturn( $this->parserData );

		$this->logger = $this->createMock( LoggerInterface::class );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newStoreWithIdTable(): SQLStore {
		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getPropertyValues', 'updateData' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		return $store;
	}

	private function newUpdateJob( Title $title, array $params, Store $store ): UpdateJob {
		return new UpdateJob(
			$title,
			$params,
			$store,
			$this->settings,
			$this->pageCreator,
			$this->pageUpdaterFactory,
			$this->serializerFactory,
			$this->contentParserFactory,
			$this->parserDataFactory,
			$this->logger
		);
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			UpdateJob::class,
			$this->newUpdateJob( $title, [], $this->newStoreWithIdTable() )
		);
	}

	public function testJobWithMissingParserOutput() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$instance = $this->newUpdateJob( $title, [], $this->newStoreWithIdTable() );
		$instance->isEnabledJobQueue( false );

		$this->assertFalse( $instance->run() );
	}

	public function testJobWithInvalidTitle() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( 0 );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$title->expects( $this->once() )
			->method( 'exists' )
			->willReturn( false );

		$instance = $this->newUpdateJob( $title, [], $this->newStoreWithIdTable() );
		$instance->isEnabledJobQueue( false );

		$this->assertTrue( $instance->run() );
	}

	public function testJobWithNoRevisionAvailable() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$this->contentParser->expects( $this->once() )
			->method( 'getOutput' )
			->willReturn( null );

		$instance = $this->newUpdateJob( $title, [], $this->newStoreWithIdTable() );
		$instance->isEnabledJobQueue( false );

		$this->assertFalse( $instance->run() );
	}

	public function testJobWithValidRevision() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getDBkey' )
			->willReturn( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( 0 );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$this->contentParser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( new ParserOutput );

		// The injected ParserData mock receives the persist call once the
		// parsed output has been threaded back through ParserDataFactory.
		$this->parserData->expects( $this->atLeastOnce() )
			->method( 'updateStore' );

		$instance = $this->newUpdateJob( $title, [], $this->newStoreWithIdTable() );
		$instance->isEnabledJobQueue( false );

		$this->assertTrue( $instance->run() );
	}

	public function testJobToCompareLastModified() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getDBkey' )
			->willReturn( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( 0 );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$title->expects( $this->any() )
			->method( 'canExist' )
			->willReturn( true );

		if ( method_exists( $title, 'getWikiId' ) ) {
			$title->expects( $this->any() )
				->method( 'getWikiId' )
				->willReturn( WikiAwareEntity::LOCAL );
		}

		$this->contentParser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( new ParserOutput );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyValues', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$instance = $this->newUpdateJob( $title, [ 'shallowUpdate' => true ], $store );
		$instance->isEnabledJobQueue( false );

		$this->assertTrue( $instance->run() );
	}

	public function testJobOnSerializedSemanticData() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$semanticData = $this->semanticDataSerializer->serialize(
			$this->semanticDataFactory->newEmptySemanticData( __METHOD__ )
		);

		// `set_data()` threads the deserialized SemanticData through
		// ParserDataFactory; the injected ParserData mock must see updateStore.
		$this->parserData->expects( $this->atLeastOnce() )
			->method( 'updateStore' );

		$instance = $this->newUpdateJob( $title,
			[
				UpdateJob::SEMANTIC_DATA => $semanticData
			],
			$this->newStoreWithIdTable()
		);

		$instance->isEnabledJobQueue( false );

		$this->assertTrue(
			$instance->run()
		);
	}

	public function testJobOnChangePropagation() {
		$subject = WikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY );

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$semanticData = $this->semanticDataSerializer->serialize(
			$this->semanticDataFactory->newEmptySemanticData( __METHOD__ )
		);

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyValues' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ new Blob( json_encode( $semanticData ) ) ] );

		// CHANGE_PROP flows through change_propagation -> set_data, which
		// hits ParserData::updateStore via the injected factory.
		$this->parserData->expects( $this->atLeastOnce() )
			->method( 'updateStore' );

		$instance = $this->newUpdateJob( $title,
			[
				UpdateJob::CHANGE_PROP => $subject->getSerialization()
			],
			$store
		);

		$instance->isEnabledJobQueue( false );

		$instance->run();
	}

}
