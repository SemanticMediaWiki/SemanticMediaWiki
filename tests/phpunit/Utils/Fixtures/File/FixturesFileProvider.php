<?php

namespace SMW\Tests\Utils\Fixtures\File;

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
			$dummyFileCreator->createFileByCopyContentOf( __DIR__ . '/' . 'LoremIpsum.txt' ),
			$desiredDestName
		);
	}

}
