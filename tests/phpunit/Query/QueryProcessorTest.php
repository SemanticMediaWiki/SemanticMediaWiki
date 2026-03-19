<?php

namespace SMW\Tests\Query;

use PHPUnit\Framework\TestCase;
use SMW\Query\QueryProcessor;

/**
 * @covers \SMW\Query\QueryProcessor
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class QueryProcessorTest extends TestCase {

	/**
	 * @dataProvider limitOffsetParamsProvider
	 */
	public function testGetProcessedParams_YieldCorrectProcessedParamValue( $params, $key, $expected ) {
		$processedParam = QueryProcessor::getProcessedParams(
			$params
		);

		$this->assertEquals(
			$expected,
			$processedParam[$key]->getValue()
		);
	}

	public function limitOffsetParamsProvider() {
		yield 'limit-string' => [
			[ 'limit' => '12' ],
			'limit',
			12
		];

		yield 'limit-integer' => [
			[ 'limit' => 12 ],
			'limit',
			12
		];

		yield 'offset-string' => [
			[ 'offset' => '42' ],
			'offset',
			42
		];

		yield 'offset-integer' => [
			[ 'offset' => 42 ],
			'offset',
			42
		];
	}

}
