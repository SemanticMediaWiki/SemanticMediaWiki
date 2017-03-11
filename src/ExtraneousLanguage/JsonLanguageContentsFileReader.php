<?php

namespace SMW\ExtraneousLanguage;

use RuntimeException;
use Onoi\Cache\Cache;
use Onoi\Cache\NullCache;
use SMW\Utils\ErrorCodeFormatter;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class JsonLanguageContentsFileReader {

	/**
	 * @var array
	 */
	private static $contents = array();

	/**
	 * @var string
	 */
	private $languageFileDir = '';

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var boolean
	 */
	private $skipCache = false;

	/**
	 * @var string
	 */
	private $cachePrefix = 'smw:ex:lang';

	/**
	 * @var integer
	 */
	private $ttl = 604800; // 7 * 24 * 3600

	/**
	 * @since 2.5
	 *
	 * @param Cache|null $cache
	 * @param string $languageFileDir
	 */
	public function __construct( Cache $cache = null, $languageFileDir = '' ) {
		$this->cache = $cache;
		$this->languageFileDir = $languageFileDir;

		if ( $this->cache === null ) {
			$this->cache = new NullCache();
		}

		if ( $this->languageFileDir === '' ) {
			$this->languageFileDir = $GLOBALS['smwgExtraneousLanguageFileDir'];
		}
	}

	/**
	 * @since 2.5
	 */
	public static function clear() {
		self::$contents = array();
	}

	/**
	 * @since 2.5
	 *
	 * @param string $cachePrefix
	 */
	public function setCachePrefix( $cachePrefix ) {
		$this->cachePrefix = $cachePrefix . $this->cachePrefix;
	}

	/**
	 * @since 2.5
	 */
	public function skipCache() {
		$this->skipCache = true;
	}

	/**
	 * @since 1.2.0
	 *
	 * @return integer
	 */
	public function getModificationTimeByLanguageCode( $languageCode ) {
		return filemtime( $this->getFileForLanguageCode( $languageCode ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $languageCode
	 * @param boolean $readFromFile
	 *
	 * @return boolean
	 */
	public function canReadByLanguageCode( $languageCode ) {

		$canReadByLanguageCode = '';

		try {
			$canReadByLanguageCode = $this->getFileForLanguageCode( $languageCode );
		} catch ( \Exception $e ) {
			$canReadByLanguageCode = '';
		}

		return $canReadByLanguageCode !== '';
	}

	/**
	 * @since 2.5
	 *
	 * @param string $languageCode
	 * @param array $contents
	 */
	public function writeByLanguageCode( $languageCode, $contents ) {

		$languageCode = strtolower( trim( $languageCode ) );

		file_put_contents(
			$this->getFileForLanguageCode( $languageCode ),
			json_encode( $contents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param string $languageCode
	 * @param boolean $readFromFile
	 *
	 * @return array
	 * @throws RuntimeException
	 */
	public function readByLanguageCode( $languageCode, $readFromFile = false ) {

		$languageCode = strtolower( trim( $languageCode ) );

		if ( !$readFromFile && isset( self::$contents[$languageCode] ) ) {
			return self::$contents[$languageCode];
		}

		$cacheKey = $this->getCacheKeyFrom( $languageCode );

		if ( !$readFromFile && !$this->skipCache && !isset( self::$contents[$languageCode] ) && $this->cache->contains( $cacheKey ) ) {
			self::$contents[$languageCode] = $this->cache->fetch( $cacheKey );
		}

		if ( $readFromFile || !isset( self::$contents[$languageCode] ) ) {
			self::$contents[$languageCode] = $this->doReadJsonContentsFromFileBy( $languageCode, $cacheKey );
		}

		return self::$contents[$languageCode];
	}

	protected function doReadJsonContentsFromFileBy( $languageCode, $cacheKey ) {

		$contents = json_decode(
			file_get_contents( $this->getFileForLanguageCode( $languageCode ) ),
			true
		);

		if ( $contents !== null && json_last_error() === JSON_ERROR_NONE ) {
			$this->cache->save( $cacheKey, $contents, $this->ttl );
			return $contents;
		}

		throw new RuntimeException( ErrorCodeFormatter::getMessageFromJsonErrorCode( json_last_error() ) );
	}

	private function getFileForLanguageCode( $languageCode ) {

		$file = str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, $this->languageFileDir . '/' . $languageCode . '.json' );

		if ( is_readable( $file ) ) {
			return $file;
		}

		throw new RuntimeException( "Expected a {$file} file" );
	}

	private function getCacheKeyFrom( $languageCode ) {
		return $this->cachePrefix . ':' . $languageCode . ':' . md5( $this->ttl . $this->getModificationTimeByLanguageCode( $languageCode ) );
	}

}
