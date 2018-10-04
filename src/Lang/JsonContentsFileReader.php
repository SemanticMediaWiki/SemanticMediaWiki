<?php

namespace SMW\Lang;

use Onoi\Cache\Cache;
use Onoi\Cache\NullCache;
use RuntimeException;
use SMW\Utils\ErrorCodeFormatter;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class JsonContentsFileReader {

	/**
	 * @var array
	 */
	private static $contents = [];

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
		self::$contents = [];
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
	public function getFileModificationTime( $languageCode ) {
		return filemtime( $this->getLanguageFile( $languageCode ) );
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
			$canReadByLanguageCode = $this->getLanguageFile( $languageCode );
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
			$this->getLanguageFile( $languageCode ),
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

		$cacheKey = smwfCacheKey(
			'smw:lang',
			[
				$languageCode,
				$this->getFileModificationTime( $languageCode ),
				$this->ttl
			]
		);

		if ( !$readFromFile && !$this->skipCache && !isset( self::$contents[$languageCode] ) && $this->cache->contains( $cacheKey ) ) {
			self::$contents[$languageCode] = $this->cache->fetch( $cacheKey );
		}

		if ( $readFromFile || !isset( self::$contents[$languageCode] ) ) {
			self::$contents[$languageCode] = $this->readJSONFile( $languageCode, $cacheKey );
		}

		return self::$contents[$languageCode];
	}

	protected function readJSONFile( $languageCode, $cacheKey ) {

		$contents = json_decode(
			file_get_contents( $this->getLanguageFile( $languageCode ) ),
			true
		);

		if ( $contents !== null && json_last_error() === JSON_ERROR_NONE ) {
			$this->cache->save( $cacheKey, $contents, $this->ttl );
			return $contents;
		}

		throw new RuntimeException( ErrorCodeFormatter::getMessageFromJsonErrorCode( json_last_error() ) );
	}

	private function getLanguageFile( $languageCode ) {

		$file = str_replace( [ '\\', '/' ], DIRECTORY_SEPARATOR, $this->languageFileDir . '/' . $languageCode . '.json' );

		if ( is_readable( $file ) ) {
			return $file;
		}

		throw new RuntimeException( "Expected a {$file} file" );
	}

}
