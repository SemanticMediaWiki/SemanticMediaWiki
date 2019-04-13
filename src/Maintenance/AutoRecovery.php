<?php

namespace SMW\Maintenance;

use SMW\Utils\File;
use RuntimeException;
use SMW\Site;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class AutoRecovery {

	const FILE_NAME = '.smw.maintenance.json';

	/**
	 * @var string
	 */
	private $identifier = '';

	/**
	 * @var string
	 */
	private $site = '';

	/**
	 * @var File
	 */
	private $file;

	/**
	 * @var boolean
	 */
	private $enabled = false;

	/**
	 * @var integer
	 */
	private $safeMargin = 0;

	/**
	 * @var array
	 */
	private $contents;

	/**
	 * @since 3.1
	 *
	 * @param string $identifier
	 * @param File|null $file
	 */
	public function __construct( $identifier, File $file = null ) {
		$this->identifier = $identifier;
		$this->file = $file;
		$this->site = Site::id();

		if ( $this->file === null ) {
			$this->file = new File();
		}

		$this->dir = $GLOBALS['smwgConfigFileDir'];
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $enabled
	 */
	public function enable( $enabled ) {
		$this->enabled = $enabled;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $dir
	 */
	public function setDir( $dir ) {
		$this->dir = $dir;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $safeMargin
	 */
	public function safeMargin( $safeMargin ) {
		$this->safeMargin = $safeMargin;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getLocation() {
		return $this->dir . "/" . self::FILE_NAME;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( $key, $value ) {

		if ( $this->contents === null ) {
			$this->initContents( $key );
		}

		$this->contents[$this->site][$this->identifier][$key] = $value;

		$this->file->write(
			$this->getLocation(),
			json_encode( $this->contents )
		);
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get( $key ) {

		if ( !$this->enabled ) {
			return false;
		}

		if ( $this->contents === null ) {
			$this->initContents( $key );
		}

		$value = $this->contents[$this->site][$this->identifier][$key];

		if ( is_int( $value ) ) {
			return max( 0, $value - $this->safeMargin );
		}

		return $value;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function has( $key ) {

		if ( !$this->enabled ) {
			return false;
		}

		if ( $this->contents === null ) {
			$this->initContents( $key );
		}

		if ( !isset( $this->contents[$this->site][$this->identifier][$key] ) ) {
			return false;
		}

		return $this->contents[$this->site][$this->identifier][$key] !== false;
	}

	private function initContents( $key ) {

		$file = $this->getLocation();

		$this->contents = [
			$this->site => [ $this->identifier => [ $key => false ] ]
		];

		if ( !$this->file->exists( $file ) ) {
			$this->file->write( $file, json_encode( $this->contents ) );
		} else {
			$this->contents = json_decode( $this->file->read( $file ), true );
		}

		if ( !isset( $this->contents[$this->site][$this->identifier][$key] ) ) {
			$this->contents[$this->site][$this->identifier][$key] = false;
		}
	}

}
