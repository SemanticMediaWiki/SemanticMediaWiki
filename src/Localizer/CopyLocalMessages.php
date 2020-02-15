<?php

namespace SMW\Localizer;

use RuntimeException;
use SMW\Exception\JSONFileParseException;
use SMW\Utils\FileFetcher;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CopyLocalMessages {

	/**
	 * @var string
	 */
	private $file = '';

	/**
	 * @var string
	 */
	private $languageFileDir = '';

	/**
	 * @since 3.2
	 *
	 * @param string $file
	 */
	public function __construct( string $file, string $languageFileDir = null ) {
		$this->file = $file;
		$this->languageFileDir = $languageFileDir ?? $GLOBALS['wgMessagesDirs']['SemanticMediaWiki'];
	}

	/**
	 * @since 3.2
	 *
	 * @return array
	 */
	public function copyCanonicalMessages() : array {

		$messages = $this->readJSONFile(
			$this->languageFileDir . '/' . $this->file
		);

		$contents = $this->readJSONFile(
			$this->languageFileDir . '/en.json'
		);

		$messages_count = 0;

		foreach ( $messages as $key => $message ) {

			if ( isset( $contents[$key] ) && $contents[$key] === $message['en'] ) {
				continue;
			}

			$contents[$key] = $message['en'];
			$messages_count++;
		}

		$json = json_encode(
			$contents,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);

		file_put_contents( $this->languageFileDir . '/en.json', $this->prettify( $json ) );

		return [
			'messages_count' => $messages_count
		];
	}

	/**
	 * @since 3.2
	 *
	 * @return array
	 */
	public function copyTranslatedMessages() : array {

		$messages = $this->readJSONFile( $this->languageFileDir . '/' . $this->file );

		$fileFetcher = new FileFetcher(
			$this->languageFileDir
		);

		$fileFetcher->setMaxDepth( 0 );

		$files = $fileFetcher->findByExtension( 'json' );
		$files_count = 0;
		$messages_count = 0;

		foreach ( $files as $file ) {
			$pathinfo = pathinfo( $file[0] );

			// The filename is languagecode
			$languageCode = $pathinfo['filename'];

			if (
				$languageCode === 'en' ||
				$this->file === $pathinfo['basename'] ) {
				continue;
			}

			$languageContents = $this->readJSONFile( $file[0] );
			$files_count++;

			foreach ( $messages as $key => &$message ) {

				if ( !isset( $languageContents[$key] ) ) {
					continue;
				}

				$message[$languageCode] = $languageContents[$key];
				$messages_count++;
			}
		}

		$json = json_encode(
			$messages,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);

		file_put_contents( $this->languageFileDir . '/' . $this->file, $this->prettify( $json ) );

		return [
			'files_count' => $files_count,
			'messages_count' => $messages_count
		];
	}

	private function readJSONFile( $file ) {

		$file = str_replace( [ '\\', '/', '//', '\\\\' ], DIRECTORY_SEPARATOR, $file );

		if ( !is_readable( $file ) ) {
			throw new RuntimeException( "Expected a {$file} file" );
		}

		$contents = json_decode( file_get_contents( $file ), true );

		if ( $contents !== null && json_last_error() === JSON_ERROR_NONE ) {
			return $this->contents = $contents;
		}

		throw new JSONFileParseException( $file );
	}

	private function prettify( $json ) {

		// Change the four-space indent to a tab indent
		$json = str_replace( "\n    ", "\n\t", $json );

		while ( strpos( $json, "\t    " ) !== false ) {
			$json = str_replace( "\t    ", "\t\t", $json );
		}

		return $json;
	}

}
