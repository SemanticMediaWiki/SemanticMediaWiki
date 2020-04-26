<?php

namespace SMW\Importer;

use RuntimeException;
use SMW\Exception\JSONFileParseException;
use SMW\Utils\FileFetcher;
use SMW\Utils\File;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class JsonImportContentsFileDirReader {

	/**
	 * @var ContentModeller
	 */
	private $contentModeller;

	/**
	 * @var FileFetcher
	 */
	private $fileFetcher;

	/**
	 * @var File
	 */
	private $file;

	/**
	 * @var array
	 */
	private static $contents = [];

	/**
	 * @var array
	 */
	private $errors = [];

	/**
	 * @var []
	 */
	private $importFileDirs = [];

	/**
	 * @since 2.5
	 *
	 * @param ContentModeller $contentModeller
	 * @param FileFetcher $fileFetcher
	 * @param File|null $file
	 * @param array $importFileDirs
	 */
	public function __construct( ContentModeller $contentModeller, FileFetcher $fileFetcher, File $file = null, $importFileDirs = [] ) {
		$this->contentModeller = $contentModeller;
		$this->fileFetcher = $fileFetcher;
		$this->file = $file;
		$this->importFileDirs = $importFileDirs;

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
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 2.5
	 *
	 * @return ImportContents[]
	 */
	public function getContentList() {

		$contents = [];
		sort( $this->importFileDirs );

		foreach ( $this->importFileDirs as $importFileDir ) {

			try {
				$files = $this->getFilesFromLocation( $importFileDir, 'json' );
			} catch( RuntimeException $e ) {
				$this->errors[] = $importFileDir . ' is not accessible.';
				continue;
			}

			foreach ( $files as $file => $path ) {

				try {
					$content = $this->readJSONFile( $file );
				} catch( JSONFileParseException $e ) {
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

	private function readJSONFile( $file ) {

		$contents = json_decode(
			$this->file->read( $file ),
			true
		);

		if ( $contents !== null && json_last_error() === JSON_ERROR_NONE ) {
			return $contents;
		}

		throw new JSONFileParseException( $file );
	}

	private function getFilesFromLocation( $path, $extension ) {

		if ( $path === '' ) {
			return [];
		}

		$this->fileFetcher->setMaxDepth( 1 );
		$this->fileFetcher->setDir( $path );

		return $this->fileFetcher->findByExtension( $extension );
	}

}
