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
	private $cachedPropertyValuesPrefetcher;
	private $cache;

	protected function setUp() {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();

		$this->cachedPropertyValuesPrefetcher = $this->getMockBuilder( '\SMW\CachedPropertyValuesPrefetcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ProtectionValidator::class,
			new ProtectionValidator( $this->cachedPropertyValuesPrefetcher, $this->cache )
		);
	}

	public function testSetGetEditProtectionRight() {

		$instance = new ProtectionValidator(
			$this->cachedPropertyValuesPrefetcher,
			$this->cache
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

		$this->cache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( false ) );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $subject->asBase() ),
				$this->equalTo( $property ) )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBoolean( true ) ] ) );

		$instance = new ProtectionValidator(
			$this->cachedPropertyValuesPrefetcher,
			$this->cache
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

		$this->cache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( false ) );

		$this->cachedPropertyValuesPrefetcher->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $subject->asBase() ),
				$this->equalTo( $property ) )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBoolean( true ) ] ) );

		$instance = new ProtectionValidator(
			$this->cachedPropertyValuesPrefetcher,
			$this->cache
		);

		$this->assertTrue(
			$instance->hasProtection( $subject->getTitle() )
		);
	}

	public function testHasProtectionFromCache() {

		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_MAIN, '', 'Bar' );

		$this->cache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( true ) );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new ProtectionValidator(
			$this->cachedPropertyValuesPrefetcher,
			$this->cache
		);

		$this->assertFalse(
			$instance->hasProtection( $subject->getTitle() )
		);
	}

	public function testHasChangePropagationProtectionOnCategory_FromCache() {

		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_CATEGORY );

		$this->cache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( true ) );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$instance = new ProtectionValidator(
			$this->cachedPropertyValuesPrefetcher,
			$this->cache
		);

		$this->assertFalse(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testNoChangePropagationProtectionOnCategory_FromCache() {

		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_CATEGORY );

		$this->cache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( true ) );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( true ) );

		$instance = new ProtectionValidator(
			$this->cachedPropertyValuesPrefetcher,
			$this->cache
		);

		$this->assertTrue(
			$instance->hasChangePropagationProtection( $subject->getTitle() )
		);
	}

	public function testNoChangePropagationProtectionOnCategory_WithFalseSetting() {

		$subject = $this->dataItemFactory->newDIWikiPage( 'Foo', NS_CATEGORY );

		$this->cache->expects( $this->never() )
			->method( 'contains' );

		$instance = new ProtectionValidator(
			$this->cachedPropertyValuesPrefetcher,
			$this->cache
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
			$this->cachedPropertyValuesPrefetcher,
			$this->cache
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

		$title->expects( $this->once() )
			->method( 'userCan' )
			->will( $this->returnValue( false ) );

		$instance = new ProtectionValidator(
			$this->cachedPropertyValuesPrefetcher,
			$this->cache
		);

		$instance->setCreateProtectionRight(
			'foo'
		);

		$this->assertTrue(
			$instance->hasCreateProtection( $title )
		);
	}

}
