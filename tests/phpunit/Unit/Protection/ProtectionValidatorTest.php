<?php

namespace SMW\Tests\Unit\Protection;

use MediaWiki\Permissions\RestrictionStore;
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
use SMW\Tests\Utils\EntityCacheFactory;
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
	private $restrictionStore;
	private $jobQueue;
	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->entityCache = EntityCacheFactory::newEntityCache();

		$this->permissionManager = $this->getMockBuilder( PermissionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->restrictionStore = $this->getMockBuilder( RestrictionStore::class )
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

	private function newProtectionValidator(): ProtectionValidator {
		return new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator,
			$this->restrictionStore
		);
	}

	private function expectEditRestriction( string $right ): void {
		$this->restrictionStore->method( 'isProtected' )
			->willReturn( true );

		$this->restrictionStore->method( 'getRestrictions' )
			->willReturn( [ $right ] );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ProtectionValidator::class,
			$this->newProtectionValidator()
		);
	}

	public function testSetGetEditProtectionRight() {
		$instance = $this->newProtectionValidator();

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

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$subject->asBase(),
				$property )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( true ) ] );

		$instance = $this->newProtectionValidator();

		$instance->setEditProtectionRight(
			'foo'
		);

		$this->expectEditRestriction( 'foo' );

		$this->assertTrue(
			$instance->hasEditProtectionOnNamespace( $subject->getTitle() )
		);
	}

	public function testHasProtection() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN, '', 'Bar' );
		$property = $this->dataItemFactory->newDIProperty( '_EDIP' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$subject->asBase(),
				$property )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( true ) ] );

		$instance = $this->newProtectionValidator();
		$instance->setEditProtectionRight( 'smw-pageedit' );
		$this->expectEditRestriction( 'smw-pageedit' );

		$this->assertTrue(
			$instance->hasProtection( $subject->getTitle() )
		);
	}

	public function testHasProtectionIgnoresAnnotationWithoutAMediaWikiEditRestriction() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN, '', 'Bar' );

		// `_EDIP` is stored (the `[[Is edit protected::true]]` annotation) ...
		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( true ) ] );

		// ... but no MediaWiki edit restriction is in place, the state an editor
		// lacking the edit-protection right leaves behind (EditProtectionUpdater
		// skips the restriction). The stored flag alone must not protect the page.
		$this->restrictionStore->method( 'isProtected' )
			->willReturn( false );

		$instance = $this->newProtectionValidator();
		$instance->setEditProtectionRight( 'smw-pageedit' );

		$this->assertFalse(
			$instance->hasProtection( $subject->getTitle() )
		);
	}

	public function testHasProtectionIgnoresAnEditRestrictionAtADifferentRight() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN, '', 'Bar' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( true ) ] );

		// The page is edit protected, but at a different right (core `sysop`) than
		// the configured edit-protection right, so the `_EDIP` annotation is not the
		// cause of the restriction and must not be honoured as annotation protection.
		$this->restrictionStore->method( 'isProtected' )
			->willReturn( true );

		$this->restrictionStore->method( 'getRestrictions' )
			->willReturn( [ 'sysop' ] );

		$instance = $this->newProtectionValidator();
		$instance->setEditProtectionRight( 'smw-pageedit' );

		$this->assertFalse(
			$instance->hasProtection( $subject->getTitle() )
		);
	}

	public function testHasProtectionReadsTheStoreOnlyOnce() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN, '', 'Bar' );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$instance = $this->newProtectionValidator();

		$instance->hasProtection( $subject->getTitle() );

		$this->assertFalse(
			$instance->hasProtection( $subject->getTitle() )
		);
	}

	public function testEditProtectionSetToFalseDoesNotProtect() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( false ) ] );

		$instance = $this->newProtectionValidator();

		$this->assertFalse(
			$instance->hasProtection( $subject->getTitle() )
		);
	}

	public function testNoChangePropagationProtectionOnCategoryWithoutMarker() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_CATEGORY );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->willReturn( [] );

		$this->jobQueue->expects( $this->any() )
			->method( 'hasPendingJob' )
			->willReturn( true );

		$instance = $this->newProtectionValidator();

		$this->assertFalse(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testHasChangePropagationProtectionOnCategory_Disabled() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_CATEGORY );

		$this->store->expects( $this->never() )
			->method( 'getPropertyValues' );

		$instance = $this->newProtectionValidator();

		$instance->setChangePropagationProtection(
			false
		);

		$this->assertFalse(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testChangePropagationProtectionOnCategoryWhileJobPending() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_CATEGORY );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'a marker' ) ] );

		$this->jobQueue->expects( $this->any() )
			->method( 'hasPendingJob' )
			->willReturn( true );

		$instance = $this->newProtectionValidator();

		$this->assertTrue(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testStaleChangePropagationMarkerDoesNotProtectWhenJobQueueEmpty() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_PROPERTY );

		// The `_CHGPRO` marker is (still) present ...
		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'a marker' ) ] );

		// ... but no change propagation job is actually pending (#4344: an orphaned
		// marker left behind by a failed/lost dispatch job must not lock the page
		// indefinitely).
		$this->jobQueue->expects( $this->any() )
			->method( 'hasPendingJob' )
			->willReturn( false );

		$this->jobQueue->expects( $this->any() )
			->method( 'getQueueSize' )
			->willReturn( 0 );

		$instance = $this->newProtectionValidator();

		$this->assertFalse(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testChangePropagationProtectionAppliesWhileJobPending() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_PROPERTY );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'a marker' ) ] );

		$this->jobQueue->expects( $this->any() )
			->method( 'hasPendingJob' )
			->willReturn( true );

		$instance = $this->newProtectionValidator();

		$this->assertTrue(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testNoChangePropagationProtectionWithTheSettingOff() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_CATEGORY );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'a marker' ) ] );

		$this->jobQueue->expects( $this->any() )
			->method( 'hasPendingJob' )
			->willReturn( true );

		$instance = $this->newProtectionValidator();
		$instance->setChangePropagationProtection( false );

		$this->assertFalse( $instance->hasChangePropagationProtection( $subject->getTitle() ) );
	}

	public function testNoChangePropagationProtectionOutsideThePropertyAndCategoryNamespaces() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'a marker' ) ] );

		$this->jobQueue->expects( $this->any() )
			->method( 'hasPendingJob' )
			->willReturn( true );

		$this->assertFalse( $this->newProtectionValidator()->hasChangePropagationProtection( $subject->getTitle() ) );
	}

	public function testChangePropagationProtectionMissedWhileReplicasLagIsFoundWithinAMinute() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_PROPERTY );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturnOnConsecutiveCalls( [], [ $this->dataItemFactory->newDIBlob( 'a marker' ) ] );

		$this->jobQueue->expects( $this->any() )
			->method( 'hasPendingJob' )
			->willReturn( true );

		$now = 1700000000.0;
		$this->entityCache = EntityCacheFactory::newEntityCache( new HashBagOStuff(), $now );
		$instance = $this->newProtectionValidator();
		$instance->hasChangePropagationProtection( $subject->getTitle() );

		$now += EntityCache::TTL_MINUTE + 1;

		$this->assertTrue( $instance->hasChangePropagationProtection( $subject->getTitle() ) );
	}

	/**
	 * @dataProvider editProtectionProvider
	 */
	public function testHasEditProtection( bool $userCanEdit, bool $isProtected, bool $expected ) {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo' );

		$this->permissionManager->expects( $this->any() )
			->method( 'userCan' )
			->willReturn( $userCanEdit );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( $isProtected ) ] );

		$this->expectEditRestriction( 'smw-pageedit' );

		$instance = $this->newProtectionValidator();
		$instance->setEditProtectionRight( 'smw-pageedit' );

		$this->assertSame( $expected, $instance->hasEditProtection( $subject->getTitle() ) );
	}

	public function editProtectionProvider(): iterable {
		yield 'protected, user may not edit' => [ false, true, true ];
		yield 'protected, user may edit' => [ true, true, false ];
		yield 'not protected, user may not edit' => [ false, false, false ];
	}

	public function testNoEditProtectionOnNamespaceWithoutAProtectionRight() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBoolean( true ) ] );

		$this->assertFalse( $this->newProtectionValidator()->hasEditProtectionOnNamespace( $subject->getTitle() ) );
	}

	public function testNoCreateProtectionWithoutAProtectionRight() {
		$this->permissionManager->expects( $this->any() )
			->method( 'userCan' )
			->willReturn( false );

		$this->assertFalse( $this->newProtectionValidator()->hasCreateProtection( $this->createMock( Title::class ) ) );
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

		$instance = $this->newProtectionValidator();
		$instance->setEditProtectionRight( 'smw-pageedit' );
		$this->expectEditRestriction( 'smw-pageedit' );

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

		$this->jobQueue->expects( $this->any() )
			->method( 'hasPendingJob' )
			->willReturn( true );

		$instance = $this->newProtectionValidator();

		// An edit-protection check runs first and caches its (`_EDIP`) result; it
		// must not make the unrelated change-propagation check report a lock.
		$instance->hasProtection( $subject->getTitle() );

		$this->assertFalse(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testSetGetCreateProtectionRight() {
		$instance = $this->newProtectionValidator();

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

		$instance = $this->newProtectionValidator();

		$instance->setCreateProtectionRight(
			'foo'
		);

		$this->assertTrue(
			$instance->hasCreateProtection( $title )
		);
	}

	public function testHasCreateProtection_NullTitle() {
		$instance = $this->newProtectionValidator();

		$this->assertFalse(
			$instance->hasCreateProtection( null )
		);
	}

	public function testHasEditProtection_NullTitle() {
		$instance = $this->newProtectionValidator();

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

		$instance = $this->newProtectionValidator();

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
			$pageCreator,
			$this->restrictionStore
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
			$pageCreator,
			$this->restrictionStore
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

		$instance = $this->newProtectionValidator();

		$instance->registerPropertyChangeListener( $propertyChangeListener );
	}

	public function testChangePropagationChangeRecordInvalidatesTheCachedProtection() {
		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_PROPERTY );

		$entityIdManager = $this->createMock( EntityIdManager::class );

		$entityIdManager->expects( $this->any() )
			->method( 'getDataItemById' )
			->with( 42 )
			->willReturn( $subject );

		$store = $this->createMock( SQLStore::class );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$store->expects( $this->exactly( 2 ) )
			->method( 'getPropertyValues' )
			->willReturnOnConsecutiveCalls( [], [ $this->dataItemFactory->newDIBlob( 'a marker' ) ] );

		$this->jobQueue->expects( $this->any() )
			->method( 'hasPendingJob' )
			->willReturn( true );

		$instance = new ProtectionValidator(
			$store,
			$this->entityCache,
			$this->permissionManager,
			$this->pageCreator,
			$this->restrictionStore
		);

		$instance->hasChangePropagationProtection( $subject->getTitle() );

		$instance->invalidateCache(
			$this->dataItemFactory->newDIProperty( '_CHGPRO' ),
			new ChangeRecord( [ new ChangeRecord( [ 'row' => [ 's_id' => 42 ] ] ) ] )
		);

		$this->assertTrue(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

}
