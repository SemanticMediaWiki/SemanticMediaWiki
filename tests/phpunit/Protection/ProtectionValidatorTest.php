<?php

namespace SMW\Tests\Protection;

use SMW\DataItemFactory;
use SMW\Protection\ProtectionValidator;

/**
 * @covers \SMW\Protection\ProtectionValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since  2.5
 *
 * @author mwjames
 */
class ProtectionValidatorTest extends \PHPUnit\Framework\TestCase {

	private $dataItemFactory;
	private $store;
	private $entityCache;
	private $permissionManager;

	protected function setUp(): void {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'save', 'contains', 'fetch', 'associate', 'invalidate', 'delete' ] )
			->getMock();

		$this->permissionManager = $this->getMockBuilder( '\SMW\MediaWiki\PermissionManager' )
			->disableOriginalConstructor()
			->getMock();
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
		$title = $this->getMockBuilder( '\Title' )
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
		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$user = $this->getMockBuilder( '\User' )
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

		$title = $this->getMockBuilder( '\Title' )
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

		$user = $this->getMockBuilder( '\User' )
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
		$this->markTestSkipped( "FIXME later" );
		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getUserText' )
			->willReturn( 'FooImporter' );

		$title = $this->getMockBuilder( '\Title' )
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

		$user = $this->getMockBuilder( '\User' )
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
		$propertyChangeListener = $this->getMockBuilder( '\SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener' )
			->disableOriginalConstructor()
			->getMock();

		$propertyChangeListener->expects( $this->at( 0 ) )
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
		$entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getDataItemById' )
			->willReturn( $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$this->entityCache->expects( $this->once() )
			->method( 'delete' )
			->with( $this->stringContains( 'smw:entity:d5c5aca7d29a32ea16a0331dac164ac4' ) );

		$changeRecord = new \SMW\Listener\ChangeListener\ChangeRecord(
			[
				new \SMW\Listener\ChangeListener\ChangeRecord( [ 'row' => [ 's_id' => 42 ] ] )
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
		$entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->any() )
			->method( 'getDataItemById' )
			->willReturn( $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$this->entityCache->expects( $this->once() )
			->method( 'save' )
			->with( $this->stringContains( 'smw:entity:d5c5aca7d29a32ea16a0331dac164ac4' ) );

		$changeRecord = new \SMW\Listener\ChangeListener\ChangeRecord(
			[
				new \SMW\Listener\ChangeListener\ChangeRecord( [ 'row' => [ 's_id' => 42 ], 'is_insert' => true ] )
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
