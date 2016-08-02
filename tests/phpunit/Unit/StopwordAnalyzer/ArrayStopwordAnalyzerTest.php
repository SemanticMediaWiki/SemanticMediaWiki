<?php

namespace Onoi\Tesa\Tests\StopwordAnalyzer;

use Onoi\Tesa\StopwordAnalyzer\ArrayStopwordAnalyzer;

/**
 * @covers \Onoi\Tesa\StopwordAnalyzer\ArrayStopwordAnalyzer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class ArrayStopwordAnalyzerTest extends \PHPUnit_Framework_TestCase {

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

		$defaultList = array( 'Foo', 'かつて', 'bAR' );

		$provider[] = array(
			$defaultList,
			'Foo',
			true
		);

		$provider[] = array(
			$defaultList,
			'かつて',
			true
		);

		$provider[] = array(
			$defaultList,
			'bar',
			false
		);

		return $provider;
	}

}
