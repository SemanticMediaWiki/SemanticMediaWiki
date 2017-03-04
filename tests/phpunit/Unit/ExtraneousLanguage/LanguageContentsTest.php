<?php

namespace SMW\Tests\ExtraneousLanguage;

use SMW\ExtraneousLanguage\JsonLanguageContentsFileReader;
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

	private $jsonLanguageContentsFileReader;
	private $languageFallbackFinder;

	protected function setUp() {

		$this->jsonLanguageContentsFileReader = $this->getMockBuilder( JsonLanguageContentsFileReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->languageFallbackFinder = $this->getMockBuilder( LanguageFallbackFinder::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			LanguageContents::class,
			new LanguageContents( $this->jsonLanguageContentsFileReader, $this->languageFallbackFinder )
		);
	}

	public function testGetCanonicalFallbackLanguageCode() {

		$this->languageFallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getCanonicalFallbackLanguageCode' );

		$instance = new LanguageContents(
			$this->jsonLanguageContentsFileReader,
			$this->languageFallbackFinder
		);

		$instance->getCanonicalFallbackLanguageCode();
	}

	public function testPrepareWithLanguageWithoutFallback() {

		$languageCode = 'Foo';

		$this->jsonLanguageContentsFileReader->expects( $this->atLeastOnce() )
			->method( 'canReadByLanguageCode' )
			->will( $this->returnValue( true ) );

		$this->jsonLanguageContentsFileReader->expects( $this->atLeastOnce() )
			->method( 'readByLanguageCode' )
			->with( $this->equalTo( $languageCode ) );

		$instance = new LanguageContents(
			$this->jsonLanguageContentsFileReader,
			$this->languageFallbackFinder
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

		$this->jsonLanguageContentsFileReader->expects( $this->at( 0 ) )
			->method( 'readByLanguageCode' )
			->with( $this->equalTo( $languageCode ) )
			->will( $this->returnValue( array() ) );

		$this->jsonLanguageContentsFileReader->expects( $this->at( 1 ) )
			->method( 'readByLanguageCode' )
			->with( $this->equalTo( $fallback ) )
			->will( $this->returnValue( array( 'Bar' => 123 ) ) );

		$this->languageFallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getCanonicalFallbackLanguageCode' )
			->will( $this->returnValue( 'en' ) );

		$this->languageFallbackFinder->expects( $this->at( 1 ) )
			->method( 'getFallbackLanguageBy' )
			->will( $this->returnValue( $fallback ) );

		$instance = new LanguageContents(
			$this->jsonLanguageContentsFileReader,
			$this->languageFallbackFinder
		);

		$this->assertEquals(
			123,
			$instance->getContentsByLanguageWithIndex( $languageCode, 'Bar' )
		);
	}

	public function testGetContentsByLanguageWithIndexWithFallbackButMissingIndexThrowsException() {

		$languageCode = 'Foo';
		$fallback = 'Foobar';

		$this->jsonLanguageContentsFileReader->expects( $this->at( 0 ) )
			->method( 'readByLanguageCode' )
			->with( $this->equalTo( $languageCode ) )
			->will( $this->returnValue( array() ) );

		$this->jsonLanguageContentsFileReader->expects( $this->at( 1 ) )
			->method( 'readByLanguageCode' )
			->with( $this->equalTo( $fallback ) )
			->will( $this->returnValue( array() ) );

		$this->languageFallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getCanonicalFallbackLanguageCode' )
			->will( $this->returnValue( 'en' ) );

		$this->languageFallbackFinder->expects( $this->at( 1 ) )
			->method( 'getFallbackLanguageBy' )
			->will( $this->returnValue( $fallback ) );

		$this->languageFallbackFinder->expects( $this->at( 3 ) )
			->method( 'getFallbackLanguageBy' )
			->will( $this->returnValue( 'en' ) );

		$instance = new LanguageContents(
			$this->jsonLanguageContentsFileReader,
			$this->languageFallbackFinder
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->getContentsByLanguageWithIndex( $languageCode, 'Bar' );
	}

}
