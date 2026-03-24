<?php

namespace SMW\Importer;

/**
 * @license GPL-2.0-or-later
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
	 * @return mixed[]
	 */
	public function makeContentList( string $fileDir, array $fileContents ): array {
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

			if ( isset( $value['import_performer'] ) ) {
				$importContents->setImportPerformer( $value['import_performer'] );
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

			$contents[] = $this->newImportContents( $importContents, $fileDir, $value );
		}

		return $contents;
	}

	private function newImportContents( ImportContents $importContents, string $fileDir, array $value ): ImportContents {
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

	private function setContents( ImportContents $importContents, string $fileDir, $contents ): void {
		if ( !is_array( $contents ) || !isset( $contents['importFrom'] ) ) {
			$importContents->setContents( $contents );
			return;
		}

		$file = $this->normalizeFile( $fileDir, $contents['importFrom'] );

		if ( !is_readable( $file ) ) {
			$importContents->addError( "File: " . $file . " wasn't accessible" );
			return;
		}

		$extension = pathinfo( $file, PATHINFO_EXTENSION );

		if ( isset( $contents['type'] ) && $contents['type'] === 'xml' && $extension !== 'xml' ) {
			$importContents->addError( "XML: " . $file . " is not recognized as xml file extension" );
			return;
		}

		if ( $extension === 'xml' ) {
			$importContents->setContentType( ImportContents::CONTENT_XML );
		}

		$importContents->setContentsFile( $file );
	}

	private function normalizeFile( string $fileDir, string $file ): string {
		return str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $fileDir . ( $file[0] === '/' ? '' : '/' ) . $file );
	}

}
