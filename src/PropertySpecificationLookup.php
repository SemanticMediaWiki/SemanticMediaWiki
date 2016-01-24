<?php

namespace SMW;

use RuntimeException;
use Onoi\Cache\Cache;
use Onoi\Cache\NullCache;

/**
 * This class should be accessed via ApplicationFactory::getPropertySpecificationLookup
 * to ensure a singleton instance.
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertySpecificationLookup {

	/**
	 * @var string
	 */
	const VERSION = '0.1';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var string
	 */
	private $languageCode = 'en';

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var string
	 */
	private $cachePrefix = ':smw:pspec:';

	/**
	 * @var integer
	 */
	private $ttl = 604800; // 7 * 24 * 3600

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param Cache|null $cache
	 */
	public function __construct( Store $store, Cache $cache = null ) {
		$this->store = $store;
		$this->cache = $cache;

		if ( $this->cache === null ) {
			$this->cache = new NullCache();
		}
	}

	/**
	 * @since 2.4
	 */
	public function resetCacheFor( DIWikiPage $subject ) {
		$this->cache->delete(
			$this->cachePrefix . md5( $subject->getHash() . self::VERSION )
		);
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
	 * @param string
	 */
	public function getLanguageCode() {
		return $this->languageCode;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $languageCode
	 */
	public function setLanguageCode( $languageCode ) {
		$this->languageCode = Localizer::asBCP47FormattedLanguageCode( $languageCode );
	}

	/**
	 * We try to cache anything to avoid unnecessary store connections or DB
	 * lookups. For cases where a property was changed, the EventDipatcher will
	 * receive a 'property.spec.change' event (emitted as soon as the content of
	 * a property page was altered) with PropertySpecificationLookup::resetCacheFor
	 * being invoked to remove the cache entry for that specific property.
	 *
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 * @param mixed|null $linker
	 *
	 * @return string
	 */
	public function getPropertyDescriptionFor( DIProperty $property, $linker = null ) {

		$description = array();

		// Take the linker into account (Special vs. in page rendering etc.)
		$key = $this->languageCode . ':' . ( $linker === null ? '-' : '+' );

		$hash = $this->cachePrefix . md5(
			$property->getDiWikiPage()->getHash() . self::VERSION
		);

		if ( $this->cache->contains( $hash ) ) {
			$description = $this->cache->fetch( $hash );

			if ( isset( $description[$key] ) ) {
				return trim( $description[$key] );
			}
		}

		$description[$key] = $this->tryToFindLocalPropertyDescription( $property, $linker );

		// If a local property description wasn't available for a predefined property
		// the try to find a system translation
		if ( trim( $description[$key] ) === '' && !$property->isUserDefined() ) {
			$description[$key] = $this->getPredefinedPropertyDescription( $property, $linker );
		}

		$this->cache->save(
			$hash,
			$description,
			$this->ttl
		);

		// Ensure that we return an empty string in case it is just true
		return trim( $description[$key] );
	}

	private function getPredefinedPropertyDescription( $property, $linker ) {

		$description = ' ';
		$msgKey = 'smw-pa-property-predefined' . strtolower( $property->getKey() );

		if ( !wfMessage( $msgKey )->exists() ) {
			return $description;
		}

		$message = wfMessage( $msgKey, $property->getLabel() )->inLanguage(
			$this->languageCode
		);

		return $linker === null ? $message->escaped() : $message->parse();
	}

	private function tryToFindLocalPropertyDescription( $property, $linker ) {

		$description = ' ';

		$dataItems = $this->store->getPropertyValues(
			$property->getDiWikiPage(),
			new DIProperty( '_PDESC' )
		);

		if ( ( $dataValue = $this->findDataValueByLanguage( $dataItems, $this->languageCode ) ) !== null ) {
			$description = $dataValue->getShortWikiText( $linker );
		}

		return $description;
	}

	private function findDataValueByLanguage( $dataItems, $languageCode ) {

		if ( $dataItems === null || $dataItems === array() ) {
			return null;
		}

		foreach ( $dataItems as $dataItem ) {

			$dataValue = DataValueFactory::getInstance()->newDataItemValue(
				$dataItem,
				new DIProperty( '_PDESC' )
			);

			// Here a MonolingualTextValue was retunred therefore the method
			// can be called without validation
			$dv = $dataValue->getTextValueByLanguage( $languageCode );

			if ( $dv !== null ) {
				return $dv;
			}
		}

		return null;
	}

}
