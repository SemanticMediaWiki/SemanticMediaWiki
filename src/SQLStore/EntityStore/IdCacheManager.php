<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SQLStore\SQLStore;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class IdCacheManager {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @since 3.0
	 *
	 * @param array $caches
	 */
	public function __construct( array $caches ) {
		$this->caches = $caches;

		if ( !isset( $this->caches['entity.id'] ) ) {
			throw new RuntimeException( "Missing 'entity.id' instance.");
		}

		if ( !isset( $this->caches['entity.sort'] ) ) {
			throw new RuntimeException( "Missing 'entity.sort' instance.");
		}
	}

 	/**
	 * @since 3.0
	 *
	 * @param string|array $args
	 *
	 * @return string
	 */
	public static function computeSha1( $args = '' ) {
		return sha1( json_encode( $args ) );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function get( $key ) {

		if ( !isset( $this->caches[$key] ) ) {
			throw new RuntimeException( "$key is unknown");
		}

		return $this->caches[$key];
	}

	/**
	 * @since 3.0
	 *
	 * @param string $hash
	 *
	 * @return boolean
	 */
	public function hasCache( $hash ) {

		if ( !is_string( $hash ) ) {
			return false;
		}

		return $this->caches['entity.id']->contains( $hash );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $title
	 * @param integer $namespace
	 * @param string $interwiki
	 * @param string $subobject
	 * @param integer $id
	 * @param string $sortkey
	 */
	public function setCache( $title, $namespace, $interwiki, $subobject, $id, $sortkey ) {

		if ( strpos( $title, ' ' ) !== false ) {
			throw new RuntimeException( "Somebody tried to use spaces in a cache title! ($title)");
		}

		$hash = $this->computeSha1(
			[ $title, (int)$namespace, $interwiki, $subobject ]
		);

		$this->caches['entity.id']->save( $hash, $id );
		$this->caches['entity.sort']->save( $hash, $sortkey );

		// Speed up detection of redirects when fetching IDs
		if ( $interwiki == SMW_SQL3_SMWREDIIW ) {
			$this->setCache( $title, $namespace, '', $subobject, 0, '' );
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $title
	 * @param integer $namespace
	 * @param string $interwiki
	 * @param string $subobject
	 */
	public function deleteCache( $title, $namespace, $interwiki, $subobject ) {

		$hash = $this->computeSha1(
			[ $title, (int)$namespace, $interwiki, $subobject ]
		);

		$this->caches['entity.id']->delete( $hash );
		$this->caches['entity.sort']->delete( $hash );
	}

	/**
	 * Get a cached SMW ID, or false if no cache entry is found.
	 *
	 * @since 3.0
	 *
	 * @param DIWikiPage|array $args
	 *
	 * @return integer|boolean
	 */
	public function getId( $args ) {

		if ( $args instanceof DIWikiPage ) {
			$args = [
				$args->getDBKey(),
				(int)$args->getNamespace(),
				$args->getInterwiki(),
				$args->getSubobjectName()
			];
		}

		$hash = $this->computeSha1( $args );

		if ( ( $id = $this->caches['entity.id']->fetch( $hash ) ) !== false ) {
			return (int)$id;
		}

		return false;
	}

	/**
	 * Get a cached SMW sortkey, or false if no cache entry is found.
	 *
	 * @since 3.0
	 *
	 * @param string $title
	 * @param integer $namespace
	 * @param string $interwiki
	 * @param string $subobject
	 *
	 * @return string|boolean
	 */
	public function getSort( $args ) {

		$hash = $this->computeSha1( $args );

		if ( ( $sort = $this->caches['entity.sort']->fetch( $hash ) ) !== false ) {
			return $sort;
		}

		return false;
	}

}
