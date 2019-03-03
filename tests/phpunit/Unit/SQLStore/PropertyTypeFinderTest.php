<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\PropertyTypeFinder;

/**
 * @covers \SMW\SQLStore\PropertyTypeFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PropertyTypeFinderTest extends \PHPUnit_Framework_TestCase {

	private $connection;

	protected function setUp() {
		parent::setUp();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
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

		$row = new \stdClass;
		$row->count = 42;

		$this->connection->expects( $this->once() )
			->method( 'selectRow' )
			->with(
				$this->equalTo( 'smw_fpt_type' ),
				$this->anything(),
				$this->equalTo( [ 'o_serialized' => 'http://semantic-mediawiki.org/swivt/1.0#_txt' ] ) )
			->will( $this->returnValue( $row ) );

		$instance = new PropertyTypeFinder(
			$this->connection
		);

		$this->assertEquals(
			42,
			$instance->countByType( '_txt' )
		);
	}

}
