<?php

namespace Onoi\Tesa\Tests\LanguageDetector;

use Onoi\Tesa\LanguageDetector\NullLanguageDetector;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Onoi\Tesa\LanguageDetector\NullLanguageDetector
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class NullLanguageDetectorTest extends TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\LanguageDetector\NullLanguageDetector',
			new NullLanguageDetector()
		);
	}

	public function testIsStopWord() {

		$instance = new NullLanguageDetector();

		$this->assertNull(
			$instance->detect( 'Foo' )
		);
	}

}
