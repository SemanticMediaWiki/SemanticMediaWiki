<?php

namespace SMW\Tests\ExtraneousLanguage;

use SMW\ExtraneousLanguage\LanguageJsonFileContentsReader;

/**
 * @covers \SMW\ExtraneousLanguage\LanguageJsonFileContentsReader
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class LanguageJsonFileContentsReaderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\ExtraneousLanguage\LanguageJsonFileContentsReader',
			new LanguageJsonFileContentsReader()
		);
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testReadByLanguageCode( $languageCode ) {

		$instance = new LanguageJsonFileContentsReader();

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
			->will( $this->returnValue( array() ) );

		$instance = new LanguageJsonFileContentsReader( $cache );
		$instance->clear();

		$this->assertInternalType(
			'array',
			$instance->readByLanguageCode( $languageCode )
		);
	}

	public function testReadByLanguageCodeToUseInMemoryCache() {

		$instance = $this->getMock( LanguageJsonFileContentsReader::class,
			array(
				'doReadJsonContentsFromFileBy',
				'getModificationTimeByLanguageCode'
			)
		);

		$instance->expects( $this->once() )
			->method( 'doReadJsonContentsFromFileBy' )
			->will( $this->returnValue( array() ) );

		$instance->expects( $this->once() )
			->method( 'getModificationTimeByLanguageCode' )
			->will( $this->returnValue( 42 ) );

		$instance->readByLanguageCode( 'foo' );

		// InMemory use
		$instance->readByLanguageCode( 'foo' );
	}

	public function testReadByLanguageCodeIsForcedToRereadFromFile() {

		$instance = $this->getMock( LanguageJsonFileContentsReader::class,
			array(
				'doReadJsonContentsFromFileBy',
				'getModificationTimeByLanguageCode'
			)
		);

		$instance->expects( $this->exactly( 2 ) )
			->method( 'doReadJsonContentsFromFileBy' )
			->will( $this->returnValue( array() ) );

		$instance->expects( $this->exactly( 2 ) )
			->method( 'getModificationTimeByLanguageCode' )
			->will( $this->returnValue( 42 ) );

		$instance->readByLanguageCode( 'bar' );
		$instance->readByLanguageCode( 'bar', true );
	}

	public function testTryToReadInaccessibleFileByLanguageThrowsException() {

		$instance = new LanguageJsonFileContentsReader();

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

		$instance = new LanguageJsonFileContentsReader();
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
	public function testGetModificationTimeByLanguageCode( $languageCode ) {

		$instance = new LanguageJsonFileContentsReader();

		$this->assertInternalType(
			'integer',
			$instance->getModificationTimeByLanguageCode( $languageCode )
		);
	}

	public function languageCodeProvider() {

		$provider[] = array(
			'en'
		);

		return $provider;
	}

	public function dataExtensionProvider() {

		$provider[] = array(
			'dataTypeLabels',
			array(
				"_ref_rec" => "Reference"
			)
		);

		return $provider;
	}

}
