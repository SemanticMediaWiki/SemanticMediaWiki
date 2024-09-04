<?php

namespace SMW\Property;

use MediaWiki\MediaWikiServices;
use RuntimeException;
use SMW\Query\DescriptionFactory;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWQuery as Query;
use SMW\Store;
use SMW\EntityCache;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Message;
use SMW\DataValueFactory;
use SMW\PropertyRegistry;


/**
 * This class should be accessed via ApplicationFactory::getPropertySpecificationLookup
 * to ensure a singleton instance.
 *
 * @license GNU GPL v2+
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
	 * @var Store
	 */
	private $store;

	/**
	 * @var Cache
	 */
	private $entityCache;

	/**
	 * @var string
	 */
	private $languageCode = 'en';

	/**
	 * @var boolean
	 */
	private $skipCache = false;

	/**
	 * @var array
	 */
	private $languagesFallbackInverse = [
		'ru' => [
			'ab',
			'av',
			'ba',
			'ce',
			'crh-cyrl',
			'cv',
			'inh',
			'koi',
			'krc',
			'kv',
			'lbe',
			'lez',
			'mhr',
			'mrj',
			'myv',
			'os',
			'rue',
			'sah',
			'tt',
			'tt-cyrl',
			'udm',
			'uk',
			'xal'
		],
		'id' => [
			'ace',
			'bjn',
			'bug',
			'jv',
			'map-bms',
			'min',
			'su'
		],
		'sq' => [
			'aln'
		],
		'gsw' => [
			'als'
		],
		'de' => [
			'als',
			'bar',
			'de-at',
			'de-ch',
			'de-formal',
			'dsb',
			'frr',
			'gsw',
			'hsb',
			'ksh',
			'lb',
			'nds',
			'pdc',
			'pdt',
			'pfl',
			'sli',
			'stq',
			'vmf'
		],
		'es' => [
			'an',
			'arn',
			'ay',
			'cbk-zam',
			'gn',
			'lad',
			'nah',
			'qu',
			'qug'
		],
		'hi' => [
			'anp',
			'mai',
			'sa'
		],
		'ar' => [
			'arz'
		],
		'sgs' => [
			'bat-smg'
		],
		'lt' => [
			'bat-smg',
			'sgs'
		],
		'fa' => [
			'bcc',
			'bqi',
			'glk',
			'mzn'
		],
		'be-tarask' => [
			'be-x-old'
		],
		'bho' => [
			'bh'
		],
		'fr' => [
			'bm',
			'ff',
			'frc',
			'frp',
			'ht',
			'ln',
			'mg',
			'pcd',
			'sg',
			'ty',
			'wa',
			'wo'
		],
		'bn' => [
			'bpy'
		],
		'crh-latn' => [
			'crh'
		],
		'pl' => [
			'csb',
			'szl'
		],
		'ms' => [
			'dtp'
		],
		'it' => [
			'egl',
			'eml',
			'fur',
			'lij',
			'lmo',
			'nap',
			'pms',
			'rgn',
			'scn',
			'vec'
		],
		'fi' => [
			'fit',
			'vot'
		],
		'vro' => [
			'fiu-vro'
		],
		'et' => [
			'fiu-vro',
			'liv',
			'vep',
			'vro'
		],
		'tr' => [
			'gag',
			'kiu',
			'lzz'
		],
		'gan-hant' => [
			'gan'
		],
		'zh-hant' => [
			'gan',
			'gan-hant',
			'zh-hk',
			'zh-mo',
			'zh-tw'
		],
		'zh-hans' => [
			'gan',
			'gan-hans',
			'gan-hant',
			'ii',
			'wuu',
			'za',
			'zh',
			'zh-cn',
			'zh-hant',
			'zh-hk',
			'zh-mo',
			'zh-my',
			'zh-sg',
			'zh-tw'
		],
		'pt' => [
			'gl',
			'mwl',
			'pt-br'
		],
		'hif-latn' => [
			'hif'
		],
		'zh-cn' => [
			'ii'
		],
		'ike-cans' => [
			'iu'
		],
		'da' => [
			'jut',
			'kl'
		],
		'kk-latn' => [
			'kaa',
			'kk-tr'
		],
		'kk-cyrl' => [
			'kaa',
			'kk',
			'kk-arab',
			'kk-latn',
			'kk-cn',
			'kk-kz',
			'kk-tr'
		],
		'kbd-cyrl' => [
			'kbd'
		],
		'ur' => [
			'khw'
		],
		'kk-arab' => [
			'kk-cn'
		],
		'ko' => [
			'ko-kp'
		],
		'ks-arab' => [
			'ks'
		],
		'ku-latn' => [
			'ku'
		],
		'ckb' => [
			'ku-arab'
		],
		'nl' => [
			'li',
			'nds-nl',
			'nl-informal',
			'srn',
			'vls',
			'zea'
		],
		'lv' => [
			'ltg'
		],
		'jv' => [
			'map-bms'
		],
		'ro' => [
			'mo',
			'rmy',
			'ruq',
			'ruq-latn'
		],
		'nb' => [
			'no'
		],
		'pt-br' => [
			'pt'
		],
		'qu' => [
			'qug'
		],
		'rup' => [
			'roa-rup'
		],
		'uk' => [
			'rue'
		],
		'ruq-latn' => [
			'ruq'
		],
		'mk' => [
			'ruq-cyrl'
		],
		'sr-ec' => [
			'sr'
		],
		'sr-cyrl' => [
			'sr'
		],
		'kn' => [
			'tcy'
		],
		'tg-cyrl' => [
			'tg'
		],
		'tt-cyrl' => [
			'tt'
		],
		'ug-arab' => [
			'ug'
		],
		'ka' => [
			'xmf'
		],
		'he' => [
			'yi'
		],
		'lzh' => [
			'zh-classical'
		],
		'nan' => [
			'zh-min-nan'
		],
		'zh-hk' => [
			'zh-mo'
		],
		'zh-sg' => [
			'zh-my'
		],
		'yue' => [
			'zh-yue'
		]
	];

	/**
	 * @since 2.4
	 *
	 * @param Store $store
	 * @param EntityCache $entityCache
	 */
	public function __construct( Store $store, EntityCache $entityCache ) {
		$this->store = $store;
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $skipCache
	 */
	public function skipCache( $skipCache = true ) {
		$this->skipCache = $skipCache;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $languageCode
	 */
	public function setLanguageCode( $languageCode ) {
		$this->languageCode = $languageCode;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIWikiPage $subject
	 */
	public function invalidateCache( DIWikiPage $subject ) {
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
	 * @param DIProperty|DIWikiPage $source
	 * @param DIProperty $target
	 *
	 * @return []|DataItem[]
	 */
	public function getSpecification( $source, DIProperty $target ) {
		if ( $source instanceof DIProperty ) {
			$subject = $source->getCanonicalDiWikiPage();
		} elseif ( $source instanceof DIWikiPage ) {
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
	 * @param DIProperty $property
	 *
	 * @return false|DataItem
	 */
	public function getFieldListBy( DIProperty $property ) {
		$fieldList = false;
		$dataItems = $this->getSpecification( $property, new DIProperty( '_LIST' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {
			$fieldList = end( $dataItems );
		}

		return $fieldList;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 * @param string $languageCode
	 *
	 * @return string
	 */
	public function getPreferredPropertyLabelByLanguageCode( DIProperty $property, $languageCode = '' ) {
		$subject = $property->getCanonicalDiWikiPage();
		$key = $this->entityCache->makeCacheKey( self::CACHE_NS_KEY_SPECIFICATIONLOOKUP_PREFERREDLABEL, $subject );

		if ( ( $text = $this->entityCache->fetchSub( $key, $languageCode ) ) !== false ) {
			return $text;
		}

		$text = $this->getTextByLanguageCode(
			$subject,
			new DIProperty( '_PPLB' ),
			$languageCode
		);

		$this->entityCache->saveSub( $key, $languageCode, $text );
		$this->entityCache->associate( $subject, $key );

		return $text;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return boolean
	 */
	public function hasUniquenessConstraint( DIProperty $property ) {
		$hasUniquenessConstraint = false;
		$dataItems = $this->getSpecification( $property, new DIProperty( '_PVUC' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {
			$hasUniquenessConstraint = end( $dataItems )->getBoolean();
		}

		return $hasUniquenessConstraint;
	}

	/**
	 * @since 3.0
	 *
	 * @param DIProperty $property
	 *
	 * @return DataItem|null
	 */
	public function getPropertyGroup( DIProperty $property ) {
		$dataItem = null;
		$dataItems = $this->getSpecification( $property, new DIProperty( '_INST' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {

			foreach ( $dataItems as $dataItem ) {
				$pv = $this->store->getPropertyValues(
					$dataItem,
					new DIProperty( '_PPGR' )
				);

				$di = end( $pv );

				if ( $di instanceof DIBoolean && $di->getBoolean() ) {
					return $dataItem;
				}
			}
		}

		return null;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 *
	 * @return DataItem|null
	 */
	public function getExternalFormatterUri( DIProperty $property ) {
		$dataItem = null;
		$dataItems = $this->getSpecification( $property, new DIProperty( '_PEFU' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {
			$dataItem = end( $dataItems );
		}

		return $dataItem;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return string
	 */
	public function getAllowedPatternBy( DIProperty $property ) {
		$allowsPattern = '';
		$dataItems = $this->getSpecification( $property, new DIProperty( '_PVAP' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {
			$allowsPattern = end( $dataItems )->getString();
		}

		return $allowsPattern;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return array
	 */
	public function getAllowedValues( DIProperty $property ) {
		$allowsValues = [];
		$dataItems = $this->getSpecification( $property, new DIProperty( '_PVAL' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {
			$allowsValues = $dataItems;
		}

		return $allowsValues;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 *
	 * @return array
	 */
	public function getAllowedListValues( DIProperty $property ) {
		$allowsListValue = [];
		$dataItems = $this->getSpecification( $property, new DIProperty( '_PVALI' ) );

		if ( is_array( $dataItems ) && $dataItems !== [] ) {
			$allowsListValue = $dataItems;
		}

		return $allowsListValue;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return integer|false
	 */
	public function getDisplayPrecision( DIProperty $property ) {
		$displayPrecision = false;
		$dataItems = $this->getSpecification( $property, new DIProperty( '_PREC' ) );

		if ( $dataItems !== false && $dataItems !== [] ) {
			$dataItem = end( $dataItems );
			$displayPrecision = abs( (int)$dataItem->getNumber() );
		}

		return $displayPrecision;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return array
	 */
	public function getDisplayUnits( DIProperty $property ) {
		$units = [];
		$dataItems = $this->getSpecification( $property, new DIProperty( '_UNIT' ) );

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
	 * @param DIProperty $property
	 * @param string $languageCode
	 * @param mixed|null $linker
	 *
	 * @return string
	 */
	public function getPropertyDescriptionByLanguageCode( DIProperty $property, $languageCode = '', $linker = null ) {
		$subject = $property->getCanonicalDiWikiPage();
		$key = $this->entityCache->makeCacheKey( self::CACHE_NS_KEY_SPECIFICATIONLOOKUP_DESCRIPTION, $subject );

		$sub_key = $languageCode . ':' . ( $linker === null ? '0' : '1' );

		if ( ( $text = $this->entityCache->fetchSub( $key, $sub_key ) ) !== false ) {
			return $text;
		}

		$text = $this->getTextByLanguageCode(
			$subject,
			new DIProperty( '_PDESC' ),
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

	private function getPredefinedPropertyDescription( $property, $languageCode, $linker ) {
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
	 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5342
	 *
	 * @param MonolingualTextLookup $monolingualTextLookup
	 * @param DIWikiPage $subject
	 * @param DIProperty $property
	 * @param string &$languageCode
	 * @return DataValue|null
	 */
	private function tryOutFalldownAndInverse( $monolingualTextLookup, $subject, $property, &$languageCode ) {
		$getDataValue = static function ( $value ) use ( $monolingualTextLookup, $subject, $property, &$languageCode ) {
			 $dataValue = $monolingualTextLookup->newDataValue(
				$subject,
				$property,
				$value
			);
			if ( $dataValue ) {
				$languageCode = $value;
			}
			return $dataValue;
		};

		if ( array_key_exists( $languageCode, $this->languagesFallbackInverse ) ) {
			foreach ( $this->languagesFallbackInverse[$languageCode] as $value ) {
				$dataValue = $getDataValue( $value );
				if ( $dataValue ) {
					return $dataValue;
				}
			}
		}

		$languageFalldown = MediaWikiServices::getInstance()->getLanguageFallback()->getFirst( $languageCode );

		// when $languageCode is 'en' $languageFalldown is null
		if ( $languageFalldown === null ) {
			return null;
		}
		return $getDataValue( $languageFalldown );
	}

	private function getTextByLanguageCode( $subject, $property, $languageCode ) {
		try {
			$monolingualTextLookup = $this->store->service( 'MonolingualTextLookup' );
		} catch( \SMW\Services\Exception\ServiceNotFoundException $e ) {
			return '';
		}

		if ( $monolingualTextLookup === null ) {
			return '';
		}

		$monolingualTextLookup->setCaller( __METHOD__ );

		$dataValue = $monolingualTextLookup->newDataValue(
			$subject,
			$property,
			$languageCode
		);

		if ( $dataValue === null ) {
			// @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5342
			$dataValue = $this->tryOutFalldownAndInverse( $monolingualTextLookup, $subject, $property, $languageCode );

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
