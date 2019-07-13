<?php

namespace SMW\Tests\System;

use SMW\Tests\Utils\UtilityFactory;

/**
 * @group semantic-mediawiki
 * @group system-test
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class I18nExtraneousLanguageFileIntegrityTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider i18nFileProvider
	 */
	public function testPropertyLabelTrailingSpaces( $file ) {

		$jsonFileReader = UtilityFactory::getInstance()->newJsonFileReader( $file );
		$contents = $jsonFileReader->read();
		$isComplete = true;
		$missedLabelPair = [];

		if ( !isset( $contents['property']['labels'] ) ) {
			return $this->markTestSkipped( 'No property label for ' . basename( $file ) . ' available.' );
		}

		foreach ( $contents['property']['labels'] as $key => $label ) {
			if ( $label !== trim( $label ) ) {
				$isComplete = false;
				$missedLabelPair[$key] = $label;
			}
		}

		$this->assertTrue(
			$isComplete,
			'Failed on ' . basename( $file ) . ' with trailing spaces ' . json_encode( $missedLabelPair, JSON_UNESCAPED_UNICODE )
		);
	}

	/**
	 * @dataProvider i18nFileProvider
	 */
	public function testPropertyAliasesTrailingSpaces( $file ) {

		$jsonFileReader = UtilityFactory::getInstance()->newJsonFileReader( $file );
		$contents = $jsonFileReader->read();
		$isComplete = true;
		$missedAliasPair = [];

		if ( !isset( $contents['property']['aliases'] ) ) {
			return $this->markTestSkipped( 'No property aliases for ' . basename( $file ) . ' available.' );
		}

		foreach ( $contents['property']['aliases'] as $label => $key ) {
			if ( $label !== trim( $label ) ) {
				$isComplete = false;
				$missedAliasPair[$label] = $key;
			}
		}

		$this->assertTrue(
			$isComplete,
			'Failed on ' . basename( $file ) . ' with trailing spaces ' . json_encode( $missedAliasPair, JSON_UNESCAPED_UNICODE )
		);
	}

	public function i18nFileProvider() {
		return $this->findFilesIn( $GLOBALS['smwgExtraneousLanguageFileDir'] );
	}

	private function findFilesIn( $location ) {

		$provider = [];

		$bulkFileProvider = UtilityFactory::getInstance()->newBulkFileProvider( $location );
		$bulkFileProvider->searchByFileExtension( 'json' );

		foreach ( $bulkFileProvider->getFiles() as $id => $file ) {
			$provider[$id] = [ $file ];
		}

		return $provider;
	}

}
