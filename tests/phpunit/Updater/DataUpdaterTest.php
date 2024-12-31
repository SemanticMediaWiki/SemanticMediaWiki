<?php

namespace SMW\Tests\Updater;

use SMW\DataUpdater;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Property\SpecificationLookup;
use SMW\SemanticData;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\DataUpdater
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DataUpdaterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $transactionalCallableUpdate;
	private $semanticDataFactory;
	private $spyLogger;
	private $store;
	private $changePropagationNotifier;
	private $eventDispatcher;
	private $revisionGuard;
	private SpecificationLookup $propertySpecificationLookup;
	private $revision;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment( [
			'smwgPageSpecialProperties'       => [],
			'smwgEnableUpdateJobs'            => false,
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true, SMW_NS_SCHEMA => true ]
		] );

		$this->spyLogger = $this->testEnvironment->newSpyLogger();

		$this->revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
			->disableOriginalConstructor()
			->getMock();

		$this->eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->revisionGuard = $this->getMockBuilder( '\SMW\MediaWiki\RevisionGuard' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\Property\SpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->changePropagationNotifier = $this->getMockBuilder( '\SMW\Property\ChangePropagationNotifier' )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'exists' ] )
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getObjectIds', 'getConnection', 'getPropertyValues', 'updateData' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->setLogger( $this->spyLogger );

		$jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'JobQueue', $jobQueue );
		$this->testEnvironment->registerObject( 'Store', $this->store );
		$this->testEnvironment->registerObject( 'RevisionGuard', $this->revisionGuard );
		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );

		$this->semanticDataFactory = $this->testEnvironment->getUtilityFactory()->newSemanticDataFactory();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			DataUpdater::class,
			new DataUpdater( $this->store, $semanticData, $this->changePropagationNotifier )
		);
	}

	public function testDoUpdateForDefaultSettings() {
		$this->revisionGuard->expects( $this->any() )
			->method( 'getRevision' )
			->willReturn( $this->revision );

		$this->eventDispatcher->expects( $this->once() )
			->method( 'dispatch' )
			->with( \SMW\Listener\EventListener\EventListeners\InvalidatePropertySpecificationLookupCacheEventListener::EVENT_ID );

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$instance = new DataUpdater(
			$this->store,
			$semanticData,
			$this->changePropagationNotifier
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$instance->setRevisionGuard(
			$this->revisionGuard
		);

		$this->assertTrue(
			$instance->doUpdate()
		);
	}

	public function testDeferredUpdate() {
		$this->revisionGuard->expects( $this->any() )
			->method( 'getRevision' )
			->willReturn( $this->revision );

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$instance = new DataUpdater(
			$this->store,
			$semanticData,
			$this->changePropagationNotifier
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$instance->setRevisionGuard(
			$this->revisionGuard
		);

		$instance->setLogger( $this->spyLogger );
		$instance->isDeferrableUpdate( true );
		$instance->doUpdate();

		$this->assertContains(
			'DeferrableUpdate',
			$this->spyLogger->getMessagesAsString()
		);
	}

	/**
	 * @dataProvider updateJobStatusProvider
	 */
	public function testDoUpdateForValidRevision( $updateJobStatus ) {
		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'updateData' ] )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'updateData' );

		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
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
			->willReturn( $content );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$this->revisionGuard->expects( $this->any() )
			->method( 'newRevisionFromPage' )
			->willReturn( $revision );

		$this->revisionGuard->expects( $this->any() )
			->method( 'getRevision' )
			->willReturn( $revision );

		$instance = new DataUpdater(
			$store,
			$semanticData,
			$this->changePropagationNotifier
		);

		$instance->setRevisionGuard(
			$this->revisionGuard
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
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
			->onlyMethods( [ 'exists' ] )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'clearData', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->once() )
			->method( 'clearData' )
			->with( $semanticData->getSubject() );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$this->revisionGuard->expects( $this->any() )
			->method( 'getRevision' )
			->willReturn( null );

		$instance = new DataUpdater(
			$store,
			$semanticData,
			$this->changePropagationNotifier
		);

		$instance->canCreateUpdateJob(
			$updateJobStatus
		);

		$instance->setRevisionGuard(
			$this->revisionGuard
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
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
			$semanticData,
			$this->changePropagationNotifier
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$this->assertIsBool(

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
			$semanticData,
			$this->changePropagationNotifier
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$this->assertFalse(
			$instance->doUpdate()
		);
	}

	public function testDoUpdateForSchema() {
		$wikiPage = new DIWikiPage(
			'Foo',
			SMW_NS_SCHEMA,
			''
		);

		$semanticData = $this->semanticDataFactory->setSubject( $wikiPage )->newEmptySemanticData();

		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'exists' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'updateData' ] )
			->getMock();

		$store->expects( $this->once() )
			->method( 'updateData' );

		$wikiPage = $this->getMockBuilder( '\WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
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
			->willReturn( $content );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$this->revisionGuard->expects( $this->any() )
			->method( 'getRevision' )
			->willReturn( $revision );

		$instance = new DataUpdater(
			$store,
			$semanticData,
			$this->changePropagationNotifier
		);

		$instance->setRevisionGuard(
			$this->revisionGuard
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$instance->canCreateUpdateJob(
			true
		);

		$this->assertTrue(
			$instance->doUpdate()
		);
	}

	public function testForYetUnknownRedirectTarget() {
		$revision = $this->getMockBuilder( '\MediaWiki\Revision\RevisionRecord' )
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
			->willReturn( $content );

		$pageCreator = $this->getMockBuilder( '\SMW\MediaWiki\PageCreator' )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$propertySpecificationLookup = $this->getMockBuilder( '\SMW\Property\SpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );
		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $propertySpecificationLookup );

		$this->revisionGuard->expects( $this->any() )
			->method( 'getRevision' )
			->willReturn( $revision );

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

		$store->setOption( 'smwgAutoRefreshSubject', true );

		$store->setLogger( $this->spyLogger );

		$semanticData = new SemanticData( $subject );

		$semanticData->addPropertyObjectValue(
			new DIProperty( '_REDI' ),
			$target
		);

		$instance = new DataUpdater(
			$store,
			$semanticData,
			$this->changePropagationNotifier
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$instance->setRevisionGuard(
			$this->revisionGuard
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
