<?php

namespace SMW\Property;

use RuntimeException;
use SMW\DataItems\Boolean;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;
use SMW\EntityCache;
use SMW\Localizer\Message;
use SMW\PropertyRegistry;
use SMW\Services\Exception\ServiceNotFoundException;
use SMW\Store;

/**
 * This class should be accessed via ApplicationFactory::getPropertySpecificationLookup
 * to ensure a singleton instance.
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 * @author thomas-topway-it for KM-A
 */
class SpecificationLookup {

	/**
	 * Identifies types used as part of the generate key to distinguish between
	 * instances that would create the same entity key
	 */
	const CACHE_NS_KEY_SPECIFICATIONLOOKUP = ':propertyspecificationlookup';
	const CACHE_NS_KEY_SPECIFICATIONLOOKUP_PREFERREDLABEL = ':propertyspecificationlookup:preferredlabel';
	const CACHE_NS_KEY_SPECIFICATIONLOOKUP_DESCRIPTION = ':propertyspecificationlookup:description';

	/**
	 * @var Cache
	 */
	private $entityCache;

	/**
	 * @var string
	 */
	private $languageCode = 'en';

	/**
	 * @var bool
	 */
	private $skipCache = false;

	/**
	 * @since 2.4
	 */
	public function __construct(
		private readonly Store $store,
		EntityCache $entityCache,
	) {
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 3.1
	 *
	 * @param bool $skipCache
	 */
	public function skipCache( $skipCache = true ): void {
		$this->skipCache = $skipCache;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $languageCode
	 */
	public function setLanguageCode( $languageCode ): void {
		$this->languageCode = $languageCode;
	}

	/**
	 * @since 2.4
	 *
	 * @param WikiPage $subject
	 */
	public function invalidateCache( WikiPage $subject ): void {
		$this->entityCache->invalidate( $subject );

		$this->entityCache->delete(
			$this->entityCache->makeCacheKey( self::CACHE_NS_KEY_SPECIFICATIONLOOKUP, $subject )
		);

		$this->entityCache->delete(
			$this->entityCache->makeCacheKey( self::CACHE_NS_KEY_SPECIFICATIONLOOKUP_PREFERREDLABEL, $subject )
		);

		$this->entityCache->delete(
			$this->entityCache->makeCacheKey( self::CACHE_NS_KEY_SPECIFICATIONLOOKUP_DESCRIPTION, $subject )
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param Property|WikiPage $source
	 * @param Property $target
	 *
	 * @return bool|array|DataItem[]
	 */
	public function getSpecification( $source, Property $target ) {
		if ( $source instanceof Property ) {
			$subject = $source->getCanonicalDiWikiPage();
		} elseif ( $source instanceof WikiPage ) {
			$subject = $source;
		} else {
			throw new RuntimeException( "Invalid request instance type" );
		}

		$key = $this->entityCache->makeCacheKey( self::CACHE_NS_KEY_SPECIFICATIONLOOKUP, $subject );
		$sub_key = $target->getKey();

		if (
			!$this->skipCache &&
			( $specification = $this->entityCache->fetchSub( $key, $sub_key ) ) !== false ) {
			return $specification;
		}

		$dataItems = $this->store->getPropertyValues(
			$subject,
			$target
		);

		if ( !is_array( $dataItems ) ) {
			$dataItems = [];
		}

		$this->entityCache->saveSub( $key, $sub_key, $dataItems, EntityCache::TTL_WEEK );
		$this->entityCache->associate( $subject, $key );

		return $dataItems;
	}

	/**
	 * @since 2.5
	 *
	 * @param Property $property
	 *
	 * @return false|DataItem
	 */
	public function getFieldListBy( Property $property ) {
		$fieldList = false;
		$dataItems = $this->getSpecification( $property, new Property( '_LIST' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {
			$fieldList = end( $dataItems );
		}

		return $fieldList;
	}

	/**
	 * @since 2.5
	 *
	 * @param Property $property
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function getPreferredPropertyLabelByLanguageCode( Property $property, $languageCode = '' ) {
		$subject = $property->getCanonicalDiWikiPage();
		$key = $this->entityCache->makeCacheKey( self::CACHE_NS_KEY_SPECIFICATIONLOOKUP_PREFERREDLABEL, $subject );

		if ( ( $text = $this->entityCache->fetchSub( $key, $languageCode ) ) !== false ) {
			return $text;
		}

		$text = $this->getTextByLanguageCode(
			$subject,
			new Property( '_PPLB' ),
			$languageCode
		);

		$this->entityCache->saveSub( $key, $languageCode, $text );
		$this->entityCache->associate( $subject, $key );

		return $text;
	}

	/**
	 * @since 2.4
	 *
	 * @param Property $property
	 *
	 * @return bool
	 */
	public function hasUniquenessConstraint( Property $property ) {
		$hasUniquenessConstraint = false;
		$dataItems = $this->getSpecification( $property, new Property( '_PVUC' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {
			$hasUniquenessConstraint = end( $dataItems )->getBoolean();
		}

		return $hasUniquenessConstraint;
	}

	/**
	 * @since 3.0
	 *
	 * @param Property $property
	 *
	 * @return DataItem|null
	 */
	public function getPropertyGroup( Property $property ) {
		$dataItem = null;
		$dataItems = $this->getSpecification( $property, new Property( '_INST' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {

			foreach ( $dataItems as $dataItem ) {
				$pv = $this->store->getPropertyValues(
					$dataItem,
					new Property( '_PPGR' )
				);

				$di = end( $pv );

				if ( $di instanceof Boolean && $di->getBoolean() ) {
					return $dataItem;
				}
			}
		}

		return null;
	}

	/**
	 * @since 2.5
	 *
	 * @param Property $property
	 *
	 * @return DataItem|null
	 */
	public function getExternalFormatterUri( Property $property ) {
		$dataItem = null;
		$dataItems = $this->getSpecification( $property, new Property( '_PEFU' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {
			$dataItem = end( $dataItems );
		}

		return $dataItem;
	}

	/**
	 * @since 2.4
	 *
	 * @param Property $property
	 *
	 * @return string
	 */
	public function getAllowedPatternBy( Property $property ) {
		$allowsPattern = '';
		$dataItems = $this->getSpecification( $property, new Property( '_PVAP' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {
			$allowsPattern = end( $dataItems )->getString();
		}

		return $allowsPattern;
	}

	/**
	 * @since 2.4
	 *
	 * @param Property $property
	 *
	 * @return array
	 */
	public function getAllowedValues( Property $property ): array {
		$allowsValues = [];
		$dataItems = $this->getSpecification( $property, new Property( '_PVAL' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {
			$allowsValues = $dataItems;
		}

		return $allowsValues;
	}

	/**
	 * @since 2.5
	 *
	 * @param Property $property
	 *
	 * @return array
	 */
	public function getAllowedListValues( Property $property ): array {
		$allowsListValue = [];
		$dataItems = $this->getSpecification( $property, new Property( '_PVALI' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {
			$allowsListValue = $dataItems;
		}

		return $allowsListValue;
	}

	/**
	 * @since 2.4
	 *
	 * @param Property $property
	 *
	 * @return int|false
	 */
	public function getDisplayPrecision( Property $property ): int|false {
		$displayPrecision = false;
		$dataItems = $this->getSpecification( $property, new Property( '_PREC' ) );

		if ( $dataItems !== false && $dataItems !== [] ) {
			$dataItem = end( $dataItems );
			$displayPrecision = abs( (int)$dataItem->getNumber() );
		}

		return $displayPrecision;
	}

	/**
	 * @since 2.4
	 *
	 * @param Property $property
	 *
	 * @return array
	 */
	public function getDisplayUnits( Property $property ): array {
		$units = [];
		$dataItems = $this->getSpecification( $property, new Property( '_UNIT' ) );

		if ( $dataItems !== false && $dataItems !== [] ) {
			foreach ( $dataItems as $dataItem ) {
				$units = array_merge( $units, preg_split( '/\s*,\s*/u', $dataItem->getString() ) );
			}
		}

		return $units;
	}

	/**
	 * @since 2.4
	 *
	 * @param Property $property
	 * @param string $languageCode
	 * @param mixed|null $linker
	 *
	 * @return string
	 */
	public function getPropertyDescriptionByLanguageCode( Property $property, string $languageCode = '', $linker = null ) {
		$subject = $property->getCanonicalDiWikiPage();
		$key = $this->entityCache->makeCacheKey( self::CACHE_NS_KEY_SPECIFICATIONLOOKUP_DESCRIPTION, $subject );

		$sub_key = $languageCode . ':' . ( $linker === null ? '0' : '1' );

		if ( ( $text = $this->entityCache->fetchSub( $key, $sub_key ) ) !== false ) {
			return $text;
		}

		$text = $this->getTextByLanguageCode(
			$subject,
			new Property( '_PDESC' ),
			$languageCode
		);

		// If a local property description wasn't available for a predefined property
		// the try to find a system translation
		if ( trim( $text ?? '' ) === '' && !$property->isUserDefined() ) {
			$text = $this->getPredefinedPropertyDescription( $property, $languageCode, $linker );
		}

		$text = trim( $text ?? '' );

		$this->entityCache->saveSub( $key, $sub_key, $text );
		$this->entityCache->associate( $subject, $key );

		return $text;
	}

	private function getPredefinedPropertyDescription( Property $property, string $languageCode, $linker ): string {
		$description = '';
		$key = $property->getKey();

		if ( ( $msgKey = PropertyRegistry::getInstance()->findPropertyDescriptionMsgKeyById( $key ) ) === '' ) {
			$msgKey = 'smw-property-predefined' . str_replace( '_', '-', strtolower( $key ) );
		}

		if ( !Message::exists( $msgKey ) ) {
			return $description;
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$property
		);

		$label = $dataValue->getFormattedLabel();

		$message = Message::get(
			[ $msgKey, $label ],
			$linker === null ? Message::ESCAPED : Message::PARSE,
			$languageCode
		);

		return $message;
	}

	/**
	 * @param WikiPage $subject
	 * @param Property $property
	 * @param string $languageCode
	 *
	 * @return string
	 */
	private function getTextByLanguageCode( ?WikiPage $subject, Property $property, $languageCode ) {
		// @TODO move in the constructor ?
		try {
			$monolingualTextLookup = $this->store->service( 'MonolingualTextLookup' );
		} catch ( ServiceNotFoundException $e ) {
			return '';
		}

		if ( $monolingualTextLookup === null ) {
			return '';
		}

		$dataValue = $monolingualTextLookup->newDataValue(
			$subject,
			$property,
			$languageCode
		);

		if ( $dataValue === null ) {
			$languageFalldownAndInverse = new LanguageFalldownAndInverse( $monolingualTextLookup, $subject, $property, $languageCode );

			// @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5342
			[ $dataValue, $languageCode ] = $languageFalldownAndInverse->tryout();

			if ( $dataValue === null ) {
				return '';
			}
		}

		$dv = $dataValue->getTextValueByLanguageCode(
			$languageCode
		);

		return $dv->getShortWikiText();
	}

}
