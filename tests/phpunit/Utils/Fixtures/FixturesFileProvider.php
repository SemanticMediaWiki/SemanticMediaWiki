<?php

namespace SMW\Tests\Utils\Fixtures;

use SMW\Tests\Utils\File\LocalFileUpload;
use SMW\Tests\Utils\Fixtures\File\DummyFileCreator;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class FixturesFileProvider {

	/**
	 * @since 2.1
	 *
	 * @param string $desiredDestName
	 *
	 * @return LocalFileUpload
	 */
	public function newUploadForDummyTextFile( $desiredDestName ) {

		$dummyFileCreator = new DummyFileCreator( $desiredDestName );

		return new LocalFileUpload(
			$dummyFileCreator->createFileByCopyContentOf( __DIR__ . '/File/' . 'LoremIpsum.txt' ),
			$desiredDestName
		);
	}

}
