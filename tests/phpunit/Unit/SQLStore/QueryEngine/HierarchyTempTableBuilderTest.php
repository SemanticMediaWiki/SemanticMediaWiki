<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder;
use SMWQuery as Query;

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
	private $temporaryIdTableCreator;

	protected function setUp() {

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->temporaryIdTableCreator = $this->getMockBuilder( '\SMW\SQLStore\TemporaryIdTableCreator' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\HierarchyTempTableBuilder',
			new HierarchyTempTableBuilder( $this->connection, $this->temporaryIdTableCreator )
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
			$this->temporaryIdTableCreator
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
			$this->temporaryIdTableCreator
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
			$this->temporaryIdTableCreator
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
