<?php

namespace Onoi\Tesa\StopwordAnalyzer;

use Cdb\Reader;
use Cdb\Writer;
use Exception;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class CdbStopwordAnalyzer implements StopwordAnalyzer {

	/**
	 * Any change to the content of its data files should be reflected in a
	 * version change (the version number does not necessarily correlate with
	 * the library version)
	 */
	const VERSION = '0.1.cdb';

	/**
	 * @var Cdb
	 */
	private $cdb;

	/**
	 * @since 0.1
	 *
	 * @param string $target
	 */
	public function __construct( $target ) {
		try {
			$this->cdb = Reader::open( $target );
		} catch( Exception $e ) {
			// Do nothing
		}
	}

	/**
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isAvailable() {
		return $this->cdb !== null;
	}

	/**
	 * @since 0.1
	 *
	 * @param string $language
	 *
	 * @return string
	 */
	public static function getLocation() {
		return str_replace( array( '\\', '/' ), DIRECTORY_SEPARATOR, __DIR__ . '/data/' );
	}

	/**
	 * @since 0.1
	 *
	 * @param string $language
	 *
	 * @return string
	 */
	public static function getTargetByLanguage( $language ) {
		return self::getLocation() . 'cdb/' . strtolower( $language )  . '.cdb';
	}

	/**
	 * @since 0.1
	 *
	 * @param string $word
	 *
	 * @return boolean
	 */
	public function isStopWord( $word ) {

		if ( $this->cdb !== null && $this->cdb->get( $word ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * @since 0.1
	 *
	 * @param string $location
	 * @param string $language
	 *
	 * @return boolean
	 */
	public static function createCdbByLanguage( $location, $language ) {

		$language = strtolower( $language );
		$source = $location . $language . '.json';

		if ( !file_exists( $source ) ) {
			throw new RuntimeException( "{$source} is not available." );
		}

		$contents = json_decode( file_get_contents( $source ), true );

		if ( !isset( $contents['list'] ) ) {
			throw new RuntimeException( "JSON is missing the `list` index." );
		}

		$writer = Writer::open(
			self::getTargetByLanguage( $language )
		);

		foreach ( $contents['list'] as $words ) {
			$writer->set( trim( $words ), true );
		}

		$writer->close();

		return true;
	}

}
