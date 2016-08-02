<?php

namespace Onoi\Tesa\Tests\LanguageDetector;

use Onoi\Tesa\LanguageDetector\TextCatLanguageDetector;

/**
 * @covers \Onoi\Tesa\LanguageDetector\TextCatLanguageDetector
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class TextCatLanguageDetectorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\LanguageDetector\TextCatLanguageDetector',
			new TextCatLanguageDetector()
		);
	}

	public function testDetectOnMock() {

		$languageCandidates = array( 'en', 'de', 'fr', 'es', 'ja', 'zh' );

		$textCat = $this->getMockBuilder( '\TextCat' )
			->disableOriginalConstructor()
			->getMock();

		$textCat->expects( $this->once() )
			->method( 'classify' )
			->with(
				$this->equalTo( 'Foo' ),
				$this->equalTo( $languageCandidates ) )
			->will( $this->returnValue( array() ) );

		$instance = new TextCatLanguageDetector( $textCat );
		$instance->setLanguageCandidates(
			$languageCandidates
		);

		$instance->detect( 'Foo' );
	}

}
