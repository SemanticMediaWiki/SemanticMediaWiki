<?php

namespace SMW\Tests\ExtraneousLanguage;

use SMW\ExtraneousLanguage\LanguageFallbackFinder;
use SMW\ExtraneousLanguage\jsonLanguageContentsFileReader;

/**
 * @covers \SMW\ExtraneousLanguage\LanguageFallbackFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class LanguageFallbackFinderTest extends \PHPUnit_Framework_TestCase {

	private $jsonLanguageContentsFileReader;

	protected function setUp() {

		$this->jsonLanguageContentsFileReader = $this->getMockBuilder( JsonLanguageContentsFileReader::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			LanguageFallbackFinder::class,
			new LanguageFallbackFinder( $this->jsonLanguageContentsFileReader )
		);
	}

	public function testGetDefaultFallbackLanguage() {

		$this->jsonLanguageContentsFileReader->expects( $this->never() )
			->method( 'readByLanguageCode' );

		$instance = new LanguageFallbackFinder(
			$this->jsonLanguageContentsFileReader
		);

		$this->assertEquals(
			'en',
			$instance->getFallbackLanguageBy( '' )
		);

		$this->assertEquals(
			$instance->getCanonicalFallbackLanguageCode(),
			$instance->getFallbackLanguageBy()
		);
	}

	public function testgetFallbackLanguageByStatedFallback() {

		$mockedContent = array(
			'fallbackLanguage' => 'Foo'
		);

		$this->jsonLanguageContentsFileReader->expects( $this->atLeastOnce() )
			->method( 'readByLanguageCode' )
			->will( $this->returnValue( $mockedContent ) );

		$instance = new LanguageFallbackFinder(
			$this->jsonLanguageContentsFileReader
		);

		$this->assertEquals(
			'Foo',
			$instance->getFallbackLanguageBy( 'unknownLanguageCode' )
		);
	}

	public function testgetFallbackLanguageByUnknownLanguageCode() {

		$this->jsonLanguageContentsFileReader->expects( $this->atLeastOnce() )
			->method( 'readByLanguageCode' )
			->will( $this->throwException( new \RuntimeException ) );

		$instance = new LanguageFallbackFinder(
			$this->jsonLanguageContentsFileReader
		);

		$this->assertEquals(
			'en',
			$instance->getFallbackLanguageBy( 'unknownLanguageCode' )
		);
	}

}
