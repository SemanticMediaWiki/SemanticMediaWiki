<?php

namespace SMW\Importer;

use SMW\Utils\ErrorCodeFormatter;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class JsonImportContentsFileDirReader {

	/**
	 * @var array
	 */
	private static $contents = array();

	/**
	 * @var string
	 */
	private $importFileDir = '';

	/**
	 * @since 2.5
	 *
	 * @param string $importFileDir
	 */
	public function __construct( $importFileDir = '' ) {
		$this->importFileDir = $importFileDir;

		if ( $this->importFileDir === '' ) {
			$this->importFileDir = $GLOBALS['smwgImportFileDir'];
		}
	}

	/**
	 * @since 2.5
	 *
	 * @return array
	 */
	public function getFiles() {
		return $this->getFilesFromLocation( $this->getImportFileDir(), 'json' );
	}

	/**
	 * @since 2.5
	 *
	 * @return ImportContents[]
	 * @throws RuntimeException
	 */
	public function getContents() {

		$contents = array();

		foreach ( $this->getFiles() as $file => $path ) {
			$importContents = $this->getImportContents( $this->doFetchContentsFrom( $path ) );

			if ( $importContents === null ) {
				continue;
			}

			$contents[$file] = $importContents;
		}

		return $contents;
	}

	private function getImportContents( $fileContents ) {

		$contents = array();

		if ( !isset( $fileContents['import'] ) ) {
			return;
		}

		foreach ( $fileContents['import'] as $value ) {
			$importContents = new ImportContents();

			if ( !isset( $value['page'] ) || !isset( $value['namespace'] ) ) {
				$importContents->addError( 'Missing page or namespace section' );
			} else {
				$importContents->setName( $value['page'] );
				$importContents->setNamespace( constant( $value['namespace'] ) );
			}

			if ( !isset( $value['contents'] ) || $value['contents'] === '' ) {
				$importContents->addError( 'Missing, or has empty contents section' );
			} else {
				$this->fetchContents( $value['contents'], $importContents );
			}

			$importContents->setVersion( $fileContents['meta']['version'] );
			$importContents->setDescription( $fileContents['description'] );

			if ( isset( $value['options'] ) ) {
				$importContents->setOptions( $value['options'] );
			}

			$contents[] = $importContents;
		}

		return $contents;
	}

	private function fetchContents( $contents, $importContents ) {

		if ( !is_array( $contents ) || !isset( $contents['importFrom'] ) ) {
			return $importContents->setContents( $contents );
		}

		$file = $contents['importFrom'];

		$file = str_replace(
			array( '\\', '/' ),
			DIRECTORY_SEPARATOR,
			$this->importFileDir . ( $file{0} === '/' ? '' : '/' ) . $file
		);

		if ( !is_readable( $file ) ) {
			return $importContents->addError( "reading of " . $contents['importFrom'] . " contents failed (not accessible)" );
		}

		$contents = file_get_contents( $file );

		// http://php.net/manual/en/function.file-get-contents.php
		$contents = mb_convert_encoding(
			$contents,
			'UTF-8',
			mb_detect_encoding(
				$contents,
				'UTF-8, ISO-8859-1, ISO-8859-2',
				true
			)
		);

		$importContents->setContents(
			$contents
		);
	}

	private function doFetchContentsFrom( $file ) {

		$contents = json_decode(
			file_get_contents( $file ),
			true
		);

		if ( $contents !== null && json_last_error() === JSON_ERROR_NONE ) {
			return $contents;
		}

		throw new RuntimeException( ErrorCodeFormatter::getMessageFromJsonErrorCode( json_last_error() ) );
	}

	private function getImportFileDir() {

		$path = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $this->importFileDir );

		if ( is_readable( $path ) ) {
			return $path;
		}

		throw new RuntimeException( "Expected an accessible {$path} path" );
	}

	private function getFilesFromLocation( $path, $extension ) {

		$files = array();

		$directoryIterator = new \RecursiveDirectoryIterator( $path );

		foreach ( new \RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( strtolower( substr( $fileInfo->getFilename(), -( strlen( $extension ) + 1 ) ) ) === ( '.' . $extension ) ) {
				$files[$fileInfo->getFilename()] = $fileInfo->getPathname();
			}
		}

		return $files;
	}

}
