<?php

namespace SMW\Tests\Lang;

use SMW\Lang\JsonContentsFileReader;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Lang\JsonContentsFileReader
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class JsonContentsFileReaderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

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

		$this->assertInternalType(
			'array',
			$instance->readByLanguageCode( $languageCode )
		);
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testReadByLanguageCodeWithCache( $languageCode ) {

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$cache->expects( $this->atLeastOnce() )
			->method( 'contains' )
			->will( $this->returnValue( true ) );

		$cache->expects( $this->atLeastOnce() )
			->method( 'fetch' )
			->will( $this->returnValue( [] ) );

		$instance = new JsonContentsFileReader( $cache );
		$instance->clear();

		$this->assertInternalType(
			'array',
			$instance->readByLanguageCode( $languageCode )
		);
	}

	public function testReadByLanguageCodeToUseInMemoryCache() {

		$instance = $this->getMockBuilder( JsonContentsFileReader::class )
			->setMethods( [ 'readJSONFile', 'getFileModificationTime' ] )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'readJSONFile' )
			->will( $this->returnValue( [] ) );

		$instance->expects( $this->once() )
			->method( 'getFileModificationTime' )
			->will( $this->returnValue( 42 ) );

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
			->will( $this->returnValue( [] ) );

		$instance->expects( $this->exactly( 2 ) )
			->method( 'getFileModificationTime' )
			->will( $this->returnValue( 42 ) );

		$instance->readByLanguageCode( 'bar' );
		$instance->readByLanguageCode( 'bar', true );
	}

	public function testTryToReadInaccessibleFileByLanguageThrowsException() {

		$instance = new JsonContentsFileReader();

		$this->setExpectedException( 'RuntimeException' );
		$instance->readByLanguageCode( 'foo', true );
	}

	/**
	 * This method is just for convenience so that one can quickly add contents to files
	 * without requiring an extra class when extending the language content. Normally the
	 * test in active
	 *
	 * @dataProvider dataExtensionProvider
	 */
	public function WriteToFile( $topic, $extension ) {

		$instance = new JsonContentsFileReader();
		$list ='ar,arz,ca,de,es,fi,fr,he,hu,id,it,nb,nl,pl,pt,ru,sk,zh-cn,zh-tw';

		foreach ( explode( ',', $list ) as $lang ) {
			$contents = $instance->readByLanguageCode( $lang, true );

			if ( $contents === '' || !isset( $contents[$topic] ) ) {
				continue;
			}

			$contents[$topic] = $contents[$topic] + $extension;

			$instance->writeByLanguageCode( $lang, $contents );
		}
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testgetFileModificationTime( $languageCode ) {

		$instance = new JsonContentsFileReader();

		$this->assertInternalType(
			'integer',
			$instance->getFileModificationTime( $languageCode )
		);
	}

	public function languageCodeProvider() {

		$provider[] = [
			'en'
		];

		return $provider;
	}

	public function dataExtensionProvider() {

		$provider[] = [
			'dataTypeLabels',
			[
				"_ref_rec" => "Reference"
			]
		];

		return $provider;
	}

}
