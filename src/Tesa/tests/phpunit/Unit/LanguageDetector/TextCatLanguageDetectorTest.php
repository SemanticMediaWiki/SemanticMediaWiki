<?php

namespace Onoi\Tesa\Tests\LanguageDetector;

use Onoi\Tesa\LanguageDetector\TextCatLanguageDetector;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Onoi\Tesa\LanguageDetector\TextCatLanguageDetector
 * @group onoi-tesa
 *
 * @license GPL-2.0-or-later
 * @since 0.1
 *
 * @author mwjames
 */
class TextCatLanguageDetectorTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\Onoi\Tesa\LanguageDetector\TextCatLanguageDetector',
			new TextCatLanguageDetector()
		);
	}

	public function testDetectOnMock() {
		$languageCandidates = [ 'en', 'de', 'fr', 'es', 'ja', 'zh' ];

		$textCat = $this->getMockBuilder( '\TextCat' )
			->disableOriginalConstructor()
			->getMock();

		$textCat->expects( $this->once() )
			->method( 'classify' )
			->with(
				'Foo',
				$languageCandidates )
			->willReturn( [] );

		$instance = new TextCatLanguageDetector( $textCat );
		$instance->setLanguageCandidates(
			$languageCandidates
		);

		$instance->detect( 'Foo' );
	}

}
