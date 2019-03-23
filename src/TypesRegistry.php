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
use SMWExporter as Exporter;

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
			'_ERR_TYPE' => [ '_txt', false, false, false ], // "Error type"
			'_ERRP' => [ '_wpp', false, false, false ], // "has improper value for"
			'_LIST' => [ '__pls', true, true, true ], // "has fields"
			'_SKEY' => [ '__key', false, true, false ], // sort key of a page

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
			'_CONSTRAINT_SCHEMA' => [ '_wps', true, true, true ], // "Constraint schema"

			// File attachment
			'_ATTCH_LINK'  => [ '_wpg', true, false, false ], // "Attachment link"
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
	public static function getTypesByGroup( $key = '' ) {

		$groups = [
			'primitive' => [
				'_txt', '_boo', '_num', '_dat'
			],
			'contextual' => [
				'_anu', '_cod', '_eid', '_geo', '_keyw', '_wpg', '_qty', '_uri'
			],
			'container' => [
				'_rec', '_mlt_rec', '_ref_rec'
			],
			'compound' => [
				'_ema', '_tel', '_tem'
			]
		];

		if ( isset( $groups[$key] ) ) {
			return $groups[$key];
		}

		return $groups;
	}

	/**
	 * @private
	 *
	 * @note These constants also occur in the store. Changing them will
	 * require to run `setup.php` again.
	 *
	 * The highest assignable ID is defined by:
	 * ( SQLStore::FIXED_PROPERTY_ID_UPPERBOUND - 1)
	 *
	 * - `id` refers to the fixed ID in the entity table assigned to a property
	 *
	 * - `default_fixed` refers to properties that by default are fixed and require
	 * their own table space
	 *
	 * - `custom_fixed` refers to properties that are not enabled by default but
	 * when the user enables them require their own table space
	 *
	 * - `id_conversion` contains properties planned to be converted and move to
	 * a fixed ID
	 *
	 * @since 3.1
	 *
	 * @param string $key
	 *
	 * @return array
	 */
	public static function getFixedProperties( $key = '' ) {

		// PROP_ID => [ ID (SQL), default_fixed, custom_fixed ]
		$fixedProperties = [

			// FIXED ID
			'_TYPE'   => [ 1,  true,  false ],
			'_URI'    => [ 2,  true,  false ],
			'_INST'   => [ 4,  true,  false ],
			'_UNIT'   => [ 7,  true,  false ],
			'_IMPO'   => [ 8,  true,  false ],
			'_PPLB'   => [ 9,  true,  false ],
			'_PDESC'  => [ 10, false, false ],
			'_PREC'   => [ 11, true,  false ],
			'_CONV'   => [ 12, true,  false ],
			'_SERV'   => [ 13, true,  false ],
			'_PVAL'   => [ 14, true,  false ],
			'_REDI'   => [ 15, true,  false ],
			'_DTITLE' => [ 16, true,  false ],
			'_SUBP'   => [ 17, true,  false ],
			'_SUBC'   => [ 18, true,  false ],
			'_CONC'   => [ 19, true,  false ],
			'_ERRP'   => [ 22, false, false ],

			// Properties for encoding (short) lists
			// '_1'  => [ 23, false, false ],
			// '_2'  => [ 24, false, false ],
			// '_3'  => [ 25, false, false ],
			// '_4'  => [ 26, false, false ],
			// '_5'  => [ 27, false, false ],
			'_LIST'  => [ 28, true,  false ],
			'_MDAT'  => [ 29, false, true  ],
			'_CDAT'  => [ 30, false, true  ],
			'_NEWP'  => [ 31, false, true  ],
			'_LEDT'  => [ 32, false, true  ],

			// Properties related to query management
			'_ASK'   => [ 33, true,  false ],
			'_ASKST' => [ 34, true,  false ],
			'_ASKFO' => [ 35, true,  false ],
			'_ASKSI' => [ 36, true,  false ],
			'_ASKDE' => [ 37, true,  false ],
			'_ASKPA' => [ 38, true,  false ],
			'_ASKSC' => [ 39, false, false ],
			'_LCODE' => [ 40, true,  false ],
			'_TEXT'  => [ 41, true,  false ],

			// Due to the potential size of related links, make it a custom_fixed
			// when enabled
			'_ATTCH_LINK' => [ 60, false, true ],

			// NON FIXED ID
			// If you convert an "non" ID fixed property (without an ID) to one
			// with a fixed ID, add the property to the `id_conversion` array
			// so that setup can start the conversion task.

			'_SOBJ'   => [ false, true,  false ],
			'_ASKDU'  => [ false, true,  false ],
			'_MIME'   => [ false, false, true  ],
			'_MEDIA'  => [ false, false, true  ],

		];

		if ( $key === 'id' ) {
			array_walk( $fixedProperties, function( &$v, $k ) { $v = $v[0]; } );
		}

		// Default fixed property table for selected special properties
		if ( $key === 'default_fixed' ) {
			$fixedProperties = array_keys(
				array_filter( $fixedProperties, function( $v ) { return $v[1]; } )
			);
		}

		// Customizable (meaning that there are added or removed via a setting)
		// special properties that can have their own fixed property table
		if ( $key === 'custom_fixed' ) {
			$fixedProperties = array_keys(
				array_filter( $fixedProperties, function( $v ) { return $v[2]; } )
			);
		}

		if ( $key === 'id_conversion' ) {
			$fixedProperties = [];
		}

		return $fixedProperties;
	}

	/**
	 * @since 3.1
	 */
	public static function getOWLPropertyByType( $type ) {

		$types = [
			'_anu' => Exporter::OWL_ANNOTATION_PROPERTY,

			'' => Exporter::OWL_OBJECT_PROPERTY,

			// Page related
			'_wpg' => Exporter::OWL_OBJECT_PROPERTY,
			'_wpp' => Exporter::OWL_OBJECT_PROPERTY,
			'_wpc' => Exporter::OWL_OBJECT_PROPERTY,
			'_wpf' => Exporter::OWL_OBJECT_PROPERTY,
			'_wps' => Exporter::OWL_OBJECT_PROPERTY,
			'_rec' => Exporter::OWL_OBJECT_PROPERTY,
		//	'_mlt_rec' => Exporter::OWL_OBJECT_PROPERTY,
		//	'_ref_rec' => Exporter::OWL_OBJECT_PROPERTY,

			// URI related
			'_uri' => Exporter::OWL_OBJECT_PROPERTY,
			'_ema' => Exporter::OWL_OBJECT_PROPERTY,
			'_tel' => Exporter::OWL_OBJECT_PROPERTY,

			'__typ' => Exporter::OWL_OBJECT_PROPERTY,
			'__spf' => Exporter::OWL_OBJECT_PROPERTY,
			'__spu' => Exporter::OWL_OBJECT_PROPERTY
		];

		if ( isset( $types[$type] ) ) {
			return $types[$type];
		}

		return Exporter::OWL_DATATYPE_PROPERTY;
	}

}
