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
	 * @return DummyFileCreator
	 */
	public function newDummyJsonFile( $desiredDestName ) {

		$dummyFileCreator = new DummyFileCreator( $desiredDestName );
		$dummyFileCreator->createFileByCopyContentOf( __DIR__ . '/File/' . 'LoremIpsum.json' );

		return $dummyFileCreator;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $desiredDestName
	 *
	 * @return DummyFileCreator
	 */
	public function newDummyTextFile( $desiredDestName ) {

		$dummyFileCreator = new DummyFileCreator( $desiredDestName );
		$dummyFileCreator->createFileByCopyContentOf( __DIR__ . '/File/' . 'LoremIpsum.txt' );

		return $dummyFileCreator;
	}

	/**
	 * @since 2.1
	 *
	 * @param string $desiredDestName
	 *
	 * @return LocalFileUpload
	 */
	public function newUploadForDummyTextFile( $desiredDestName ) {

		$dummyTextFile = $this->newDummyTextFile( $desiredDestName );

		return new LocalFileUpload(
			$dummyTextFile->getPath(),
			$desiredDestName
		);
	}

}
