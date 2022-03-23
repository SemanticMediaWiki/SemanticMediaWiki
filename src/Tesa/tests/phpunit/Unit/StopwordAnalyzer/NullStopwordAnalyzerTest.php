<?php

namespace Onoi\Tesa\Tests\StopwordAnalyzer;

use Onoi\Tesa\StopwordAnalyzer\NullStopwordAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Onoi\Tesa\StopwordAnalyzer\NullStopwordAnalyzer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class NullStopwordAnalyzerTest extends TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\StopwordAnalyzer\NullStopwordAnalyzer',
			new NullStopwordAnalyzer()
		);
	}

	public function testIsStopWord() {

		$instance = new NullStopwordAnalyzer();

		$this->assertFalse(
			$instance->isStopWord( 'Foo' )
		);
	}

}
