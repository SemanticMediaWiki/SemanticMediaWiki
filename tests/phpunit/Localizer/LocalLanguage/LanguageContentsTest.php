<?php

namespace SMW\Tests\Localizer\LocalLanguage;

use SMW\Localizer\LocalLanguage\FallbackFinder;
use SMW\Localizer\LocalLanguage\JsonContentsFileReader;
use SMW\Localizer\LocalLanguage\LanguageContents;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Localizer\LocalLanguage\LanguageContents
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class LanguageContentsTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

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

		$this->jsonContentsFileReader->expects( $this->at( 0 ) )
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

		$this->jsonContentsFileReader->expects( $this->at( 0 ) )
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

		$this->jsonContentsFileReader->expects( $this->at( 0 ) )
			->method( 'readByLanguageCode' )
			->with( $languageCode )
			->willReturn( [] );

		$this->jsonContentsFileReader->expects( $this->at( 1 ) )
			->method( 'readByLanguageCode' )
			->with( $fallback )
			->willReturn( [ 'Bar' => 123 ] );

		$this->fallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getCanonicalFallbackLanguageCode' )
			->willReturn( 'en' );

		$this->fallbackFinder->expects( $this->at( 1 ) )
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

		$this->jsonContentsFileReader->expects( $this->at( 0 ) )
			->method( 'readByLanguageCode' )
			->with( $languageCode )
			->willReturn( [] );

		$this->jsonContentsFileReader->expects( $this->at( 1 ) )
			->method( 'readByLanguageCode' )
			->with( $fallback )
			->willReturn( [] );

		$this->fallbackFinder->expects( $this->atLeastOnce() )
			->method( 'getCanonicalFallbackLanguageCode' )
			->willReturn( 'en' );

		$this->fallbackFinder->expects( $this->at( 1 ) )
			->method( 'getFallbackLanguageBy' )
			->willReturn( $fallback );

		$this->fallbackFinder->expects( $this->at( 3 ) )
			->method( 'getFallbackLanguageBy' )
			->willReturn( 'en' );

		$instance = new LanguageContents(
			$this->jsonContentsFileReader,
			$this->fallbackFinder
		);

		$this->expectException( 'RuntimeException' );
		$instance->get( 'Bar', $languageCode );
	}

}
