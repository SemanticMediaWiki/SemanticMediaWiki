<?php

namespace SMW;

use RuntimeException;
use Onoi\BlobStore\BlobStore;

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
	const VERSION = '0.2';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var string
	 */
	private $languageCode = 'en';

	/**
	 * @var BlobStore
	 */
	private $blobStore;

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param BlobStore $blobStore
	 */
	public function __construct( Store $store, BlobStore $blobStore ) {
		$this->store = $store;
		$this->blobStore = $blobStore;
	}

	/**
	 * @since 2.4
	 */
	public function resetCacheFor( DIWikiPage $subject ) {
		$this->blobStore->delete( md5( $subject->getHash() . self::VERSION ) );
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
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return integer|false
	 */
	public function getDisplayPrecisionFor( DIProperty $property ) {

		$displayPrecision = false;
		$key = 'prec';

		$hash = md5(
			$property->getDiWikiPage()->getHash() . self::VERSION
		);

		$container = $this->blobStore->read( $hash );

		if ( $container->has( $key ) ) {
			return $container->get( $key );
		}

		$dataItems = $this->store->getPropertyValues(
			$property->getDiWikiPage(),
			new DIProperty( '_PREC' )
		);

		if ( $dataItems !== false && $dataItems !== array() ) {
			$dataItem = end( $dataItems );
			$displayPrecision = abs( (int)$dataItem->getNumber() );
		}

		$container->set( $key, $displayPrecision );

		$this->blobStore->save(
			$container
		);

		return $displayPrecision;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return array
	 */
	public function getDisplayUnitsFor( DIProperty $property ) {

		$units = array();
		$key = 'unit';

		$hash = md5(
			$property->getDiWikiPage()->getHash() . self::VERSION
		);

		$container = $this->blobStore->read( $hash );

		if ( $container->has( $key ) ) {
			return $container->get( $key );
		}

		$dataItems = $this->store->getPropertyValues(
			$property->getDiWikiPage(),
			new DIProperty( '_UNIT' )
		);

		if ( $dataItems !== false && $dataItems !== array() ) {
			foreach ( $dataItems as $dataItem ) {
				$units = array_merge( $units, preg_split( '/\s*,\s*/u', $dataItem->getString() ) );
			}
		}

		$container->set( $key, $units );

		$this->blobStore->save(
			$container
		);

		return $units;
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

		$propertyDescription = '';

		// Take the linker into account (Special vs. in page rendering etc.)
		$key = 'pdesc:' . $this->languageCode . ':' . ( $linker === null ? '0' : '1' );

		$hash = md5(
			$property->getDiWikiPage()->getHash() . self::VERSION
		);

		$container = $this->blobStore->read( $hash );

		if ( $container->has( $key ) ) {
			return $container->get( $key );
		}

		$propertyDescription = $this->tryToFindLocalPropertyDescription( $property, $linker );

		// If a local property description wasn't available for a predefined property
		// the try to find a system translation
		if ( trim( $propertyDescription ) === '' && !$property->isUserDefined() ) {
			$propertyDescription = $this->getPredefinedPropertyDescription( $property, $linker );
		}

		$container->set( $key, $propertyDescription );

		$this->blobStore->save(
			$container
		);

		return $propertyDescription;
	}

	private function getPredefinedPropertyDescription( $property, $linker ) {

		$description = '';
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

		$description = '';

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
