<?php

namespace SMW\Tests\Localizer\LocalLanguage;

use SMW\Localizer\LocalLanguage\FallbackFinder;
use SMW\Localizer\LocalLanguage\JsonContentsFileReader;
use SMW\Localizer\LocalLanguage\LanguageContents;

/**
 * @covers \SMW\Localizer\LocalLanguage\LanguageContents
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class LanguageContentsTest extends \PHPUnit\Framework\TestCase {

	private $jsonContentsFileReader;
	private $fallbackFinder;

	protected function setUp(): void {
		$this->jsonContentsFileReader = $this->getMockBuilder( JsonContentsFileReader::class )
			->disableOriginalConstructor()
			->getMock();

		$this->fallbackFinder = $this->getMockBuilder( FallbackFinder::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			LanguageContents::class,
			new LanguageContents( $this->jsonContentsFileReader, $this->fallbackFinder )
		);
	}

	public function testGetCanonicalFallbackLanguageCode() {
		$this->fallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getCanonicalFallbackLanguageCode' );

		$instance = new LanguageContents(
			$this->jsonContentsFileReader,
			$this->fallbackFinder
		);

		$instance->getCanonicalFallbackLanguageCode();
	}

	public function testPrepareWithLanguageWithoutFallback() {
		$languageCode = 'Foo';

		$this->jsonContentsFileReader->expects( $this->atLeastOnce() )
			->method( 'canReadByLanguageCode' )
			->willReturn( true );

		$this->jsonContentsFileReader->expects( $this->atLeastOnce() )
			->method( 'readByLanguageCode' )
			->with( $languageCode );

		$instance = new LanguageContents(
			$this->jsonContentsFileReader,
			$this->fallbackFinder
		);

		$this->assertFalse(
			$instance->isLoaded( $languageCode )
		);

		$instance->load( $languageCode );

		$this->assertTrue(
			$instance->isLoaded( $languageCode )
		);
	}

	public function testGetContentsByLanguage_ID_Depth_2() {
		$languageCode = 'Foo';

		$this->jsonContentsFileReader->expects( $this->once() )
			->method( 'readByLanguageCode' )
			->with( $languageCode )
			->willReturn( [ 'Foo' => [ 'Bar' => 123 ] ] );

		$this->fallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getCanonicalFallbackLanguageCode' )
			->willReturn( 'en' );

		$instance = new LanguageContents(
			$this->jsonContentsFileReader,
			$this->fallbackFinder
		);

		$this->assertEquals(
			123,
			$instance->get( 'Foo.Bar', $languageCode )
		);
	}

	public function testGetContentsByLanguage_ID_Depth_3() {
		$languageCode = 'Foo';

		$this->jsonContentsFileReader->expects( $this->once() )
			->method( 'readByLanguageCode' )
			->with( $languageCode )
			->willReturn( [ 'Foo' => [ 'Bar' => [ 'Foobar' => 456 ] ] ] );

		$this->fallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getCanonicalFallbackLanguageCode' )
			->willReturn( 'en' );

		$instance = new LanguageContents(
			$this->jsonContentsFileReader,
			$this->fallbackFinder
		);

		$this->assertEquals(
			456,
			$instance->get( 'Foo.Bar.Foobar', $languageCode )
		);
	}

	public function testGetContentsByLanguageWithIndexWithFallback() {
		$languageCode = 'Foo';
		$fallback = 'Foobar';

		$readCallCount = 0;
		$this->jsonContentsFileReader->expects( $this->exactly( 2 ) )
			->method( 'readByLanguageCode' )
			->willReturnCallback( function ( $code ) use ( &$readCallCount, $languageCode, $fallback ) {
				$readCallCount++;
				if ( $readCallCount === 1 ) {
					$this->assertEquals( $languageCode, $code );
					return [];
				}
				$this->assertEquals( $fallback, $code );
				return [ 'Bar' => 123 ];
			} );

		$this->fallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getCanonicalFallbackLanguageCode' )
			->willReturn( 'en' );

		$this->fallbackFinder->expects( $this->once() )
			->method( 'getFallbackLanguageBy' )
			->willReturn( $fallback );

		$instance = new LanguageContents(
			$this->jsonContentsFileReader,
			$this->fallbackFinder
		);

		$this->assertEquals(
			123,
			$instance->get( 'Bar', $languageCode )
		);
	}

	public function testGetContentsByLanguageWithIndexWithFallbackButMissingIndexThrowsException() {
		$languageCode = 'Foo';
		$fallback = 'Foobar';

		$this->jsonContentsFileReader->expects( $this->atLeastOnce() )
			->method( 'readByLanguageCode' )
			->willReturn( [] );

		$this->fallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getCanonicalFallbackLanguageCode' )
			->willReturn( 'en' );

		$fallbackCallCount = 0;
		$this->fallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getFallbackLanguageBy' )
			->willReturnCallback( static function () use ( &$fallbackCallCount, $fallback ) {
				$fallbackCallCount++;
				return $fallbackCallCount === 1 ? $fallback : 'en';
			} );

		$instance = new LanguageContents(
			$this->jsonContentsFileReader,
			$this->fallbackFinder
		);

		$this->expectException( 'RuntimeException' );
		$instance->get( 'Bar', $languageCode );
	}

}
