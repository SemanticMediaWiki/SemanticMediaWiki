<?php

namespace SMW\Tests\Structure;

use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @group semantic-mediawiki
 * @group system-test
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class I18nJsonFileIntegrityTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testPrettifyCanonicalMediaWikiI18NJson() {
		$i18nDir = !is_array( $GLOBALS['wgMessagesDirs']['SemanticMediaWiki'] )
				 ? $GLOBALS['wgMessagesDirs']['SemanticMediaWiki']
				 : $GLOBALS['wgMessagesDirs']['SemanticMediaWiki'][0];
		$target = $i18nDir . '/en.json';
		$contents = file_get_contents( $target );

		$json = json_encode(
			json_decode( $contents, true ),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);

		// Change the four-space indent to a tab indent
		$json = str_replace( "\n    ", "\n\t", $json ) . "\n";

		while ( strpos( $json, "\t    " ) !== false ) {
			$json = str_replace( "\t    ", "\t\t", $json );
		}

		if ( $contents !== $json ) {
			$isPretty = (bool)file_put_contents( $target, $json );
		} else {
			$isPretty = true;
		}

		$this->assertTrue(
			$isPretty
		);
	}

	/**
	 * @dataProvider mediawikiI18nFileProvider
	 */
	public function testMediaWikiI18NJsonDecodeEncode( $file ) {
		$jsonFileReader = UtilityFactory::getInstance()->newJsonFileReader( $file );

		$this->assertIsInt(

			$jsonFileReader->getModificationTime()
		);

		$this->assertIsArray(

			$jsonFileReader->read()
		);
	}

	/**
	 * @dataProvider semanticMediaWikiI18nFileProvider
	 */
	public function testSemanticMediaWikiI18NJsonDecodeEncode( $file ) {
		$jsonFileReader = UtilityFactory::getInstance()->newJsonFileReader( $file );

		$this->assertIsInt(

			$jsonFileReader->getModificationTime()
		);

		$contents = $jsonFileReader->read();

		$this->assertIsArray(

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
		$i18nDir = !is_array( $GLOBALS['wgMessagesDirs']['SemanticMediaWiki'] )
				 ? $GLOBALS['wgMessagesDirs']['SemanticMediaWiki']
				 : $GLOBALS['wgMessagesDirs']['SemanticMediaWiki'][0];

		return $this->findFilesIn( $i18nDir );
	}

	public function semanticMediaWikiI18nFileProvider() {
		$i18nDir = ( !is_array( $GLOBALS['smwgExtraneousLanguageFileDir'] )
					 ? $GLOBALS['smwgExtraneousLanguageFileDir']
					 : $GLOBALS['smwgExtraneousLanguageFileDir'][0] );

		return $this->findFilesIn( $i18nDir );
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
