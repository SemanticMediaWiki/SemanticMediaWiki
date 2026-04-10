<?php

namespace SMW\Importer;

use RuntimeException;
use SMW\Exception\JSONFileParseException;
use SMW\Utils\File;
use SMW\Utils\FileFetcher;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class JsonImportContentsFileDirReader {

	private static array $contents = [];

	private array $errors = [];

	/**
	 * @since 2.5
	 */
	public function __construct(
		private readonly ContentModeller $contentModeller,
		private readonly FileFetcher $fileFetcher,
		private ?File $file = null,
		private $importFileDirs = [],
	) {
		if ( $this->importFileDirs === [] ) {
			$this->importFileDirs = $GLOBALS['smwgImportFileDirs'];
		}

		if ( $this->file === null ) {
			$this->file = new File();
		}
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * @since 2.5
	 *
	 * @return ImportContents[]
	 */
	public function getContentList(): array {
		$contents = [];
		sort( $this->importFileDirs );

		foreach ( $this->importFileDirs as $importFileDir ) {

			try {
				$files = $this->getFilesFromLocation( $importFileDir, 'json' );
			} catch ( RuntimeException $e ) {
				$this->errors[] = $importFileDir . ' is not accessible.';
				continue;
			}

			foreach ( $files as $file => $path ) {

				try {
					$content = $this->readJSONFile( $file );
				} catch ( JSONFileParseException $e ) {
					$this->errors[] = $e->getMessage();
					continue;
				}

				$contentList = $this->contentModeller->makeContentList(
					$importFileDir,
					$content
				);

				if ( $contentList === [] ) {
					continue;
				}

				$fileName = pathinfo( $file, PATHINFO_BASENAME );
				$contents[$fileName] = $contentList;
			}
		}

		return $contents;
	}

	private function readJSONFile( $file ): mixed {
		$contents = json_decode(
			$this->file->read( $file ),
			true
		);

		if ( $contents !== null && json_last_error() === JSON_ERROR_NONE ) {
			return $contents;
		}

		throw new JSONFileParseException( $file );
	}

	private function getFilesFromLocation( $path, string $extension ) {
		if ( $path === '' ) {
			return [];
		}

		$this->fileFetcher->setMaxDepth( 1 );
		$this->fileFetcher->setDir( $path );

		return $this->fileFetcher->findByExtension( $extension );
	}

}
