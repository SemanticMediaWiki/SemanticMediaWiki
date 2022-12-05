<?php

declare( strict_types = 1 );

namespace SMW;

use FileFetcher\FileFetcher;
use FileFetcher\FileFetchingException;
use SMW\Utils\File;

/**
 * @private
 */
class FileSystemSmwJsonRepo implements SmwJsonRepo {

	private /* FileFetcher */ $fileFetcher;
	private /* File */ $file;

	public function __construct( FileFetcher $fileFetcher, File $file ) {
		$this->fileFetcher = $fileFetcher;
		$this->file = $file;
	}

	public function loadSmwJson( string $configDirectory ): ?array {
		$filePath = $this->getFilePath( $configDirectory );

		try {
			$fileContents = $this->fileFetcher->fetchFile( $filePath );
		}
		catch ( FileFetchingException $ex ) {
			return null;
		}

		return json_decode( $fileContents, true );
	}

	private function getFilePath( string $configDirectory ): string {
		return File::dir( $configDirectory . '/' . SetupFile::FILE_NAME );
	}

	public function saveSmwJson( string $configDirectory, array $smwJson ): void {
		$filePath = $this->getFilePath( $configDirectory );
		$jsonString = json_encode( $smwJson, JSON_PRETTY_PRINT );

		$this->file->write( $filePath, $jsonString );
	}

}
