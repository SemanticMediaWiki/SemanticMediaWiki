<?php

namespace SMW\Tests\ExtraneousLanguage;

use SMW\ExtraneousLanguage\LanguageFallbackFinder;
use SMW\ExtraneousLanguage\LanguageFileContentsReader;

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

	public function testCanConstruct() {

		$languageFileContentsReader = $this->getMockBuilder( LanguageFileContentsReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			LanguageFallbackFinder::class,
			new LanguageFallbackFinder( $languageFileContentsReader )
		);
	}

	public function testGetDefaultFallbackLanguage() {

		$languageFileContentsReader = $this->getMockBuilder( LanguageFileContentsReader::class )
			->disableOriginalConstructor()
			->getMock();

		$languageFileContentsReader->expects( $this->never() )
			->method( 'readByLanguageCode' );

		$instance = new LanguageFallbackFinder(
			$languageFileContentsReader
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

		$languageFileContentsReader = $this->getMockBuilder( LanguageFileContentsReader::class )
			->disableOriginalConstructor()
			->getMock();

		$languageFileContentsReader->expects( $this->atLeastOnce() )
			->method( 'readByLanguageCode' )
			->will( $this->returnValue( $mockedContent ) );

		$instance = new LanguageFallbackFinder(
			$languageFileContentsReader
		);

		$this->assertEquals(
			'Foo',
			$instance->getFallbackLanguageBy( 'unknownLanguageCode' )
		);
	}

	public function testgetFallbackLanguageByUnknownLanguageCode() {

		$languageFileContentsReader = $this->getMockBuilder( LanguageFileContentsReader::class )
			->disableOriginalConstructor()
			->getMock();

		$languageFileContentsReader->expects( $this->atLeastOnce() )
			->method( 'readByLanguageCode' )
			->will( $this->throwException( new \RuntimeException ) );

		$instance = new LanguageFallbackFinder(
			$languageFileContentsReader
		);

		$this->assertEquals(
			'en',
			$instance->getFallbackLanguageBy( 'unknownLanguageCode' )
		);
	}

}
