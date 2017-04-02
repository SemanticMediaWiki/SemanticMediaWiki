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
	 * @var array
	 */
	private $errors = array();

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
	public function getErrors() {
		return $this->errors;
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
	public function getContentList() {

		$contents = array();

		try{
			$files = $this->getFiles();
		} catch( RuntimeException $e ) {
			$this->errors[] = $this->importFileDir . ' is not accessible.';
			$files = array();
		}

		foreach ( $files as $file => $path ) {
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

			if ( isset( $value['page'] ) ) {
				$importContents->setName( $value['page'] );
			}

			if ( isset( $value['namespace'] ) ) {
				$importContents->setNamespace(
					defined( $value['namespace'] ) ? constant( $value['namespace'] ) : 0
				);
			}

			if ( !isset( $value['contents'] ) || $value['contents'] === '' ) {
				$importContents->addError( 'Missing, or has empty contents section' );
			} else {
				$this->fetchContents( $value['contents'], $importContents );
			}

			if ( !isset( $value['description'] ) ) {
				$importContents->setDescription( $fileContents['description'] );
			} else {
				$importContents->setDescription( $value['description'] );
			}

			$importContents->setVersion( $fileContents['meta']['version'] );

			if ( isset( $value['options'] ) ) {
				$importContents->setOptions( $value['options'] );
			}

			$contents[] = $importContents;
		}

		return $contents;
	}

	private function fetchContents( $contents, $importContents ) {

		$importContents->setContentType( ImportContents::CONTENT_TEXT );

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
			return $importContents->addError( $contents['importFrom'] . " wasn't accessible" );
		}

		$extension = pathinfo( $file, PATHINFO_EXTENSION );

		if ( $extension === 'xml' ) {
			$importContents->setContentType( ImportContents::CONTENT_XML );
		}

		$importContents->setContentsFile( $file );
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
