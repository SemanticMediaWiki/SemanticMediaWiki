<?php

namespace Onoi\Tesa\Tests\StopwordAnalyzer;

use Onoi\Tesa\StopwordAnalyzer\ArrayStopwordAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Onoi\Tesa\StopwordAnalyzer\ArrayStopwordAnalyzer
 * @group onoi-tesa
 *
 * @license GPL-2.0-or-later
 * @since 0.1
 *
 * @author mwjames
 */
class ArrayStopwordAnalyzerTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\Onoi\Tesa\StopwordAnalyzer\ArrayStopwordAnalyzer',
			new ArrayStopwordAnalyzer()
		);
	}

	/**
	 * @dataProvider stopWordsProvider
	 */
	public function testIsStopWord( $defaultList, $word, $expected ) {
		$instance = new ArrayStopwordAnalyzer( $defaultList );

		$this->assertEquals(
			$expected,
			$instance->isStopWord( $word )
		);
	}

	public function stopWordsProvider() {
		$defaultList = [ 'Foo', 'かつて', 'bAR' ];

		$provider[] = [
			$defaultList,
			'Foo',
			true
		];

		$provider[] = [
			$defaultList,
			'かつて',
			true
		];

		$provider[] = [
			$defaultList,
			'bar',
			false
		];

		return $provider;
	}

}
