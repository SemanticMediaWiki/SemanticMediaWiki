<?php
/**
 * @license GNU GPL v2+
 *
 * @author thomas-topway-it for KM-A
 * @see https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5342
 */
namespace SMW\Property;

use MediaWiki\MediaWikiServices;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SQLStore\Lookup\MonolingualTextLookup;

class LanguageFalldownAndInverse {

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
	 * @var MonolingualTextLookup
	 */
	private $monolingualTextLookup;

	/**
	 * @var DIWikiPage
	 */
	private $subject;

	/**
	 * @var DIProperty
	 */
	private $property;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param MonolingualTextLookup $monolingualTextLookup
	 * @param DIWikiPage $subject
	 * @param DIProperty $property
	 * @param string $languageCode
	 */
	public function __construct( $monolingualTextLookup, $subject, $property, $languageCode ) {
		$this->monolingualTextLookup = $monolingualTextLookup;
		$this->subject = $subject;
		$this->property = $property;
		$this->languageCode = $languageCode;
	}

	/**
	 * @return array
	 */
	public function tryout() {
		$dataValue = $this->falldownInverse();
		if ( $dataValue ) {
			return [ $dataValue, $this->languageCode ];
		}
		$dataValue = $this->falldown();
		return [ $dataValue, $this->languageCode ];
	}

	/**
	 * @param string $languageCode
	 * @return DataValue|null
	 */
	private function getDataValue( $languageCode ) {
		return $this->monolingualTextLookup->newDataValue(
			$this->subject,
			$this->property,
			$languageCode
		);
	}

	/**
	 * @return DataValue|null
	 */
	public function falldownInverse() {
		if ( array_key_exists( $this->languageCode, $this->languagesFallbackInverse ) ) {
			foreach ( $this->languagesFallbackInverse[$this->languageCode] as $value ) {
				$dataValue = $this->getDataValue( $value );
				if ( $dataValue ) {
					$this->languageCode = $value;
					return $dataValue;
				}
			}
		}

		return null;
	}

	/**
	 * @return DataValue|null
	 */
	public function falldown() {
		$languageFalldown = MediaWikiServices::getInstance()->getLanguageFallback()->getFirst( $this->languageCode );

		// when $languageCode is 'en' $languageFalldown is null
		if ( $languageFalldown === null ) {
			return null;
		}

		$dataValue = $this->getDataValue( $languageFalldown );
		if ( !$dataValue ) {
			return null;
		}

		$this->languageCode = $languageFalldown;
		return $dataValue;
	}

}
