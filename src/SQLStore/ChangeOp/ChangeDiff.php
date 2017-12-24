<?php

namespace SMW\SQLStore\ChangeOp;

use SMW\DIWikiPage;
use Onoi\Cache\Cache;
use SMW\Utils\HmacSerializer;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class ChangeDiff {

	/**
	 * Identifies the cache namespace
	 */
	const CACHE_NAMESPACE = 'smw:store:diff';

	/**
	 * Identifies the cache TTL (one week)
	 */
	const CACHE_TTL = 604800;

	/**
	 * @var DIWikiPage
	 */
	private $subject;

	/**
	 * @var array
	 */
	private $tableChangeOps = [];

	/**
	 * @var array
	 */
	private $propertyList = [];

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 * @param array $tableChangeOps
	 * @param array $propertyList
	 */
	public function __construct( DIWikiPage $subject, array $tableChangeOps, array $propertyList ) {
		$this->subject = $subject;
		$this->tableChangeOps = $tableChangeOps;
		$this->propertyList = $propertyList;
	}

	/**
	 * @since 3.0
	 *
	 * @return DIWikiPage
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * @since 3.0
	 *
	 * @return TableChangeOps[]
	 */
	public function getTableChangeOps() {
		return $this->tableChangeOps;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $flip
	 *
	 * @return []
	 */
	public function getPropertyList( $flip = false ) {

		if ( $flip === true ) {
			return array_flip( $this->propertyList );
		}

		return $this->propertyList;
	}

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function serialize() {
		return HmacSerializer::serialize( $this );
	}

	/**
	 * @since 3.0
	 *
	 * @param Cache $cache
	 */
	public function save( Cache $cache ) {

		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			$this->subject->getHash()
		);

		// Keep it a week
		$cache->save( $key, HmacSerializer::serialize( $this ), self::CACHE_TTL );
	}

	/**
	 * @since 3.0
	 *
	 * @param Cache $cache
	 * @param DIWikiPage $subject
	 */
	public static function fetch( Cache $cache, DIWikiPage $subject ) {

		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			$subject->getHash()
		);

		if ( ( $diff = $cache->fetch( $key ) ) !== false ) {
			return HmacSerializer::unserialize( $diff );
		}

		return false;
	}

}
