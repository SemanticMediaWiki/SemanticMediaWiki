<?php

namespace SMW;

use SMW\DataValues\AllowsListValue;
use SMW\DataValues\AllowsPatternValue;
use SMW\DataValues\AllowsValue;
use SMW\DataValues\BooleanValue;
use SMW\DataValues\ErrorMsgTextValue;
use SMW\DataValues\ExternalFormatterUriValue;
use SMW\DataValues\ExternalIdentifierValue;
use SMW\DataValues\ImportValue;
use SMW\DataValues\KeywordValue;
use SMW\DataValues\LanguageCodeValue;
use SMW\DataValues\MonolingualTextValue;
use SMW\DataValues\PropertyChainValue;
use SMW\DataValues\PropertyValue;
use SMW\DataValues\ReferenceValue;
use SMW\DataValues\StringValue;
use SMW\DataValues\TelephoneUriValue;
use SMW\DataValues\TemperatureValue;
use SMW\DataValues\TypesValue;
use SMW\DataValues\UniquenessConstraintValue;
use SMWDataItem as DataItem;
use SMWNumberValue as NumberValue;
use SMWQuantityValue as QuantityValue;
use SMWTimeValue as TimeValue;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TypesRegistry {

	/**
	 * @note All IDs must start with an underscore, two underscores indicate a
	 * truly internal (non user-interacted type). All others should also get a
	 * translation in the language files, or they won't be available for users.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public static function getDataTypeList() {
		return [

			// ID => [ Class, DI type, isSubDataType, isBrowsable ]

			// Special import vocabulary type
			ImportValue::TYPE_ID => [ ImportValue::class, DataItem::TYPE_BLOB, false, false ],
			// Property chain
			PropertyChainValue::TYPE_ID => [ PropertyChainValue::class, DataItem::TYPE_BLOB, false, false ],
			// Property type (possibly predefined, not always based on a page)
			PropertyValue::TYPE_ID => [ PropertyValue::class, DataItem::TYPE_PROPERTY, false, false ],
			 // Text type
			StringValue::TYPE_ID => [ StringValue::class, DataItem::TYPE_BLOB, false, false ],
			 // Code type
			StringValue::TYPE_COD_ID => [ StringValue::class, DataItem::TYPE_BLOB, false, false ],
			 // Legacy string ID `_str`
			StringValue::TYPE_LEGACY_ID => [ StringValue::class, DataItem::TYPE_BLOB, false, false ],
			 // Email type
			'_ema' => [ 'SMWURIValue', DataItem::TYPE_URI, false, false ],
			 // URL/URI type
			'_uri' => [ 'SMWURIValue', DataItem::TYPE_URI, false, false ],
			 // Annotation URI type
			'_anu' => [ 'SMWURIValue', DataItem::TYPE_URI, false, false ],
			 // Phone number (URI) type
			'_tel' => [ TelephoneUriValue::class, DataItem::TYPE_URI, false, false ],
			 // Page type
			'_wpg' => [ 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false, true ],
			 // Property page type TODO: make available to user space
			'_wpp' => [ 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false, true ],
			 // Category page type TODO: make available to user space
			'_wpc' => [ 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false, true ],
			 // Form page type for Semantic Forms
			'_wpf' => [ 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false, true ],
			 // Rule page
			'_wps'  => [ 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false, true ],
			 // Number type
			NumberValue::TYPE_ID => [ NumberValue::class, DataItem::TYPE_NUMBER, false, false ],
			 // Temperature type
			TemperatureValue::TYPE_ID => [ TemperatureValue::class, DataItem::TYPE_NUMBER, false, false ],
			 // Time type
			TimeValue::TYPE_ID => [ TimeValue::class, DataItem::TYPE_TIME, false, false ],
			 // Boolean type
			'_boo' => [ BooleanValue::class, DataItem::TYPE_BOOLEAN, false, false ],
			 // Value list type (replacing former nary properties)
			'_rec' => [ 'SMWRecordValue', DataItem::TYPE_WIKIPAGE, true, false ],
			MonolingualTextValue::TYPE_ID => [ MonolingualTextValue::class, DataItem::TYPE_WIKIPAGE, true, false ],
			ReferenceValue::TYPE_ID => [ ReferenceValue::class, DataItem::TYPE_WIKIPAGE, true, false ],
			 // Geographical coordinates
			'_geo' => [ null, DataItem::TYPE_GEO, false, false ],
			 // Geographical polygon
			'_gpo' => [ null, DataItem::TYPE_BLOB, false, false ],
			// External identifier
			ExternalIdentifierValue::TYPE_ID => [ ExternalIdentifierValue::class, DataItem::TYPE_BLOB, false, false ],
			// KeywordValue
			KeywordValue::TYPE_ID => [ KeywordValue::class, DataItem::TYPE_BLOB, false, false ],
			 // Type for numbers with units of measurement
			QuantityValue::TYPE_ID => [ QuantityValue::class, DataItem::TYPE_NUMBER, false, false ],
			// Special types are not avaialble directly for users (and have no local language name):
			// Special type page type
			TypesValue::TYPE_ID => [ TypesValue::class, DataItem::TYPE_URI, false, false ],
			// Special type list for decalring _rec properties
			'__pls' => [ 'SMWPropertyListValue', DataItem::TYPE_BLOB, false, false ],
			// Special concept page type
			'__con' => [ 'SMWConceptValue', DataItem::TYPE_CONCEPT, false, false ],
			// Special string type
			'__sps' => [ StringValue::class, DataItem::TYPE_BLOB, false, false ],
			// Special uri type
			'__spu' => [ 'SMWURIValue', DataItem::TYPE_URI, false, false ],
			// Special subobject type
			'__sob' => [ 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, true, true ],
			// Special subproperty type
			'__sup' => [ 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false, true ],
			// Special subcategory type
			'__suc' => [ 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false, true ],
			// Special Form page type for Semantic Forms
			'__spf' => [ 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false, true ],
			// Special instance of type
			'__sin' => [ 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false, true ],
			// Special redirect type
			'__red' => [ 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false, true ],
			// Special error type
			'__err' => [ 'SMWErrorValue', DataItem::TYPE_ERROR, false, false ],
			// Special error type
			'__errt' => [ ErrorMsgTextValue::class, DataItem::TYPE_BLOB, false, false ],
			// Sort key of a page
			'__key' => [ StringValue::class, DataItem::TYPE_BLOB, false, false ],
			LanguageCodeValue::TYPE_ID => [ LanguageCodeValue::class, DataItem::TYPE_BLOB, false, false ],
			AllowsValue::TYPE_ID => [ AllowsValue::class, DataItem::TYPE_BLOB, false, false ],
			AllowsListValue::TYPE_ID => [ AllowsListValue::class, DataItem::TYPE_BLOB, false, false ],
			AllowsPatternValue::TYPE_ID => [ AllowsPatternValue::class, DataItem::TYPE_BLOB, false, false ],
			'__pvuc' => [ UniquenessConstraintValue::class, DataItem::TYPE_BOOLEAN, false, false ],
			'__pefu' => [ ExternalFormatterUriValue::class, DataItem::TYPE_URI, false, false ]
		];
	}

	/**
	 * @note All ids must start with underscores. The translation for each ID,
	 * if any, is defined in the language files. Properties without translation
	 * cannot be entered by or displayed to users, whatever their "show" value
	 * below.
	 *
	 * @since 3.0
	 *
	 * @param boolean $useCategoryHierarchy
	 *
	 * @return  array
	 */
	public static function getPropertyList( $useCategoryHierarchy = true ) {
		return [

			// ID => [ valueType, isVisible, isAnnotable, isDeclarative ]

			'_TYPE' => [ '__typ', true, true, true ], // "has type"
			'_URI'  => [ '__spu', true, true, false ], // "equivalent URI"
			'_INST' => [ '__sin', false, true, false ], // instance of a category
			'_UNIT' => [ '__sps', true, true, true ], // "displays unit"
			'_IMPO' => [ '__imp', true, true, true ], // "imported from"
			'_CONV' => [ '__sps', true, true, true ], // "corresponds to"
			'_SERV' => [ '__sps', true, true, true ], // "provides service"
			'_PVAL' => [ '__pval', true, true, true ], // "allows value"
			'_REDI' => [ '__red', true, true, false ], // redirects to some page
			'_SUBP' => [ '__sup', true, true, true ], // "subproperty of"
			'_SUBC' => [ '__suc', !$useCategoryHierarchy, true, true ], // "subcategory of"
			'_CONC' => [ '__con', false, true, false ], // associated concept
			'_MDAT' => [ '_dat', false, false, false ], // "modification date"
			'_CDAT' => [ '_dat', false, false, false ], // "creation date"
			'_NEWP' => [ '_boo', false, false, false ], // "is a new page"
			'_EDIP' => [ '_boo', true, true, false ], // "is edit protected"
			'_LEDT' => [ '_wpg', false, false, false ], // "last editor is"
			'_ERRC' => [ '__sob', false, false, false ], // "has error"
			'_ERRT' => [ '__errt', false, false, false ], // "has error text"
			'_ERRP' => [ '_wpp', false, false, false ], // "has improper value for"
			'_LIST' => [ '__pls', true, true, true ], // "has fields"
			'_SKEY' => [ '__key', false, true, false ], // sort key of a page

			// FIXME SF related properties to be removed with 3.0
			'_SF_DF' => [ '__spf', true, true, false ], // Semantic Form's default form property
			'_SF_AF' => [ '__spf', true, true, false ],  // Semantic Form's alternate form property

			'_SOBJ' => [ '__sob', true, false, false ], // "has subobject"
			'_ASK'  => [ '__sob', false, false, false ], // "has query"
			'_ASKST' => [ '_cod', true, false, false ], // "Query string"
			'_ASKFO' => [ '_txt', true, false, false ], // "Query format"
			'_ASKSI' => [ '_num', true, false, false ], // "Query size"
			'_ASKDE' => [ '_num', true, false, false ], // "Query depth"
			'_ASKDU' => [ '_num', true, false, false ], // "Query duration"
			'_ASKSC' => [ '_txt', true, false, false ], // "Query source"
			'_ASKPA' => [ '_cod', true, false, false ], // "Query parameters"
			'_ASKCO' => [ '_num', true, false, false ], // "Query scode"
			'_MEDIA' => [ '_txt', true, false, false ], // "has media type"
			'_MIME' => [ '_txt', true, false, false ], // "has mime type"
			'_PREC' => [ '_num', true, true, true ], // "Display precision of"
			'_LCODE' => [ '__lcode', true, true, false ], // "Language code"
			'_TEXT' => [ '_txt', true, true, false ], // "Text"
			'_PDESC' => [ '_mlt_rec', true, true, true ], // "Property description"
			'_PVAP' => [ '__pvap', true, true, true ], // "Allows pattern"
			'_PVALI' => [ '__pvali', true, true, true ], // "Allows value list"
			'_DTITLE' => [ '_txt', false, true, false ], // "Display title of"
			'_PVUC' => [ '__pvuc', true, true, true ], // Uniqueness constraint
			'_PEID' => [ '_eid', true, true, false ], // External identifier
			'_PEFU' => [ '__pefu', true, true, true ], // External formatter uri
			'_PPLB' => [ '_mlt_rec', true, true, true ], // Preferred property label
			'_CHGPRO' => [ '_cod', true, false, true ], // "Change propagation"
			'_PPGR' => [ '_boo', true, true, true ], // "Property group"

			// Schema
			'_SCHEMA_TYPE' => [ '_txt', true, false, false ], // "Schema type"
			'_SCHEMA_DEF'  => [ '_cod', true, false, false ], // "Schema definition"
			'_SCHEMA_DESC' => [ '_txt', true, false, false ], // "Schema description"
			'_SCHEMA_TAG'  => [ '_txt', true, false, false ], // "Schema tag"
			'_SCHEMA_LINK' => [ '_wps', true, false, false ], // "Schema link"

			//
			'_FORMAT_SCHEMA' => [ '_wps', true, true, false ], // "Formatter schema"

			// File attachment
			'_FILE_ATTCH'  => [ '__sob', false, false, false ], // "File attachment"
			'_CONT_TYPE' => [ '_txt', true, true, false ], // "Content type"
			'_CONT_AUTHOR' => [ '_txt', true, true, false ], // "Content author"
			'_CONT_LEN' => [ '_num', true, true, false ], // "Content length"
			'_CONT_LANG' => [ '__lcode', true, true, false ], // "Content language"
			'_CONT_TITLE' => [ '_txt', true, true, false ], // "Content title"
			'_CONT_DATE' => [ '_dat', true, true, false ], // "Content date",
			'_CONT_KEYW' => [ '_keyw', true, true, false ], // "Content keyword"

			// Translation
			'_TRANS' => [ '__sob', false, false, false ], // "Translation"
			'_TRANS_SOURCE' => [ '_wpg', true, false, false ], // "Translation source"
			'_TRANS_GROUP' => [ '_txt', true, false, false ], // "Translation group"
		];
	}

	/**
	 * @since 3.0
	 *
	 * @return array
	 */
	public static function getTypesByGroup( $group = '' ) {

		if ( $group === 'primitive' ) {
			return [ '_txt' => true , '_boo' => true , '_num' => true, '_dat' => true ];
		}

		if ( $group === 'compound' ) {
			return [ '_ema' => true, '_tel' => true, '_tem' => true ];
		}

		return [];
	}

	/**
	 * Use pre-defined ids for Very Important Properties, avoiding frequent
	 * ID lookups for those.
	 *
	 * @note These constants also occur in the store. Changing them will
	 * require to run setup.php again.
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	public static function getFixedPropertyIdList() {
		return [
			'_TYPE' => 1,
			'_URI'  => 2,
			'_INST' => 4,
			'_UNIT' => 7,
			'_IMPO' => 8,
			'_PPLB' => 9,
			'_PDESC' => 10,
			'_PREC' => 11,
			'_CONV' => 12,
			'_SERV' => 13,
			'_PVAL' => 14,
			'_REDI' => 15,
			'_DTITLE' => 16,
			'_SUBP' => 17,
			'_SUBC' => 18,
			'_CONC' => 19,
			'_ERRP' => 22,
	// 		'_1' => 23, // properties for encoding (short) lists
	// 		'_2' => 24,
	// 		'_3' => 25,
	// 		'_4' => 26,
	// 		'_5' => 27,
	// 		'_SOBJ' => 27
			'_LIST' => 28,
			'_MDAT' => 29,
			'_CDAT' => 30,
			'_NEWP' => 31,
			'_LEDT' => 32,
			// properties related to query management
			'_ASK'   => 33,
			'_ASKST' => 34,
			'_ASKFO' => 35,
			'_ASKSI' => 36,
			'_ASKDE' => 37,
			'_ASKPA' => 38,
			'_ASKSC' => 39,
			'_LCODE' => 40,
			'_TEXT'  => 41,
		];
	}

}
