<?php

namespace SMW\Tests\Unit\Localizer\LocalLanguage;

use PHPUnit\Framework\TestCase;
use SMW\Localizer\LocalLanguage\JsonContentsFileReader;
use Wikimedia\ObjectCache\BagOStuff;

/**
 * @covers \SMW\Localizer\LocalLanguage\JsonContentsFileReader
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class JsonContentsFileReaderTest extends TestCase {

	private string $languageFileDir = '';

	protected function setUp(): void {
		$this->languageFileDir = sys_get_temp_dir() . '/' . uniqid( 'smw-lang-', true );

		mkdir( $this->languageFileDir );

		// `getLanguageFile` requires the file to exist before it can be written.
		file_put_contents( $this->languageFileDir . '/zxx.json', '{}' );
	}

	protected function tearDown(): void {
		foreach ( glob( $this->languageFileDir . '/*.json' ) as $file ) {
			unlink( $file );
		}

		rmdir( $this->languageFileDir );

		JsonContentsFileReader::clear();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			JsonContentsFileReader::class,
			new JsonContentsFileReader()
		);
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testReadByLanguageCode( $languageCode ) {
		$instance = new JsonContentsFileReader();

		$this->assertIsArray(

			$instance->readByLanguageCode( $languageCode )
		);
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testReadByLanguageCodeWithCache( $languageCode ) {
		$cache = $this->getMockBuilder( BagOStuff::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$cache->expects( $this->atLeastOnce() )
			->method( 'get' )
			->willReturn( [] );

		$instance = new JsonContentsFileReader( $cache );
		$instance->clear();

		$this->assertIsArray(

			$instance->readByLanguageCode( $languageCode )
		);
	}

	public function testReadByLanguageCodeToUseInMemoryCache() {
		$instance = $this->getMockBuilder( JsonContentsFileReader::class )
			->setMethods( [ 'readJSONFile', 'getFileModificationTime' ] )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'readJSONFile' )
			->willReturn( [] );

		$instance->expects( $this->once() )
			->method( 'getFileModificationTime' )
			->willReturn( 42 );

		$instance->readByLanguageCode( 'foo' );

		// InMemory use
		$instance->readByLanguageCode( 'foo' );
	}

	public function testReadByLanguageCodeIsForcedToRereadFromFile() {
		$instance = $this->getMockBuilder( JsonContentsFileReader::class )
			->setMethods( [ 'readJSONFile', 'getFileModificationTime' ] )
			->getMock();

		$instance->expects( $this->exactly( 2 ) )
			->method( 'readJSONFile' )
			->willReturn( [] );

		$instance->expects( $this->exactly( 2 ) )
			->method( 'getFileModificationTime' )
			->willReturn( 42 );

		$instance->readByLanguageCode( 'bar' );
		$instance->readByLanguageCode( 'bar', true );
	}

	public function testTryToReadInaccessibleFileByLanguageThrowsException() {
		$instance = new JsonContentsFileReader();

		$this->expectException( 'RuntimeException' );
		$instance->readByLanguageCode( 'foo', true );
	}

	public function testWriteByLanguageCodeRoundTripsContents() {
		$instance = new JsonContentsFileReader( null, $this->languageFileDir );

		// A slash and a non-ASCII label: both survive a round trip regardless
		// of how json_encode escapes them on disk.
		$contents = [
			'namespace' => [ 'SMW_NS_SCHEMA' => 'SMW/Schema' ],
			'datatype' => [ 'labels' => [ '_ref_rec' => 'Verknüpfung' ] ],
		];

		$instance->writeByLanguageCode( 'zxx', $contents );

		$this->assertSame(
			$contents,
			$instance->readByLanguageCode( 'zxx', true )
		);
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testgetFileModificationTime( $languageCode ) {
		$instance = new JsonContentsFileReader();

		$this->assertIsInt(

			$instance->getFileModificationTime( $languageCode )
		);
	}

	public function languageCodeProvider() {
		$provider[] = [
			'en'
		];

		return $provider;
	}

}
