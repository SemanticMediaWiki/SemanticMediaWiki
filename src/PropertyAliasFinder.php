<?php

namespace SMW;

use Onoi\Cache\Cache;

/**
 * @license GNU GPL v2
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyAliasFinder {

	/**
	 * Identifies the cache namespace
	 */
	const CACHE_NAMESPACE = 'smw:property:alias';

	/**
	 * Identifies the cache TTL (one week)
	 */
	const CACHE_TTL = 604800;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * Array with entries "property alias" => "property id"
	 *
	 * @var string[]
	 */
	private $propertyAliases = [];

	/**
	 * @var string[]
	 */
	private $propertyAliasesByMsgKey = [];

	/**
	 * @var string[]
	 */
	private $canonicalPropertyAliases = [];

	/**
	 * @since 2.4
	 *
	 * @param Cache $cache
	 * @param array $propertyAliases
	 * @param array $canonicalPropertyAliases
	 */
	public function __construct( Cache $cache, array $propertyAliases = [], array $canonicalPropertyAliases = [] ) {
		$this->cache = $cache;
		$this->canonicalPropertyAliases = $canonicalPropertyAliases;

		foreach ( $propertyAliases as $alias => $id ) {
			$this->registerAliasByFixedLabel( $id, $alias );
		}
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getKnownPropertyAliases() {
		return $this->propertyAliases;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getKnownPropertyAliasesWithMsgKey() {
		return $this->propertyAliasesByMsgKey;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $languageCode
	 *
	 * @return array
	 */
	public function getKnownPropertyAliasesByLanguageCode( $languageCode = 'en' ) {

		$key = smwfCacheKey(
			self::CACHE_NAMESPACE,
			[
				$languageCode,
				$this->propertyAliasesByMsgKey
			]
		);

		if ( ( $propertyAliases = $this->cache->fetch( $key ) ) !== false ) {
			return $propertyAliases;
		}

		$propertyAliases = [];

		foreach ( $this->propertyAliasesByMsgKey as $msgKey => $id ) {
			$propertyAliases[Message::get( $msgKey, Message::TEXT, $languageCode )] = $id;
		}

		$this->cache->save( $key, $propertyAliases, self::CACHE_TTL );

		return $propertyAliases;
	}

	/**
	 * Add a new alias label to an existing property ID. Note that every ID
	 * should have a primary label.
	 *
	 * @param string $id string
	 * @param string $label
	 */
	public function registerAliasByFixedLabel( $id, $label ) {

		// Prevent an extension to register an already known
		// label
		if ( isset( $this->canonicalPropertyAliases[$label] ) && $this->canonicalPropertyAliases[$label] !== $id ) {
			return;
		}

		// Indicates an untranslated MW message key
		if ( $label !== '' && $label{0} === '<' ) {
			return null;
		}

		$this->propertyAliases[$label] = $id;
	}

	/**
	 * Register an alias using a message key to allow fetching localized
	 * labels dynamically.
	 *
	 * @since 2.4
	 *
	 * @param string $id
	 * @param string $msgKey
	 */
	public function registerAliasByMsgKey( $id, $msgKey ) {
		$this->propertyAliasesByMsgKey[$msgKey] = $id;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $id
	 *
	 * @return string|boolean
	 */
	public function findCanonicalPropertyAliasById( $id ) {
		return array_search( $id, $this->canonicalPropertyAliases );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $id
	 *
	 * @return string|boolean
	 */
	public function findPropertyAliasById( $id ) {
		return array_search( $id, $this->propertyAliases );
	}

	/**
	 * Find and return the ID for the pre-defined property of the given
	 * local label. If the label does not belong to a pre-defined property,
	 * return false.
	 *
	 * @param string $alias
	 *
	 * @return string|boolean
	 */
	public function findPropertyIdByAlias( $alias ) {

		if ( isset( $this->propertyAliases[$alias] ) ) {
			return $this->propertyAliases[$alias];
		} elseif ( isset( $this->canonicalPropertyAliases[$alias] ) ) {
			return $this->canonicalPropertyAliases[$alias];
		}

		return false;
	}

}
