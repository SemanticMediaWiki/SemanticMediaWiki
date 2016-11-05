<?php

namespace SMW\Tests\ExtraneousLanguage;

use SMW\ExtraneousLanguage\LanguageJsonFileContentsReader;
use SMW\ExtraneousLanguage\LanguageContents;
use SMW\ExtraneousLanguage\LanguageFallbackFinder;

/**
 * @covers \SMW\ExtraneousLanguage\LanguageContents
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class LanguageContentsTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$languageJsonFileContentsReader = $this->getMockBuilder( LanguageJsonFileContentsReader::class )
			->disableOriginalConstructor()
			->getMock();

		$languageFallbackFinder = $this->getMockBuilder( LanguageFallbackFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			LanguageContents::class,
			new LanguageContents( $languageJsonFileContentsReader, $languageFallbackFinder )
		);
	}

	public function testGetCanonicalFallbackLanguageCode() {

		$languageJsonFileContentsReader = $this->getMockBuilder( LanguageJsonFileContentsReader::class )
			->disableOriginalConstructor()
			->getMock();

		$languageFallbackFinder = $this->getMockBuilder( LanguageFallbackFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$languageFallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getCanonicalFallbackLanguageCode' );

		$instance = new LanguageContents(
			$languageJsonFileContentsReader,
			$languageFallbackFinder
		);

		$instance->getCanonicalFallbackLanguageCode();
	}

	public function testPrepareWithLanguageWithoutFallback() {

		$languageCode = 'Foo';

		$languageJsonFileContentsReader = $this->getMockBuilder( LanguageJsonFileContentsReader::class )
			->disableOriginalConstructor()
			->getMock();

		$languageJsonFileContentsReader->expects( $this->atLeastOnce() )
			->method( 'canReadByLanguageCode' )
			->will( $this->returnValue( true ) );

		$languageJsonFileContentsReader->expects( $this->atLeastOnce() )
			->method( 'readByLanguageCode' )
			->with( $this->equalTo( $languageCode ) );

		$languageFallbackFinder = $this->getMockBuilder( LanguageFallbackFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new LanguageContents(
			$languageJsonFileContentsReader,
			$languageFallbackFinder
		);

		$this->assertFalse(
			$instance->has( $languageCode )
		);

		$instance->prepareWithLanguage( $languageCode );

		$this->assertTrue(
			$instance->has( $languageCode )
		);
	}

	public function testGetContentsByLanguageWithIndexWithFallback() {

		$languageCode = 'Foo';
		$fallback = 'Foobar';

		$languageJsonFileContentsReader = $this->getMockBuilder( LanguageJsonFileContentsReader::class )
			->disableOriginalConstructor()
			->getMock();

		$languageJsonFileContentsReader->expects( $this->at( 0 ) )
			->method( 'readByLanguageCode' )
			->with( $this->equalTo( $languageCode ) )
			->will( $this->returnValue( array() ) );

		$languageJsonFileContentsReader->expects( $this->at( 1 ) )
			->method( 'readByLanguageCode' )
			->with( $this->equalTo( $fallback ) )
			->will( $this->returnValue( array( 'Bar' => 123 ) ) );

		$languageFallbackFinder = $this->getMockBuilder( LanguageFallbackFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$languageFallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getCanonicalFallbackLanguageCode' )
			->will( $this->returnValue( 'en' ) );

		$languageFallbackFinder->expects( $this->at( 1 ) )
			->method( 'getFallbackLanguageBy' )
			->will( $this->returnValue( $fallback ) );

		$instance = new LanguageContents(
			$languageJsonFileContentsReader,
			$languageFallbackFinder
		);


		$this->assertEquals(
			123,
			$instance->getContentsByLanguageWithIndex( $languageCode, 'Bar' )
		);
	}

	public function testGetContentsByLanguageWithIndexWithFallbackButMissingIndexThrowsException() {

		$languageCode = 'Foo';
		$fallback = 'Foobar';

		$languageJsonFileContentsReader = $this->getMockBuilder( LanguageJsonFileContentsReader::class )
			->disableOriginalConstructor()
			->getMock();

		$languageJsonFileContentsReader->expects( $this->at( 0 ) )
			->method( 'readByLanguageCode' )
			->with( $this->equalTo( $languageCode ) )
			->will( $this->returnValue( array() ) );

		$languageJsonFileContentsReader->expects( $this->at( 1 ) )
			->method( 'readByLanguageCode' )
			->with( $this->equalTo( $fallback ) )
			->will( $this->returnValue( array() ) );

		$languageFallbackFinder = $this->getMockBuilder( LanguageFallbackFinder::class )
			->disableOriginalConstructor()
			->getMock();

		$languageFallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getCanonicalFallbackLanguageCode' )
			->will( $this->returnValue( 'en' ) );

		$languageFallbackFinder->expects( $this->at( 1 ) )
			->method( 'getFallbackLanguageBy' )
			->will( $this->returnValue( $fallback ) );

		$languageFallbackFinder->expects( $this->at( 3 ) )
			->method( 'getFallbackLanguageBy' )
			->will( $this->returnValue( 'en' ) );

		$instance = new LanguageContents(
			$languageJsonFileContentsReader,
			$languageFallbackFinder
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->getContentsByLanguageWithIndex( $languageCode, 'Bar' );
	}

}
