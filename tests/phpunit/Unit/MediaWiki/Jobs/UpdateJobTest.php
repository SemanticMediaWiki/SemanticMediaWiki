<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\Tests\TestEnvironment;
use SMW\DIWikiPage;
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
class UpdateJobTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $semanticDataFactory;
	private $semanticDataSerializer;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( array(
			'smwgCacheType'        => 'hash',
			'smwgEnableUpdateJobs' => false,
			'smwgEnabledDeferredUpdate' => false,
			'smwgDVFeatures' => '',
			'smwgSemanticsEnabled' => false
		) );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'exists' ) )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds', 'getPropertyValues', 'updateData' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->semanticDataFactory = $this->testEnvironment->getUtilityFactory()->newSemanticDataFactory();
		$this->semanticDataSerializer = \SMW\ApplicationFactory::getInstance()->newSerializerFactory()->newSemanticDataSerializer();
	}

	protected function tearDown() {
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
			->will( $this->returnValue( true ) );

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
			->will( $this->returnValue( 0 ) );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( false ) );

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
			->will( $this->returnValue( true ) );

		$contentParser = $this->getMockBuilder( '\SMW\ContentParser' )
			->disableOriginalConstructor()
			->getMock();

		$contentParser->expects( $this->once() )
			->method( 'getOutput' )
			->will( $this->returnValue( null ) );

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
			->will( $this->returnValue( __METHOD__ ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( 0 ) );

		$title->expects( $this->once() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$contentParser = $this->getMockBuilder( '\SMW\ContentParser' )
			->disableOriginalConstructor()
			->getMock();

		$contentParser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->will( $this->returnValue( new \ParserOutput ) );

		$this->testEnvironment->registerObject( 'ContentParser', $contentParser );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'exists' ) )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'clearData', 'getObjectIds' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

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
			->will( $this->returnValue( __METHOD__ ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'getNamespace' )
			->will( $this->returnValue( 0 ) );

		$title->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$contentParser = $this->getMockBuilder( '\SMW\ContentParser' )
			->disableOriginalConstructor()
			->getMock();

		$contentParser->expects( $this->atLeastOnce() )
			->method( 'getOutput' )
			->will( $this->returnValue( new \ParserOutput ) );

		$this->testEnvironment->registerObject( 'ContentParser', $contentParser );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'exists' ) )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getPropertyValues', 'getObjectIds' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array() ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new UpdateJob( $title, array( 'pm' => SMW_UJ_PM_CLASTMDATE ) );
		$instance->isEnabledJobQueue( false );

		$this->assertTrue( $instance->run() );
	}

	public function testJobOnSerializedSemanticData() {

		$title = Title::newFromText( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'updateData' ) )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'updateData' );

		$this->testEnvironment->registerObject( 'Store', $store );

		$semanticData = $this->semanticDataSerializer->serialize(
			$this->semanticDataFactory->newEmptySemanticData( __METHOD__ )
		);

		$instance = new UpdateJob( $title,
			array(
				UpdateJob::SEMANTIC_DATA => $semanticData
			)
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
			->setMethods( array( 'updateData', 'getPropertyValues' ) )
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array( new DIBlob( json_encode( $semanticData ) ) ) ) );

		$store->expects( $this->once() )
			->method( 'updateData' );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new UpdateJob( $subject->getTitle(),
			array(
				UpdateJob::CHANGE_PROP => $subject->getSerialization()
			)
		);

		$instance->isEnabledJobQueue( false );

		$instance->run();
	}

}
