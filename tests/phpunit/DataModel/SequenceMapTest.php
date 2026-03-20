<?php

namespace SMW\Tests\DataModel;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataModel\SequenceMap;
use SMW\Schema\SchemaFactory;
use SMW\Schema\SchemaFinder;
use SMW\Schema\SchemaList;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataModel\SequenceMap
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SequenceMapTest extends TestCase {

	private $testEnvironment;
	private $schemaFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->schemaFactory = $this->getMockBuilder( SchemaFactory::class )
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
		$schemaList = $this->getMockBuilder( SchemaList::class )
			->disableOriginalConstructor()
			->getMock();

		$schemaList->expects( $this->atLeastOnce() )
			->method( 'get' )
			->with( 'profile' )
			->willReturn( [ 'sequence_map' => true ] );

		$schemaFinder = $this->getMockBuilder( SchemaFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$schemaFinder->expects( $this->atLeastOnce() )
			->method( 'newSchemaList' )
			->willReturn( $schemaList );

		$this->schemaFactory->expects( $this->atLeastOnce() )
			->method( 'newSchemaFinder' )
			->willReturn( $schemaFinder );

		$property = $this->getMockBuilder( Property::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertTrue(
			SequenceMap::canMap( $property )
		);

		$this->assertTrue(
			( new SequenceMap() )->hasSequenceMap( $property )
		);
	}

}
