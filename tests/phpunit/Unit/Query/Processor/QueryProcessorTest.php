<?php

namespace SMW\Tests\Query\Processor;

use SMWQueryProcessor as QueryProcessor;

/**
 * @covers SMWQueryProcessor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class QueryProcessorTest extends \PHPUnit_Framework_TestCase {

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
