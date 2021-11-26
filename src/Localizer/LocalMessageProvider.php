<?php

namespace SMW\Localizer;

use RuntimeException;
use SMW\Exception\JSONFileParseException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class LocalMessageProvider implements MessageLocalizer {

	/**
	 * @var string
	 */
	private static string $i18nDir = __DIR__ . '/../../i18n';

	/**
	 * @var string
	 */
	private static string $smwExtraI18nDir = __DIR__ . '/../../i18n/extra';

	/**
	 * @var string
	 */
	private string $file = '';

	/**
	 * @var string
	 */
	private ?string $languageCode;

	/**
	 * @var string
	 */
	private string $languageFileDir = '';

	/**
	 * @var string
	 */
	private string $fallbackLanguageCode = 'en';

	/**
	 * @var array
	 */
	private array $contents = [];

	/**
	 * @since 3.2
	 *
	 * @param string $file
	 * @param ?string $languageCode
	 */
	public function __construct( string $file, ?string $languageCode = null ) {
		$this->file = $file;
		$this->languageCode = $languageCode;
		$this->languageFileDir = self::$i18nDir;
	}

	/**
	 * @since 4.0
	 */
	public static function setI18nDir( $dir ): void {
		self::$i18nDir = !is_array( $dir ) ? $dir : $dir[0];
	}

	/**
	 * @since 4.0
	 */
	public static function getI18nDir(): string {
		return self::$i18nDir;
	}

	/**
	 * @since 4.0
	 */
	public static function setSMWExtraI18nDir( $dir ): void {
		self::$smwExtraI18nDir = !is_array( $dir ) ? $dir : $dir[0];
	}

	/**
	 * @since 4.0
	 */
	public static function getSMWExtraI18nDir(): string {
		return self::$smwExtraI18nDir;
	}

	/**
	 * @since 3.2
	 */
	public function loadMessages() {
		$this->contents = $this->readJSONFile( $this->file );
	}

	/**
	 * @since 3.2
	 *
	 * @param string $languageFileDir
	 */
	public function setLanguageFileDir( string $languageFileDir ) {
		$this->languageFileDir = $languageFileDir;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $languageCode
	 */
	public function setLanguageCode( string $languageCode ) {
		$this->languageCode = $languageCode;
	}

	/**
	 * @since 3.2
	 *
	 * @param string|array $args
	 *
	 * @return bool
	 */
	public function has( ...$args ) : bool {

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
	 * @param string|array $args
	 *
	 * @return string
	 */
	public function msg( ...$args ) : string {

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

	private function readJSONFile( $file ) {

		$file = str_replace( [ '\\', '/', '//', '\\\\' ], DIRECTORY_SEPARATOR, $this->languageFileDir . '/' . $file );

		if ( !is_readable( $file ) ) {
			throw new RuntimeException( "Expected a {$file} file" );
		}

		$contents = json_decode( file_get_contents( $file ), true );

		if ( $contents !== null && json_last_error() === JSON_ERROR_NONE ) {
			return $this->contents = $contents;
		}

		throw new JSONFileParseException( $file );
	}

}
