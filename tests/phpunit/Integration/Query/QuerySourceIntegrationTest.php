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
class QuerySourceIntegrationTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $store;

	protected function setUp(): void {
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

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testQueryProcessorWithDefaultSource() {
		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->willReturn( [] );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->willReturn( $queryResult );

		$rawParams = [
			'[[Modification date::+]]',
			'?Modification date',
			'format=list',
			'source=default'
		];

		$this->assertIsString(

			$this->makeQueryResultFromRawParameters( $rawParams )
		);
	}

	public function testQueryProcessorWithValidSource() {
		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->willReturn( [] );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->willReturn( $queryResult );

		$rawParams = [
			'[[Modification date::+]]',
			'?Modification date',
			'format=list',
			'source=foo'
		];

		$this->assertIsString(

			$this->makeQueryResultFromRawParameters( $rawParams )
		);
	}

	public function testQueryProcessorWithInvalidSourceSwitchesToDefault() {
		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->willReturn( [] );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->willReturn( $queryResult );

		$rawParams = [
			'[[Modification date::+]]',
			'?Modification date',
			'format=list',
			'source=bar'
		];

		$this->assertIsString(

			$this->makeQueryResultFromRawParameters( $rawParams )
		);
	}

	public function testQuerySourceOnCount() {
		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->atLeastOnce() )
			->method( 'getCountValue' )
			->willReturn( 42 );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getQueryResult' )
			->willReturn( $queryResult );

		$rawParams = [
			'[[Modification date::+]]',
			'?Modification date',
			'format=count',
			'source=foo'
		];

		$this->assertIsString(

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

		$this->expectException( 'RuntimeException' );
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
