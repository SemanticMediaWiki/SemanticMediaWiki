<?php

namespace SMW\Tests;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\StoreUpdater;

/**
 * @covers \SMW\StoreUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class StoreUpdaterTest  extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $semanticDataFactory;
	private $store;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( array(
			'smwgPageSpecialProperties'       => array(),
			'smwgEnableUpdateJobs'            => false,
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true )
		) );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'hasIDFor' ) )
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds' ) )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->testEnvironment->registerObject( 'Store', $this->store );
		$this->semanticDataFactory = $this->testEnvironment->getUtilityFactory()->newSemanticDataFactory();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\StoreUpdater',
			new StoreUpdater( $this->store, $semanticData )
		);
	}

	public function testDoUpdateForDefaultSettings() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$instance = new StoreUpdater(
			$this->store,
			$semanticData
		);

		$this->assertTrue(
			$instance->doUpdate()
		);
	}

	/**
	 * @dataProvider updateJobStatusProvider
	 */
	public function testDoUpdateForValidRevision( $updateJobStatus ) {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'updateData' ) )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'updateData' );

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getRevision' )
			->will( $this->returnValue( $revision ) );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$instance = new StoreUpdater(
			$store,
			$semanticData
		);

		$instance->setUpdateJobsEnabledState(
			$updateJobStatus
		);

		$this->assertTrue(
			$instance->doUpdate()
		);
	}

	/**
	 * @dataProvider updateJobStatusProvider
	 */
	public function testDoUpdateForNullRevision( $updateJobStatus ) {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'hasIDFor' ) )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'hasIDFor' )
			->will( $this->returnValue( true ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'clearData', 'getObjectIds' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$store->expects( $this->once() )
			->method( 'clearData' )
			->with( $this->equalTo( $semanticData->getSubject() ) );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$instance = new StoreUpdater(
			$store,
			$semanticData
		);

		$instance->setUpdateJobsEnabledState(
			$updateJobStatus
		);

		$this->assertTrue(
			$instance->doUpdate()
		);
	}

	public function testDoUpdateForTitleInUnknownNs() {

		$wikiPage = new DIWikiPage(
			'Foo',
			-32768, // This namespace does not exist
			''
		);

		$semanticData = $this->semanticDataFactory->setSubject( $wikiPage )->newEmptySemanticData();

		$instance = new StoreUpdater(
			$this->store,
			$semanticData
		);

		$this->assertInternalType(
			'boolean',
			$instance->doUpdate()
		);
	}

	public function testDoUpdateForSpecialPage() {

		$wikiPage = new DIWikiPage(
			'Foo',
			NS_SPECIAL,
			''
		);

		$semanticData = $this->semanticDataFactory->setSubject( $wikiPage )->newEmptySemanticData();

		$instance = new StoreUpdater(
			$this->store,
			$semanticData
		);

		$this->assertFalse(
			$instance->doUpdate()
		);
	}

	public function testForYetUnknownRedirectTarget() {

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getRevision' )
			->will( $this->returnValue( $revision ) );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->will( $this->returnValue( $wikiPage ) );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$subject = new DIWikiPage(
			'Foo',
			NS_MAIN
		);

		$target = new DIWikiPage(
			'Bar',
			NS_MAIN
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'changeTitle' )
			->with(
				$this->equalTo( $subject->getTitle() ),
				$this->equalTo( $target->getTitle() ),
				$this->anything(),
				$this->anything() );

		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_REDI' ),
			$target
		);

		$instance = new StoreUpdater(
			$store,
			$semanticData
		);

		$instance->setUpdateJobsEnabledState( true );
		$instance->doUpdate();
	}

	public function updateJobStatusProvider() {

		$provider = array(
			array( true ),
			array( false )
		);

		return $provider;
	}

}
