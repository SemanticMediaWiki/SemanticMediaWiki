<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder;

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
			'\SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder',
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

		$instance->setPropertyHierarchyTableDefinition( 'bar', 3 );

		$this->assertEquals(
			array( '_bar', 3 ),
			$instance->getHierarchyTableDefinitionForType( 'property' )
		);
	}

	public function testTryToGetHierarchyTableDefinitionForUnregisteredTypeThrowsException() {

		$instance = new HierarchyTempTableBuilder(
			$this->connection,
			$this->temporaryTableBuilder
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->getHierarchyTableDefinitionForType( 'foo' );
	}

	public function testCreateHierarchyTempTable() {

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

		$instance->setClassHierarchyTableDefinition( 'bar', 3 );
		$instance->createHierarchyTempTableFor( 'class', 'foobar', '(42)' );

		$expected = array(
			'(42)' => 'foobar'
		);

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
