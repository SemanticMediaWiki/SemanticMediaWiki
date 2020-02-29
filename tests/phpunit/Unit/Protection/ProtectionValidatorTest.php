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
	private $permissionExaminer;

	protected function setUp() : void {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( '\SMW\store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->entityCache = $this->getMockBuilder( '\SMW\EntityCache' )
			->disableOriginalConstructor()
			->setMethods( [ 'save', 'contains', 'fetch', 'associate', 'invalidate' ] )
			->getMock();

		$this->permissionExaminer = $this->getMockBuilder( '\SMW\MediaWiki\PermissionExaminer' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ProtectionValidator::class,
			new ProtectionValidator( $this->store, $this->entityCache, $this->permissionExaminer )
		);
	}

	public function testSetGetEditProtectionRight() {

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionExaminer
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
			$this->permissionExaminer
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
			$this->permissionExaminer
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
			$this->permissionExaminer
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
			$this->permissionExaminer
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
			$this->permissionExaminer
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
			$this->permissionExaminer
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
			$this->permissionExaminer
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
			$this->permissionExaminer
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

		$this->permissionExaminer->expects( $this->once() )
			->method( 'userCan' )
			->with(
				$this->equalTo( 'edit' ),
				$this->equalTo( null ),
				$this->equalTo( $title ) )
			->will( $this->returnValue( false ) );

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionExaminer
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
			$this->permissionExaminer
		);

		$this->assertFalse(
			$instance->hasCreateProtection( null )
		);
	}

	public function testHasEditProtection_NullTitle() {

		$instance = new ProtectionValidator(
			$this->store,
			$this->entityCache,
			$this->permissionExaminer
		);

		$this->assertFalse(
			$instance->hasEditProtection( null )
		);
	}

}
