<?php

namespace SMW\Tests\DataModel;

use SMW\DataModel\SequenceMap;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataModel\SequenceMap
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SequenceMapTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $schemaFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->schemaFactory = $this->getMockBuilder( '\SMW\Schema\SchemaFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'SchemaFactory', $this->schemaFactory );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SequenceMap::class,
			new SequenceMap()
		);
	}

	public function testCanMap() {

		$schemaList = $this->getMockBuilder( '\SMW\Schema\SchemaList' )
			->disableOriginalConstructor()
			->getMock();

		$schemaList->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with( $this->equalTo( 'profile' ) )
			->will( $this->returnValue( [ 'sequence_map' => true ] ) );

		$schemaFinder = $this->getMockBuilder( '\SMW\Schema\SchemaFinder' )
			->disableOriginalConstructor()
			->getMock();

		$schemaFinder->expects( $this->atLeastOnce() )
			->method( 'newSchemaList' )
			->will( $this->returnValue( $schemaList ) );

		$this->schemaFactory->expects( $this->atLeastOnce() )
			->method( 'newSchemaFinder' )
			->will( $this->returnValue( $schemaFinder ) );

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertTrue(
			SequenceMap::canMap( $property )
		);
	}

}
