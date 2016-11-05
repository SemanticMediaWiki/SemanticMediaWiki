<?php

namespace SMW\Tests\ExtraneousLanguage;

use SMW\ExtraneousLanguage\LanguageFallbackFinder;
use SMW\ExtraneousLanguage\LanguageJsonFileContentsReader;

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

		$languageJsonFileContentsReader = $this->getMockBuilder( LanguageJsonFileContentsReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			LanguageFallbackFinder::class,
			new LanguageFallbackFinder( $languageJsonFileContentsReader )
		);
	}

	public function testGetDefaultFallbackLanguage() {

		$languageJsonFileContentsReader = $this->getMockBuilder( LanguageJsonFileContentsReader::class )
			->disableOriginalConstructor()
			->getMock();

		$languageJsonFileContentsReader->expects( $this->never() )
			->method( 'readByLanguageCode' );

		$instance = new LanguageFallbackFinder(
			$languageJsonFileContentsReader
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

		$languageJsonFileContentsReader = $this->getMockBuilder( LanguageJsonFileContentsReader::class )
			->disableOriginalConstructor()
			->getMock();

		$languageJsonFileContentsReader->expects( $this->atLeastOnce() )
			->method( 'readByLanguageCode' )
			->will( $this->returnValue( $mockedContent ) );

		$instance = new LanguageFallbackFinder(
			$languageJsonFileContentsReader
		);

		$this->assertEquals(
			'Foo',
			$instance->getFallbackLanguageBy( 'unknownLanguageCode' )
		);
	}

	public function testgetFallbackLanguageByUnknownLanguageCode() {

		$languageJsonFileContentsReader = $this->getMockBuilder( LanguageJsonFileContentsReader::class )
			->disableOriginalConstructor()
			->getMock();

		$languageJsonFileContentsReader->expects( $this->atLeastOnce() )
			->method( 'readByLanguageCode' )
			->will( $this->throwException( new \RuntimeException ) );

		$instance = new LanguageFallbackFinder(
			$languageJsonFileContentsReader
		);

		$this->assertEquals(
			'en',
			$instance->getFallbackLanguageBy( 'unknownLanguageCode' )
		);
	}

}
