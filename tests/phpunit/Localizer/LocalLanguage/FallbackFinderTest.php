<?php

namespace SMW\Tests\Localizer\LocalLanguage;

use SMW\Localizer\LocalLanguage\FallbackFinder;
use SMW\Localizer\LocalLanguage\JsonContentsFileReader;

/**
 * @covers \SMW\Localizer\LocalLanguage\FallbackFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class FallbackFinderTest extends \PHPUnit_Framework_TestCase {

	private $jsonContentsFileReader;

	protected function setUp() : void {

		$this->jsonContentsFileReader = $this->getMockBuilder( JsonContentsFileReader::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			FallbackFinder::class,
			new FallbackFinder( $this->jsonContentsFileReader )
		);
	}

	public function testGetDefaultFallbackLanguage() {

		$this->jsonContentsFileReader->expects( $this->never() )
			->method( 'readByLanguageCode' );

		$instance = new FallbackFinder(
			$this->jsonContentsFileReader
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

		$mockedContent = [
			'fallback_language' => 'Foo'
		];

		$this->jsonContentsFileReader->expects( $this->atLeastOnce() )
			->method( 'readByLanguageCode' )
			->will( $this->returnValue( $mockedContent ) );

		$instance = new FallbackFinder(
			$this->jsonContentsFileReader
		);

		$this->assertEquals(
			'Foo',
			$instance->getFallbackLanguageBy( 'unknownLanguageCode' )
		);
	}

	public function testgetFallbackLanguageByUnknownLanguageCode() {

		$this->jsonContentsFileReader->expects( $this->atLeastOnce() )
			->method( 'readByLanguageCode' )
			->will( $this->throwException( new \RuntimeException ) );

		$instance = new FallbackFinder(
			$this->jsonContentsFileReader
		);

		$this->assertEquals(
			'en',
			$instance->getFallbackLanguageBy( 'unknownLanguageCode' )
		);
	}

}
