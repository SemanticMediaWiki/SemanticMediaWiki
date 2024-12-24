<?php

namespace SMW\Tests\MediaWiki\Jobs;

use MediaWiki\DAO\WikiAwareEntity;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\Tests\TestEnvironment;
use SMWDIBlob as DIBlob;
use Title;

/**
 * @covers \SMW\MediaWiki\Jobs\UpdateJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class UpdateJobTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $semanticDataFactory;
	private $semanticDataSerializer;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( [
			'smwgMainCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false,
			'smwgEnabledDeferredUpdate' => false,
			'smwgDVFeatures' => '',
		] );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds', 'getPropertyValues', 'updateData' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$this->testEnvironment->registerObject( 'Store', $store );

		$revisionGuard = $this->getMockBuilder( '\SMW\MediaWiki\RevisionGuard' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'RevisionGuard', $revisionGuard );

		$this->semanticDataFactory = $this->testEnvironment->getUtilityFactory()->newSemanticDataFactory();
		$this->semanticDataSerializer = \SMW\ApplicationFactory::getInstance()->newSerializerFactory()->newSemanticDataSerializer();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\UpdateJob',
			new UpdateJob( $title )
		);

		// FIXME Delete SMWUpdateJob assertion after all
		// references to SMWUpdateJob have been removed
		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\UpdateJob',
			new \SMWUpdateJob( $title )
		);
	}

	public function testJobWithMissingParserOutput() {
		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->willReturn( true );

		$instance = new UpdateJob( $title );
		$instance->isEnabledJobQueue( false );

		$this->assertFalse(	$instance->run() );
	}

	public function testJobWithInvalidTitle() {
		$title = $this->getMockBuilder( 'Title' )
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

		$this->testEnvironment->registerObject( 'ContentParser', null );

		$instance = new UpdateJob( $title );
		$instance->isEnabledJobQueue( false );

		$this->assertTrue( $instance->run() );
	}

	public function testJobWithNoRevisionAvailable() {
		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$contentParser = $this->getMockBuilder( '\SMW\ContentParser' )
			->disableOriginalConstructor()
			->getMock();

		$contentParser->expects( $this->once() )
			->method( 'getOutput' )
			->willReturn( null );

		$this->testEnvironment->registerObject( 'ContentParser', $contentParser );

		$instance = new UpdateJob( $title );
		$instance->isEnabledJobQueue( false );

		$this->assertFalse( $instance->run() );
	}

	public function testJobWithValidRevision() {
		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->atLeastOnce() )
			->method( 'getDBkey' )
			->willReturn( __METHOD__ );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->willReturn( 0 );

		$title->expects( $this->once() )
			->method( 'exists' )
			->willReturn( true );

		$contentParser = $this->getMockBuilder( '\SMW\ContentParser' )
			->disableOriginalConstructor()
			->getMock();

		$contentParser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( new \ParserOutput );

		$this->testEnvironment->registerObject( 'ContentParser', $contentParser );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$idTable->expects( $this->any() )
			->method( 'findAssociatedRev' )
			->willReturn( 42 );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'clearData', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->once() )
			->method( 'clearData' );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new UpdateJob( $title );
		$instance->isEnabledJobQueue( false );

		$this->assertTrue( $instance->run() );
	}

	public function testJobToCompareLastModified() {
		$title = $this->getMockBuilder( 'Title' )
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

		$contentParser = $this->getMockBuilder( '\SMW\ContentParser' )
			->disableOriginalConstructor()
			->getMock();

		$contentParser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->willReturn( new \ParserOutput );

		$this->testEnvironment->registerObject( 'ContentParser', $contentParser );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getPropertyValues', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new UpdateJob( $title, [ 'shallowUpdate' => true ] );
		$instance->isEnabledJobQueue( false );

		$this->assertTrue( $instance->run() );
	}

	public function testJobOnSerializedSemanticData() {
		$title = Title::newFromText( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'updateData' );

		$this->testEnvironment->registerObject( 'Store', $store );

		$semanticData = $this->semanticDataSerializer->serialize(
			$this->semanticDataFactory->newEmptySemanticData( __METHOD__ )
		);

		$instance = new UpdateJob( $title,
			[
				UpdateJob::SEMANTIC_DATA => $semanticData
			]
		);

		$instance->isEnabledJobQueue( false );

		$this->assertTrue(
			$instance->run()
		);
	}

	public function testJobOnChangePropagation() {
		$subject = DIWikiPage::newFromText( __METHOD__, SMW_NS_PROPERTY );

		$semanticData = $this->semanticDataSerializer->serialize(
			$this->semanticDataFactory->newEmptySemanticData( __METHOD__ )
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'updateData', 'getPropertyValues' ] )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ new DIBlob( json_encode( $semanticData ) ) ] );

		$store->expects( $this->once() )
			->method( 'updateData' );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new UpdateJob( $subject->getTitle(),
			[
				UpdateJob::CHANGE_PROP => $subject->getSerialization()
			]
		);

		$instance->isEnabledJobQueue( false );

		$instance->run();
	}

}
