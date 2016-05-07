<?php

namespace SMW;

use Onoi\Cache\Cache;
use Onoi\Cache\NullCache;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ExtraneousLanguageFileHandler {

	/**
	 * @var string
	 */
	private $extraneousLanguageFileDir = '';

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var string
	 */
	private $cachePrefix = 'smw:ex:lang';

	/**
	 * @var integer
	 */
	private $ttl = 604800; // 7 * 24 * 3600

	/**
	 * @since 2.4
	 *
	 * @param Cache|null $cache
	 * @param string $extraneousLanguageFileDir
	 */
	public function __construct( Cache $cache = null, $extraneousLanguageFileDir = '', $ttl = 0 ) {
		$this->cache = $cache;
		$this->extraneousLanguageFileDir = $extraneousLanguageFileDir;
		$this->ttl = $ttl;

		if ( $this->cache === null ) {
			$this->cache = new NullCache();
		}

		if ( $this->extraneousLanguageFileDir === '' ) {
			$this->extraneousLanguageFileDir = $GLOBALS['smwgExtraneousLanguageFileDir'];
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param string $cachePrefix
	 */
	public function setCachePrefix( $cachePrefix ) {
		$this->cachePrefix = $cachePrefix . $this->cachePrefix;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $languageCode
	 *
	 * @return SMWLanguage
	 * @throws RuntimeException
	 */
	public function newByLanguageCode( $languageCode ) {
		return $this->newClassByLanguageCode( $languageCode );
	}

	private function newClassByLanguageCode( $languageCode ) {

		$langClass = 'SMWLanguage' . str_replace( '-', '_', ucfirst( $languageCode ) );

		$file = str_replace(
			array( '\\', '/' ),
			DIRECTORY_SEPARATOR,
			$this->extraneousLanguageFileDir . '/' . 'SMW_Language' . str_replace( '-', '_', ucfirst( $languageCode ) ) . '.php'
		);

		if ( file_exists( $file ) ) {
			include_once ( $file );
		}

		if ( class_exists( $langClass ) ) {
			return new $langClass;
		}

		$fallbackLanguageCode = 'en';

		// This is a hack until the JSON format conversion is done
		if ( strpos( $languageCode, 'zh' ) !== false ) {
			$fallbackLanguageCode = 'zh-cn';
		}

		return $this->newClassByLanguageCode( $fallbackLanguageCode );
	}

}
