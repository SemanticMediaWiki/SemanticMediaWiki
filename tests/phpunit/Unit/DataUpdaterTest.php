<?php

namespace SMW\Tests\Updater;

use SMW\DataUpdater;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataUpdater
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DataUpdaterTest  extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $transactionalCallableUpdate;
	private $semanticDataFactory;
	private $spyLogger;
	private $store;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( [
			'smwgPageSpecialProperties'       => [],
			'smwgEnableUpdateJobs'            => false,
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true ]
		] );

		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists' ] )
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->store->setLogger( $this->spyLogger );

		$this->testEnvironment->registerObject( 'Store', $this->store );

		$this->transactionalCallableUpdate = $this->getMockBuilder( '\SMW\MediaWiki\Deferred\TransactionalCallableUpdate' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'DeferredTransactionalCallableUpdate', $this->transactionalCallableUpdate );

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
			DataUpdater::class,
			new DataUpdater( $this->store, $semanticData )
		);
	}

	public function testDoUpdateForDefaultSettings() {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$instance = new DataUpdater(
			$this->store,
			$semanticData
		);

		$this->assertTrue(
			$instance->doUpdate()
		);
	}

	public function testDeferredUpdate() {

		$this->transactionalCallableUpdate->expects( $this->once() )
			->method( 'pushUpdate' );

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$instance = new DataUpdater(
			$this->store,
			$semanticData
		);

		$instance->isDeferrableUpdate( true );
		$instance->doUpdate();
	}

	/**
	 * @dataProvider updateJobStatusProvider
	 */
	public function testDoUpdateForValidRevision( $updateJobStatus ) {

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'updateData' );

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$content = $this->getMockBuilder( '\Content' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getContent' )
			->will( $this->returnValue( $content ) );

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

		$instance = new DataUpdater(
			$store,
			$semanticData
		);

		$instance->canCreateUpdateJob(
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
			->setMethods( [ 'exists' ] )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'clearData', 'getObjectIds' ] )
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

		$instance = new DataUpdater(
			$store,
			$semanticData
		);

		$instance->canCreateUpdateJob(
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

		$instance = new DataUpdater(
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

		$instance = new DataUpdater(
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

		$content = $this->getMockBuilder( '\Content' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getContent' )
			->will( $this->returnValue( $content ) );

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

		$source = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

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
			->method( 'changeTitle' );

		$store->setOption( 'smwgSemanticsEnabled', true );
		$store->setOption( 'smwgAutoRefreshSubject', true );

		$store->setLogger( $this->spyLogger );

		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_REDI' ),
			$target
		);

		$instance = new DataUpdater(
			$store,
			$semanticData
		);

		$instance->canCreateUpdateJob( true );
		$instance->doUpdate();
	}

	public function updateJobStatusProvider() {

		$provider = [
			[ true ],
			[ false ]
		];

		return $provider;
	}

}
