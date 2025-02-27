<?php

namespace SMW\Tests\Utils\Fixtures;

use SMW\Tests\Utils\File\DummyFileCreator;
use SMW\Tests\Utils\File\LocalFileUpload;

/**
 * @license GPL-2.0-or-later
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
		$dummyFileCreator = new DummyFileCreator();
		$dummyFileCreator->createFileWithCopyFrom( $desiredDestName, __DIR__ . '/File/' . 'LoremIpsum.json' );

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
		$dummyFileCreator = new DummyFileCreator();
		$dummyFileCreator->createFileWithCopyFrom( $desiredDestName, __DIR__ . '/File/' . 'LoremIpsum.txt' );

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
