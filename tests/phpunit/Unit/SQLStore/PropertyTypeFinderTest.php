<?php

namespace SMW\Tests\Unit\SQLStore;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\PropertyTypeFinder;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\SQLStore\PropertyTypeFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class PropertyTypeFinderTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	private $connection;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyTypeFinder::class,
			new PropertyTypeFinder( $this->connection )
		);
	}

	public function testCountByType() {
		$row = new stdClass;
		$row->count = 42;

		$whereConditions = [];
		$selectBuilder = $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );

		$this->connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new PropertyTypeFinder(
			$this->connection
		);

		$this->assertEquals(
			42,
			$instance->countByType( '_txt' )
		);

		$this->assertSame(
			[ [ 'o_serialized' => 'http://semantic-mediawiki.org/swivt/1.0#_txt' ] ],
			$whereConditions
		);
	}

}
