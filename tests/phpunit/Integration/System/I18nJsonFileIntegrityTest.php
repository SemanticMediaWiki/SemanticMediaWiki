<?php

namespace SMW\Tests\System;

use SMW\Tests\Utils\UtilityFactory;

/**
 * @group semantic-mediawiki
 * @group system-test
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class I18nJsonFileIntegrityTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider mediawikiI18nFileProvider
	 */
	public function testMediaWikiI18NJsonDecodeEncode( $file ) {

		$jsonFileReader = UtilityFactory::getInstance()->newJsonFileReader( $file );

		$this->assertInternalType(
			'integer',
			$jsonFileReader->getModificationTime()
		);

		$this->assertInternalType(
			'array',
			$jsonFileReader->read()
		);
	}

	/**
	 * @dataProvider semanticMediaWikiI18nFileProvider
	 */
	public function testSemanticMediaWikiI18NJsonDecodeEncode( $file ) {

		$jsonFileReader = UtilityFactory::getInstance()->newJsonFileReader( $file );

		$this->assertInternalType(
			'integer',
			$jsonFileReader->getModificationTime()
		);

		$contents = $jsonFileReader->read();

		$this->assertInternalType(
			'array',
			$contents
		);

		$expectedKeys = [
			'fallback_language',
			'datatype'  => [ 'labels', 'aliases' ],
			'property'  => [ 'labels', 'aliases' ],
			'namespace' => [ 'labels', 'aliases' ],
			'date'      => [ 'precision', 'format', 'months', 'days' ]
		];

		// If the file is marked with isLanguageRedirect then only check for the fallbackLanguage
		if ( isset( $contents['isLanguageRedirect'] ) && $contents['isLanguageRedirect'] ) {
			return $this->assertArrayHasKey( 'fallback_language', $contents, $file );
		}

		foreach ( $expectedKeys as $key => $val ) {
			if ( !is_array( $val ) ) {
				$this->assertArrayHasKey( $val, $contents, "Failed on $file with key: $val" );
			} else {
				foreach ( $val as $k => $v ) {
					$this->assertArrayHasKey( $v, $contents[$key], "Failed on $file with key: $key/$v" );
				}
			}
		}
	}

	public function mediawikiI18nFileProvider() {
		return $this->findFilesIn( $GLOBALS['wgMessagesDirs']['SemanticMediaWiki'] );
	}

	public function semanticMediaWikiI18nFileProvider() {
		return $this->findFilesIn( $GLOBALS['smwgExtraneousLanguageFileDir'] );
	}

	private function findFilesIn( $location ) {

		$provider = [];

		$bulkFileProvider = UtilityFactory::getInstance()->newBulkFileProvider( $location );
		$bulkFileProvider->searchByFileExtension( 'json' );

		foreach ( $bulkFileProvider->getFiles() as $file ) {
			$provider[] = [ $file ];
		}

		return $provider;
	}

}
