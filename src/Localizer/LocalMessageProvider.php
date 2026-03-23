<?php

namespace SMW\Localizer;

use RuntimeException;
use SMW\Exception\JSONFileParseException;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class LocalMessageProvider implements MessageLocalizer {

	private /* string */ $languageFileDir = '';
	private /* string */ $fallbackLanguageCode = 'en';
	private /* array */ $contents = [];

	/**
	 * @since 3.2
	 */
	public function __construct(
		private readonly string $file,
		private ?string $languageCode = null,
	) {
		$this->languageFileDir = !is_array( $GLOBALS['wgMessagesDirs']['SemanticMediaWiki'] )
							  ? $GLOBALS['wgMessagesDirs']['SemanticMediaWiki']
							  : $GLOBALS['wgMessagesDirs']['SemanticMediaWiki'][0];
	}

	/**
	 * @since 3.2
	 */
	public function loadMessages(): void {
		$this->contents = $this->readJSONFile( $this->file );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $languageFileDir
	 */
	public function setLanguageFileDir( string $languageFileDir ): void {
		$this->languageFileDir = $languageFileDir;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $languageCode
	 */
	public function setLanguageCode( string $languageCode ): void {
		$this->languageCode = $languageCode;
	}

	/**
	 * @since 3.2
	 *
	 * @param string|array ...$args
	 *
	 * @return bool
	 */
	public function has( ...$args ): bool {
		$key = array_shift( $args );
		$msgArgs = [];

		if ( is_array( $key ) ) {
			$msgArgs = $key;
			$key = array_shift( $msgArgs );
		}

		if (
			isset( $this->contents[$key][$this->languageCode] ) ||
			isset( $this->contents[$key][$this->fallbackLanguageCode] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @since 3.2
	 *
	 * @param string|array ...$args
	 *
	 * @return string
	 */
	public function msg( ...$args ): string {
		$key = array_shift( $args );
		$msgArgs = [];

		if ( is_array( $key ) ) {
			$msgArgs = $key;
			$key = array_shift( $msgArgs );
		}

		if ( isset( $this->contents[$key][$this->languageCode] ) ) {
			$message = $this->contents[$key][$this->languageCode];
		} elseif ( isset( $this->contents[$key][$this->fallbackLanguageCode] ) ) {
			$message = $this->contents[$key][$this->fallbackLanguageCode];
		} else {
			$message = '⧼' . htmlspecialchars( $key ) . '⧽';
		}

		foreach ( $msgArgs as $k => $value ) {
			$message = str_replace( "$" . ++$k, $value, $message );
		}

		return $message;
	}

	private function readJSONFile( string $file ) {
		$file = str_replace( [ '\\', '/', '//', '\\\\' ], DIRECTORY_SEPARATOR, $this->languageFileDir . '/' . $file );

		if ( !is_readable( $file ) ) {
			throw new RuntimeException( "Expected a {$file} file" );
		}

		$contents = json_decode( file_get_contents( $file ), true );

		if ( $contents !== null && json_last_error() === JSON_ERROR_NONE ) {
			$this->contents = $contents;
			return $this->contents;
		}

		throw new JSONFileParseException( $file );
	}

}
