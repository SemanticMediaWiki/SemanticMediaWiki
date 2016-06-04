<?php

namespace SMW\Tests\Query;

use SMW\DIWikiPage;
use SMW\Query\TemporaryEntityListAccumulator;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Query\TemporaryEntityListAccumulator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class TemporaryEntityListAccumulatorTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\Query\TemporaryEntityListAccumulator',
			new TemporaryEntityListAccumulator( $this->query )
		);
	}

	public function testAddToEntityList() {

		$dataItem = DIWikiPage::newFromText( 'Foo' );

		$this->query->expects( $this->any() )
			->method( 'getQueryId' )
			->will( $this->returnValue( 'FOO:123' ) );

		$instance = new TemporaryEntityListAccumulator(
			$this->query
		);

		$instance->pruneEntityList();
		$instance->addToEntityList( null, $dataItem );

		$this->assertEquals(
			array( $dataItem ),
			$instance->getEntityList( 'FOO:123' )
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

		$instance = new TemporaryEntityListAccumulator(
			$this->query
		);

		$instance->addToEntityList( null, $dataItem );

		$this->assertEquals(
			array(
				'FOO:BAR' => array( $dataItem ),
				'FOO:123' => array( DIWikiPage::newFromText( 'Foo' ) ) // from previous run
			),
			$instance->getEntityList()
		);
	}

	/**
	 * @depends testAddAnotherToEntityList
	 */
	public function testGetAndPruneEntityList() {

		$instance = new TemporaryEntityListAccumulator(
			$this->query
		);

		$this->assertCount(
			2,
			$instance->getEntityList()
		);

		$this->assertNotEmpty(
			$instance->getEntityList( 'FOO:123' )
		);

		$instance->pruneEntityList( 'FOO:123' );

		$this->assertEmpty(
			$instance->getEntityList( 'FOO:123' )
		);

		$this->assertNotEmpty(
			$instance->getEntityList( 'FOO:BAR' )
		);

		$instance->pruneEntityList();

		$this->assertEmpty(
			$instance->getEntityList()
		);
	}

}
