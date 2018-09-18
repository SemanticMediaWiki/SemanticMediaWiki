<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;
use SMWQueryProcessor;

/**
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since   1.9.2
 *
 * @author mwjames
 */
class QuerySourceIntegrationTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $store;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->testEnvironment->addConfiguration(
			'smwgQuerySources',
			[
				'foo'    => 'SMW\Tests\Utils\Mock\FakeQueryStore',
				'foobar' => 'SMW\Tests\Integration\Query\AnotherFakeQueryStoreWhichDoesNotImplentTheQueryEngineInterface',
				'bar'    => 'SMW\Tests\NonExistentQueryStore',
			]
		);

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testQueryProcessorWithDefaultSource() {

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->will( $this->returnValue( $queryResult ) );

		$rawParams = [
			'[[Modification date::+]]',
			'?Modification date',
			'format=list',
			'source=default'
		];

		$this->assertInternalType(
			'string',
			$this->makeQueryResultFromRawParameters( $rawParams )
		);
	}

	public function testQueryProcessorWithValidSource() {

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->will( $this->returnValue( $queryResult ) );

		$rawParams = [
			'[[Modification date::+]]',
			'?Modification date',
			'format=list',
			'source=foo'
		];

		$this->assertInternalType(
			'string',
			$this->makeQueryResultFromRawParameters( $rawParams )
		);
	}

	public function testQueryProcessorWithInvalidSourceSwitchesToDefault() {

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( [] ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->will( $this->returnValue( $queryResult ) );

		$rawParams = [
			'[[Modification date::+]]',
			'?Modification date',
			'format=list',
			'source=bar'
		];

		$this->assertInternalType(
			'string',
			$this->makeQueryResultFromRawParameters( $rawParams )
		);
	}

	public function testQuerySourceOnCount() {

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getCountValue' )
			->will( $this->returnValue( 42 ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->will( $this->returnValue( $queryResult ) );

		$rawParams = [
			'[[Modification date::+]]',
			'?Modification date',
			'format=count',
			'source=foo'
		];

		$this->assertInternalType(
			'string',
			$this->makeQueryResultFromRawParameters( $rawParams )
		);
	}

	public function testQueryProcessorWithInvalidSource() {

		$rawParams = [
			'[[Modification date::+]]',
			'?Modification date',
			'format=list',
			'source=foobar'
		];

		$this->setExpectedException( 'RuntimeException' );
		$this->makeQueryResultFromRawParameters( $rawParams );
	}

	protected function makeQueryResultFromRawParameters( $rawParams ) {

		list( $query, $params ) = SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY,
			false
		);

		return SMWQueryProcessor::getResultFromQuery(
			$query,
			$params,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY
		);
	}

}

class AnotherFakeQueryStoreWhichDoesNotImplentTheQueryEngineInterface {
}
