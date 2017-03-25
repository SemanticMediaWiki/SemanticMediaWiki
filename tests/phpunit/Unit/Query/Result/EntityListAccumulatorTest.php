<?php

namespace SMW\Tests\Query\Result;

use SMW\DIWikiPage;
use SMW\Query\Result\EntityListAccumulator;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Query\Result\EntityListAccumulator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class EntityListAccumulatorTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $query;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			EntityListAccumulator::class,
			new EntityListAccumulator( $this->query )
		);
	}

	public function testAddToEntityList() {

		$dataItem = DIWikiPage::newFromText( 'Foo' );

		$this->query->expects( $this->any() )
			->method( 'getQueryId' )
			->will( $this->returnValue( 'FOO:123' ) );

		$instance = new EntityListAccumulator(
			$this->query
		);

		$instance->pruneEntityList();
		$instance->addToEntityList( $dataItem );

		$this->assertEquals(
			[ 'Foo#0#' => $dataItem ],
			$instance->getEntityList( 'FOO:123' )
		);

		$instance->pruneEntityList();

		$this->assertEmpty(
			$instance->getEntityList()
		);
	}

	/**
	 * @depends testAddToEntityList
	 */
	public function testAddAnotherToEntityList() {

		$dataItem = DIWikiPage::newFromText( 'Bar' );

		$this->query->expects( $this->any() )
			->method( 'getQueryId' )
			->will( $this->returnValue( 'FOO:BAR' ) );

		$instance = new EntityListAccumulator(
			$this->query
		);

		$instance->addToEntityList( $dataItem );

		$this->assertEquals(
			[
				'FOO:BAR' => [ 'Bar#0#' => $dataItem ]
			],
			$instance->getEntityList()
		);
	}

}
