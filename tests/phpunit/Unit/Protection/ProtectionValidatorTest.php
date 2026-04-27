<?php

namespace SMW\Tests\Unit\Protection;

use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
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

		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ProtectionValidator::class,
			new ProtectionValidator( $this->store, $this->entityCache, $this->permissionManager )
		);
	}

	public function testSetGetEditProtectionRight() {
		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager
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
			$this->permissionManager
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
			$this->permissionManager
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
			$this->permissionManager
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
			$this->permissionManager
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
			$this->permissionManager
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

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager
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
			$this->permissionManager
		);

		$instance->setChangePropagationProtection(
			false
		);

		$this->assertFalse(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testSetGetCreateProtectionRight() {
		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager
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
			$this->permissionManager
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
			$this->permissionManager
		);

		$this->assertFalse(
			$instance->hasCreateProtection( null )
		);
	}

	public function testHasEditProtection_NullTitle() {
		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager
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
			$this->permissionManager
		);

		$this->assertFalse(
			$instance->isClassifiedAsImportPerformerProtected( $title, $user )
		);
	}

	public function testIsClassifiedAsImportPerformerProtected_CreatorAndCurrentUserDontMatch() {
		$this->markTestSkipped( "FIXME later" );
		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getUserText' )
			->willReturn( 'FooImporter' );

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( SMW_NS_SCHEMA );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'FooSchema' );

		$title->expects( $this->any() )
			->method( 'getFirstRevision' )
			->willReturn( $revision );

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager
		);

		$instance->setImportPerformers(
			[ 'FooImporter' ]
		);

		$this->assertTrue(
			$instance->isClassifiedAsImportPerformerProtected( $title, $user )
		);
	}

	public function testIsClassifiedAsNotImportPerformerProtected_CreatorAndCurrentUserDoMatch() {
		$revisionRecord = $this->getMockBuilder( RevisionRecord::class )
			->disableOriginalConstructor()
			->getMock();

		$revisionRecord->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( User::newFromName( 'FooImporter', false ) );

		$revisionStore = $this->getMockBuilder( RevisionStore::class )
			->disableOriginalConstructor()
			->getMock();

		$revisionStore->expects( $this->any() )
			->method( 'getFirstRevision' )
			->willReturn( $revisionRecord );

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
			->willReturn( $revisionStore );

		$pageCreator = $this->getMockBuilder( PageCreator::class )
			->disableOriginalConstructor()
			->getMock();

		$pageCreator->expects( $this->atLeastOnce() )
			->method( 'createPage' )
			->willReturn( $wikiPage );

		$this->testEnvironment->registerObject( 'PageCreator', $pageCreator );

		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'FooImporter' );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionManager
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
			$this->permissionManager
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
			->with( $this->stringContains( 'smw:entity:d5c5aca7d29a32ea16a0331dac164ac4' ) );

		$changeRecord = new ChangeRecord(
			[
				new ChangeRecord( [ 'row' => [ 's_id' => 42 ] ] )
			]
		);

		$instance = new ProtectionValidator(
			$store,
			$this->entityCache,
			$this->permissionManager
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
			->with( $this->stringContains( 'smw:entity:d5c5aca7d29a32ea16a0331dac164ac4' ) );

		$changeRecord = new ChangeRecord(
			[
				new ChangeRecord( [ 'row' => [ 's_id' => 42 ], 'is_insert' => true ] )
			]
		);

		$instance = new ProtectionValidator(
			$store,
			$this->entityCache,
			$this->permissionManager
		);

		$property = $this->dataItemFactory->newDIProperty( '_CHGPRO' );

		$instance->invalidateCache( $property, $changeRecord );
	}

}
