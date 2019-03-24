<?php

namespace SMW\Tests\SQLStore\QueryDependency;

use SMW\DataItemFactory;
use SMW\RequestOptions;
use SMW\SQLStore\QueryDependency\DependencyLinksValidator;

/**
 * @covers \SMW\SQLStore\QueryDependency\DependencyLinksValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DependencyLinksValidatorTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DependencyLinksValidator::class,
			new DependencyLinksValidator( $this->store )
		);
	}

	public function testHasArchaicDependencies_Disabled() {

		$subject = $this->dataItemFactory->newDIWikiPage( 'Bar', NS_MAIN );

		$instance = new DependencyLinksValidator(
			$this->store
		);

		$instance->setCheckDependencies( false );

		$this->assertFalse(
			$instance->canCheckDependencies()
		);

		$this->assertFalse(
			$instance->hasArchaicDependencies( $subject )
		);
	}

	public function testHasArchaicDependencies() {

		$subject = $this->dataItemFactory->newDIWikiPage( 'Bar', NS_MAIN, '', '' );

		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)[ 'smw_id' => 42 ] ) );

		$connection->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( [ (object)[ 'smw_id' => 42, 'smw_subobject' => '_foo', 'smw_touched' => '99999' ] ] ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( [ '_foo' => $propertyTableDefinition ] ) );

		$propertyTableInfoFetcher = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableInfoFetcher' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher->expects( $this->any() )
			->method( 'findTableIdForProperty' )
			->will( $this->returnValue( '_foo' ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->will( $this->returnValue( $propertyTableInfoFetcher ) );

		$instance = new DependencyLinksValidator(
			$this->store
		);

		$instance->setCheckDependencies( true );

		$this->assertTrue(
			$instance->canCheckDependencies()
		);

		$this->assertTrue(
			$instance->hasArchaicDependencies( $subject )
		);

		$this->assertEquals(
			[ '_foo' ],
			$instance->getCheckedDependencies()
		);
	}

}
