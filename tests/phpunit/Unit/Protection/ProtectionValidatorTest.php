<?php

namespace SMW\Tests\Unit\Protection;

use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\EntityCache;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\Listener\ChangeListener\ChangeRecord;
use SMW\MediaWiki\PageCreator;
use SMW\MediaWiki\PermissionManager;
use SMW\Protection\ProtectionValidator;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use Wikimedia\ObjectCache\HashBagOStuff;
use WikiPage;

/**
 * @covers \SMW\Protection\ProtectionValidator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since  2.5
 *
 * @author mwjames
 */
class ProtectionValidatorTest extends TestCase {

	private $dataItemFactory;
	private $store;
	private $entityCache;
	private $permissionManager;
	private $pageCreator;
	private $jobQueue;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->entityCache = $this->getMockBuilder( EntityCache::class )
			->disableOriginalConstructor()
			->setMethods( [ 'save', 'contains', 'fetch', 'associate', 'invalidate', 'delete' ] )
			->getMock();

		$this->permissionManager = $this->getMockBuilder( PermissionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->registerObject( 'JobQueue', $this->jobQueue );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ProtectionValidator::class,
			new ProtectionValidator( $this->store, $this->entityCache, $this->permissionManager, $this->pageCreator )
		);
	}

	public function testSetGetEditProtectionRight() {
		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$instance->setEditProtectionRight(
			'foo'
		);

		$this->assertEquals(
			'foo',
			$instance->getEditProtectionRight()
		);
	}

	public function testHasEditProtectionOnNamespace() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN, '', 'Bar' );
		$property = $this->dataItemFactory->newDIProperty( '_EDIP' );

		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->willReturn( false );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$subject->asBase(),
				$property )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( true ) ] );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$instance->setEditProtectionRight(
			'foo'
		);

		$this->assertTrue(
			$instance->hasEditProtectionOnNamespace( $subject->getTitle() )
		);
	}

	public function testHasProtection() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN, '', 'Bar' );
		$property = $this->dataItemFactory->newDIProperty( '_EDIP' );

		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->willReturn( false );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$subject->asBase(),
				$property )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( true ) ] );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$this->assertTrue(
			$instance->hasProtection( $subject->getTitle() )
		);
	}

	public function testHasProtectionFromCache() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN, '', 'Bar' );

		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->willReturn( true );

		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$this->assertFalse(
			$instance->hasProtection( $subject->getTitle() )
		);
	}

	public function testHasChangePropagationProtectionOnCategory_FromCache() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_CATEGORY );

		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->willReturn( true );

		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$this->assertFalse(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testHasChangePropagationProtectionOnCategory_Disabled() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_CATEGORY );

		$this->entityCache->expects( $this->never() )
			->method( 'contains' );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$instance->setChangePropagationProtection(
			false
		);

		$this->assertFalse(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testNoChangePropagationProtectionOnCategory_FromCache() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_CATEGORY );

		$this->entityCache->expects( $this->once() )
			->method( 'contains' )
			->willReturn( true );

		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( 'yes' );

		$this->jobQueue->expects( $this->any() )
			->method( 'hasPendingJob' )
			->willReturn( true );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$this->assertTrue(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testStaleChangePropagationMarkerDoesNotProtectWhenJobQueueEmpty() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_PROPERTY );

		// The `_CHGPRO` marker is (still) present ...
		$this->entityCache->expects( $this->any() )
			->method( 'contains' )
			->willReturn( true );

		$this->entityCache->expects( $this->any() )
			->method( 'fetch' )
			->willReturn( 'yes' );

		// ... but no change propagation job is actually pending (#4344: an orphaned
		// marker left behind by a failed/lost dispatch job must not lock the page
		// indefinitely).
		$this->jobQueue->expects( $this->any() )
			->method( 'hasPendingJob' )
			->willReturn( false );

		$this->jobQueue->expects( $this->any() )
			->method( 'getQueueSize' )
			->willReturn( 0 );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$this->assertFalse(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testChangePropagationProtectionAppliesWhileJobPending() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_PROPERTY );

		$this->entityCache->expects( $this->any() )
			->method( 'contains' )
			->willReturn( true );

		$this->entityCache->expects( $this->any() )
			->method( 'fetch' )
			->willReturn( 'yes' );

		$this->jobQueue->expects( $this->any() )
			->method( 'hasPendingJob' )
			->willReturn( true );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$this->assertTrue(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testNoChangePropagationProtectionOnCategory_WithFalseSetting() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_CATEGORY );

		$this->entityCache->expects( $this->never() )
			->method( 'contains' );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$instance->setChangePropagationProtection(
			false
		);

		$this->assertFalse(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testEditProtectionCacheIsNotPoisonedByChangePropagationCheck() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_PROPERTY );

		// The page carries no `_CHGPRO` (no change propagation) but is edit
		// protected (`_EDIP` = true).
		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturnCallback( function ( $subj, $property ) {
				return $property->getKey() === '_EDIP'
					? [ $this->dataItemFactory->newDIBoolean( true ) ]
					: [];
			} );

		// Use a real cache so the two protection checks share (or, once fixed, do
		// not share) the same slot.
		$realEntityCache = new EntityCache( new HashBagOStuff() );

		$instance = new ProtectionValidator(
			$this->store,
			$realEntityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		// A change-propagation check runs first and caches its own (`_CHGPRO`)
		// result; it must not poison the unrelated edit-protection (`_EDIP`)
		// lookup for the same page.
		$instance->hasChangePropagationProtection( $subject->getTitle() );

		$this->assertTrue(
			$instance->hasProtection( $subject->getTitle() )
		);
	}

	public function testChangePropagationCacheIsNotPoisonedByEditProtectionCheck() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_PROPERTY );

		// The page is edit protected (`_EDIP` = true) but has no `_CHGPRO`.
		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturnCallback( function ( $subj, $property ) {
				return $property->getKey() === '_EDIP'
					? [ $this->dataItemFactory->newDIBoolean( true ) ]
					: [];
			} );

		$realEntityCache = new EntityCache( new HashBagOStuff() );

		$instance = new ProtectionValidator(
			$this->store,
			$realEntityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		// An edit-protection check runs first and caches its (`_EDIP`) result; it
		// must not make the unrelated change-propagation check report a lock.
		$instance->hasProtection( $subject->getTitle() );

		$this->assertFalse(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testSetGetCreateProtectionRight() {
		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$instance->setCreateProtectionRight(
			'foo'
		);

		$this->assertEquals(
			'foo',
			$instance->getCreateProtectionRight()
		);
	}

	public function testHasCreateProtection() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionManager->expects( $this->once() )
			->method( 'userCan' )
			->with(
				'edit',
				null,
				$title )
			->willReturn( false );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$instance->setCreateProtectionRight(
			'foo'
		);

		$this->assertTrue(
			$instance->hasCreateProtection( $title )
		);
	}

	public function testHasCreateProtection_NullTitle() {
		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$this->assertFalse(
			$instance->hasCreateProtection( null )
		);
	}

	public function testHasEditProtection_NullTitle() {
		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$this->assertFalse(
			$instance->hasEditProtection( null )
		);
	}

	public function testIsClassifiedAsImportPerformerProtected_NoImportersNoProtection() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$this->assertFalse(
			$instance->isClassifiedAsImportPerformerProtected( $title, $user )
		);
	}

	public function testIsClassifiedAsImportPerformerProtected_CreatorAndCurrentUserDontMatch() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( SMW_NS_SCHEMA );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'FooSchema' );

		$wikiPage = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getCreator' )
			->willReturn( User::newFromName( 'FooImporter', false ) );

		$pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$pageCreator
		);

		$instance->setImportPerformers(
			[ 'FooImporter' ]
		);

		$this->assertTrue(
			$instance->isClassifiedAsImportPerformerProtected( $title, $user )
		);
	}

	public function testIsClassifiedAsNotImportPerformerProtected_CreatorAndCurrentUserDoMatch() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( SMW_NS_SCHEMA );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'FooSchema' );

		$wikiPage = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$wikiPage->expects( $this->atLeastOnce() )
			->method( 'getCreator' )
			->willReturn( User::newFromName( 'FooImporter', false ) );

		$pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'FooImporter' );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$pageCreator
		);

		$instance->setImportPerformers(
			[ 'FooImporter' ]
		);

		$this->assertFalse(
			$instance->isClassifiedAsImportPerformerProtected( $title, $user )
		);
	}

	public function testRegisterPropertyChangeListener() {
		$propertyChangeListener = $this->getMockBuilder( PropertyChangeListener::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyChangeListener->expects( $this->once() )
			->method( 'addListenerCallback' )
			->with(
				$this->dataItemFactory->newDIProperty( '_CHGPRO' ),
				$this->anything() );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$instance->registerPropertyChangeListener( $propertyChangeListener );
	}

	public function testInvalidateCacheFromChangeRecord() {
		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getDataItemById' )
			->willReturn( $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$this->entityCache->expects( $this->once() )
			->method( 'delete' )
			->with( $this->stringContains( 'smw:entity:ea54787292d320f8940f3447754fae22' ) );

		$changeRecord = new ChangeRecord(
			[
				new ChangeRecord( [ 'row' => [ 's_id' => 42 ] ] )
			]
		);

		$instance = new ProtectionValidator(
			$store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$property = $this->dataItemFactory->newDIProperty( '_CHGPRO' );

		$instance->invalidateCache( $property, $changeRecord );
	}

	public function testCacheStateChangeFromChangeRecord() {
		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getDataItemById' )
			->willReturn( $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$this->entityCache->expects( $this->once() )
			->method( 'save' )
			->with( $this->stringContains( 'smw:entity:ea54787292d320f8940f3447754fae22' ) );

		$changeRecord = new ChangeRecord(
			[
				new ChangeRecord( [ 'row' => [ 's_id' => 42 ], 'is_insert' => true ] )
			]
		);

		$instance = new ProtectionValidator(
			$store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator
		);

		$property = $this->dataItemFactory->newDIProperty( '_CHGPRO' );

		$instance->invalidateCache( $property, $changeRecord );
	}

}
