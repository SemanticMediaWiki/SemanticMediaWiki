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
class ProtectionValidatorTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;
	private $store;
	private $entityCache;
	private $permissionManager;

	protected function setUp() : void {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->setMethods( [ 'save', 'contains', 'fetch', 'associate', 'invalidate', 'delete' ] )
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
			->will( $this->returnValue( false ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $subject->asBase() ),
				$this->equalTo( $property ) )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBoolean( true ) ] ) );

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
			->will( $this->returnValue( false ) );

		$this->store->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $subject->asBase() ),
				$this->equalTo( $property ) )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBoolean( true ) ] ) );

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
			->will( $this->returnValue( true ) );

		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

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
			->will( $this->returnValue( true ) );

		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

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
			->will( $this->returnValue( true ) );

		$this->entityCache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( 'yes' ) );

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
				$this->equalTo( 'edit' ),
				$this->equalTo( null ),
				$this->equalTo( $title ) )
			->will( $this->returnValue( false ) );

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

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getUserText' )
			->will( $this->returnValue( 'FooImporter' ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_SCHEMA ) );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'FooSchema' ) );

		$title->expects( $this->any() )
			->method( 'getFirstRevision' )
			->will( $this->returnValue( $revision ) );

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

		$revision = $this->getMockBuilder( '\Revision' )
			->disableOriginalConstructor()
			->getMock();

		$revision->expects( $this->any() )
			->method( 'getUserText' )
			->will( $this->returnValue( 'FooImporter' ) );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_SCHEMA ) );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'FooSchema' ) );

		$title->expects( $this->any() )
			->method( 'getFirstRevision' )
			->will( $this->returnValue( $revision ) );

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( 'FooImporter' ) );

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
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_CHGPRO' ) ),
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
			->will( $this->returnValue( $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityIdManager ) );

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
			->will( $this->returnValue( $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN ) ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $entityIdManager ) );

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
