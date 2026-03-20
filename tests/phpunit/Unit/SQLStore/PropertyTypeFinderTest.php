<?php

namespace SMW\Tests\Unit\SQLStore;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\PropertyTypeFinder;
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

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				'smw_fpt_type',
				$this->anything(),
				[ 'o_serialized' => 'http://semantic-mediawiki.org/swivt/1.0#_txt' ] )
			->willReturn( $row );

		$instance = new PropertyTypeFinder(
			$this->connection
		);

		$this->assertEquals(
			42,
			$instance->countByType( '_txt' )
		);
	}

}
