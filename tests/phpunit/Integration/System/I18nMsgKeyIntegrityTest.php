<?php

namespace SMW\Tests\System;

use SMW\Tests\Utils\UtilityFactory;

/**
 * @group semantic-mediawiki
 * @group system-test
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class I18nMsgKeyIntegrityTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider mediawikiI18nFileProvider
	 */
	public function testDecKiloSeparatorMsgKeySetting( $file ) {

		$jsonFileReader = UtilityFactory::getInstance()->newJsonFileReader( $file );
		$contents = $jsonFileReader->read();

		$isComplete = true;

		if ( isset( $contents['smw_decseparator'] ) ) {
			$isComplete = isset( $contents['smw_kiloseparator'] );
		}

		if ( $isComplete && isset( $contents['smw_kiloseparator'] ) ) {
			$isComplete = isset( $contents['smw_decseparator'] );
		}

		$this->assertTrue(
			$isComplete,
			'Failed on ' . basename( $file ) . ' with an incomplete pair of smw_decseparator/smw_kiloseparator.'
		);
	}

	/**
	 * @dataProvider mediawikiI18nFileProvider
	 */
	public function testDecKiloSeparatorHasDifferentValue( $file ) {

		$jsonFileReader = UtilityFactory::getInstance()->newJsonFileReader( $file );
		$contents = $jsonFileReader->read();

		$hasDifferentSeparatorValue = true;

		// Test on whether the values are different, otherwise fail
		if ( isset( $contents['smw_kiloseparator'] ) && isset( $contents['smw_decseparator'] ) ) {
			$hasDifferentSeparatorValue = $contents['smw_kiloseparator'] != $contents['smw_decseparator'];
		}

		$this->assertTrue(
			$hasDifferentSeparatorValue,
			'Failed on ' . basename( $file ) . ' where smw_decseparator/smw_kiloseparator have the same set of values.'
		);
	}

	public function mediawikiI18nFileProvider() {
		return $this->findFilesIn( $GLOBALS['wgMessagesDirs']['SemanticMediaWiki'] );
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
