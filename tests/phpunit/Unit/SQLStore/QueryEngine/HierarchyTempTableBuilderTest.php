<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class HierarchyTempTableBuilderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $connection;
	private $temporaryTableBuilder;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->temporaryTableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TemporaryTableBuilder' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			HierarchyTempTableBuilder::class,
			new HierarchyTempTableBuilder( $this->connection, $this->temporaryTableBuilder )
		);
	}

	public function testGetHierarchyTableDefinitionForType() {

		$this->connection->expects( $this->once() )
			->method( 'tableName' )
			->with(
				$this->stringContains( 'bar') )
			->will( $this->returnValue( '_bar' ) );

		$instance = new HierarchyTempTableBuilder(
			$this->connection,
			$this->temporaryTableBuilder
		);

		$instance->setTableDefinitions( [ 'property' => [ 'table' => 'bar', 'depth' => 3 ] ] );

		$this->assertEquals(
			[ '_bar', 3 ],
			$instance->getTableDefinitionByType( 'property' )
		);
	}

	public function testTryToGetHierarchyTableDefinitionForUnregisteredTypeThrowsException() {

		$instance = new HierarchyTempTableBuilder(
			$this->connection,
			$this->temporaryTableBuilder
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->getTableDefinitionByType( 'foo' );
	}

	public function testFillTempTable() {

		$this->connection->expects( $this->once() )
			->method( 'tableName' )
			->with(
				$this->stringContains( 'bar') )
			->will( $this->returnValue( '_bar' ) );

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'query' );

		$instance = new HierarchyTempTableBuilder(
			$this->connection,
			$this->temporaryTableBuilder
		);

		$instance->setTableDefinitions( [ 'class' => [ 'table' => 'bar', 'depth' => 3 ] ] );

		$instance->fillTempTable( 'class', 'foobar', '(42)' );

		$expected = [
			'(42)' => 'foobar'
		];

		$this->assertEquals(
			$expected,
			$instance->getHierarchyCache()
		);

		$instance->emptyHierarchyCache();

		$this->assertEmpty(
			$instance->getHierarchyCache()
		);
	}

}
