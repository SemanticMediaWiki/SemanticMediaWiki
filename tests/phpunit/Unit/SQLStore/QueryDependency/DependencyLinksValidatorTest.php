<?php

namespace SMW\Tests\Unit\SQLStore\QueryDependency;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\QueryDependency\DependencyLinksValidator;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\QueryDependency\DependencyLinksValidator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class DependencyLinksValidatorTest extends TestCase {

	private $store;
	private $dataItemFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
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

		$propertyTableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->willReturn( (object)[ 'smw_id' => 42 ] );

		$connection->expects( $this->once() )
			->method( 'select' )
			->willReturn( [ (object)[ 'smw_id' => 42, 'smw_subobject' => '_foo', 'smw_touched' => '99999' ] ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ '_foo' => $propertyTableDefinition ] );

		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableInfoFetcher->expects( $this->any() )
			->method( 'findTableIdForProperty' )
			->willReturn( '_foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

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
