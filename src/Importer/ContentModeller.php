<?php

namespace SMW\Importer;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ContentModeller {

	/**
	 * @since 3.0
	 *
	 * @param string $fileDir
	 * @param array $fileContents
	 *
	 * @return ImportContents[]|[]
	 */
	public function makeContentList( $fileDir, array $fileContents ) {

		$contents = [];

		if ( !isset( $fileContents['import'] ) ) {
			return $contents;
		}

		foreach ( $fileContents['import'] as $value ) {

			$importContents = new ImportContents();

			if ( isset( $value['namespace'] ) ) {
				$importContents->setNamespace(
					defined( $value['namespace'] ) ? constant( $value['namespace'] ) : 0
				);
			}

			if ( isset( $value['page'] ) ) {
				$importContents->setName( $value['page'] );
			}

			if ( isset( $value['description'] ) ) {
				$importContents->setDescription( $value['description'] );
			} elseif ( isset( $fileContents['description'] ) ) {
				$importContents->setDescription( $fileContents['description'] );
			} else {
				$importContents->setDescription( 'No description' );
			}

			if ( isset( $fileContents['meta']['version'] ) ) {
				$importContents->setVersion( $fileContents['meta']['version'] );
			} else {
				$importContents->setVersion( 0 );
			}

			if ( isset( $value['contents']['type'] ) && $value['contents']['type'] === 'xml' ) {
				$contents[] = $this->newImportContents( $importContents, $fileDir, $value );
			} else {
				$contents[] = $this->newImportContents( $importContents, $fileDir, $value );
			}
		}

		return $contents;
	}

	private function newImportContents( $importContents, $fileDir, $value ) {

		$importContents->setContentType( ImportContents::CONTENT_TEXT );

		if ( !isset( $value['contents'] ) || $value['contents'] === '' ) {
			$importContents->addError( 'Missing, or has empty contents section' );
		} else {
			$this->setContents( $importContents, $fileDir, $value['contents'] );
		}

		if ( isset( $value['options'] ) ) {
			$importContents->setOptions( $value['options'] );
		}

		return $importContents;
	}

	private function setContents( $importContents, $fileDir, $contents ) {

		if ( !is_array( $contents ) || !isset( $contents['importFrom'] ) ) {
			return $importContents->setContents( $contents );
		}

		$file = $this->normalizeFile( $fileDir, $contents['importFrom'] );

		if ( !is_readable( $file ) ) {
			return $importContents->addError( "File: " . $file . " wasn't accessible" );
		}

		$extension = pathinfo( $file, PATHINFO_EXTENSION );

		if ( isset( $contents['type'] ) && $contents['type'] === 'xml' && $extension !== 'xml' ) {
			return $importContents->addError( "XML: " . $file . " is not recognized as xml file extension" );
		}

		if ( $extension === 'xml' ) {
			$importContents->setContentType( ImportContents::CONTENT_XML );
		}

		$importContents->setContentsFile( $file );
	}

	private function normalizeFile( $fileDir, $file ) {
		return str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $fileDir . ( $file[0] === '/' ? '' : '/' ) . $file );
	}

}
