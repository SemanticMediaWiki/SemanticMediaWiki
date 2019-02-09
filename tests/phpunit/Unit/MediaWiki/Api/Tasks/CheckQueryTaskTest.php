<?php

namespace SMW\Tests\MediaWiki\Api\Tasks;

use SMW\MediaWiki\Api\Tasks\CheckQueryTask;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Api\Tasks\CheckQueryTask
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CheckQueryTaskTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$instance = new CheckQueryTask( $this->store );

		$this->assertInstanceOf(
			CheckQueryTask::class,
			$instance
		);
	}

	public function testProcess() {

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->will( $this->returnValue( $queryResult ) );

		$instance = new CheckQueryTask(
			$this->store
		);

		$query = [
			'query_hash_1#result_hash_2' => [
				'parameters' => [
					'limit' => 5,
					'offset' => 0,
					'querymode' => 1
				],
				'conditions' => ''
			]
		];

		$instance->process( [ 'subject' => 'Foo#0##', 'query' => $query ] );
	}

}
