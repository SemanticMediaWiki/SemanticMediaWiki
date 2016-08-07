<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder;
use SMWDataItem as DataItem;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableRebuilderTest extends \PHPUnit_Framework_TestCase {

	private $searchTableUpdater;
	private $connection;

	protected function setUp() {

		$this->searchTableUpdater = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder',
			new SearchTableRebuilder( $this->searchTableUpdater, $this->connection )
		);
	}

	public function testRunWithoutUpdate() {

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->atLeastOnce() )
			->method( 'getDiType' )
			->will( $this->returnValue( DataItem::TYPE_BLOB ) );

		$this->searchTableUpdater->expects( $this->once() )
			->method( 'isEnabled' )
			->will( $this->returnValue( true ) );

		$this->searchTableUpdater->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( $tableDefinition ) ) );

		$instance = new SearchTableRebuilder(
			$this->searchTableUpdater,
			$this->connection
		);

		$instance->run();
	}

}
