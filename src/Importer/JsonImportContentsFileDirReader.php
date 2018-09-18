<?php

namespace SMW\Importer;

use RuntimeException;
use SMW\Utils\ErrorCodeFormatter;

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
	 * @param array $importFileDirs
	 */
	public function __construct( ContentModeller $contentModeller, $importFileDirs = [] ) {
		$this->contentModeller = $contentModeller;
		$this->importFileDirs = $importFileDirs;

		if ( $this->importFileDirs === [] ) {
			$this->importFileDirs = $GLOBALS['smwgImportFileDirss'];
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

		foreach ( $this->importFileDirs as $importFileDir ) {

			try{
				$files = $this->getFilesFromLocation( $this->normalize( $importFileDir ), 'json' );
			} catch( RuntimeException $e ) {
				$this->errors[] = $importFileDir . ' is not accessible.';
				$files = [];
			}

			foreach ( $files as $file => $path ) {

				$contentList = $this->contentModeller->makeContentList(
					$importFileDir,
					$this->readJSONFile( $path )
				);

				if ( $contentList === [] ) {
					continue;
				}

				$contents[$file] = $contentList;
			}
		}

		return $contents;
	}

	private function readJSONFile( $file ) {

		$contents = json_decode(
			file_get_contents( $file ),
			true
		);

		if ( $contents !== null && json_last_error() === JSON_ERROR_NONE ) {
			return $contents;
		}

		throw new RuntimeException( ErrorCodeFormatter::getMessageFromJsonErrorCode( json_last_error() ) );
	}

	private function normalize( $importFileDir ) {

		if ( $importFileDir === '' ) {
			return '';
		}

		$path = str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $importFileDir );

		if ( is_readable( $path ) ) {
			return $path;
		}

		throw new RuntimeException( "Expected an accessible {$path} path" );
	}

	private function getFilesFromLocation( $path, $extension ) {

		if ( $path === '' ) {
			return [];
		}

		$files = [];

		$directoryIterator = new \RecursiveDirectoryIterator( $path );

		foreach ( new \RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( strtolower( substr( $fileInfo->getFilename(), -( strlen( $extension ) + 1 ) ) ) === ( '.' . $extension ) ) {
				$files[$fileInfo->getFilename()] = $fileInfo->getPathname();
			}
		}

		return $files;
	}

}
