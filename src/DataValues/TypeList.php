<?php

namespace SMW\DataValues;

use SMWDataItem as DataItem;
use SMWPropertyValue as PropertyValue;
use SMWStringValue as StringValue;
use SMWQuantityValue as QuantityValue;
use SMWNumberValue as NumberValue;
use SMWTimeValue as TimeValue;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TypeList {

	/**
	 * @note All IDs must start with an underscore, two underscores indicate a
	 * truly internal (non user-interacted type). All others should also get a
	 * translation in the language files, or they won't be available for users.
	 *
	 * @since 2.5
	 *
	 * @return aray
	 */
	public static function getList() {

		// Expected format
		// ID => Class, DI type, isSubDataType

		return array(
			// Special import vocabulary type
			ImportValue::TYPE_ID => array( ImportValue::class, DataItem::TYPE_BLOB, false ),
			// Property chain
			PropertyChainValue::TYPE_ID => array( PropertyChainValue::class, DataItem::TYPE_BLOB, false ),
			// Property type (possibly predefined, not always based on a page)
			PropertyValue::TYPE_ID => array( PropertyValue::class, DataItem::TYPE_PROPERTY, false ),
			 // Text type
			StringValue::TYPE_ID  => array( StringValue::class, DataItem::TYPE_BLOB, false ),
			 // Code type
			StringValue::TYPE_COD_ID  => array( StringValue::class, DataItem::TYPE_BLOB, false ),
			 // Legacy string ID `_str`
			StringValue::TYPE_LEGACY_ID => array( StringValue::class, DataItem::TYPE_BLOB, false ),
			 // Email type
			'_ema'  => array( 'SMWURIValue', DataItem::TYPE_URI, false ),
			 // URL/URI type
			'_uri'  => array( 'SMWURIValue', DataItem::TYPE_URI, false ),
			 // Annotation URI type
			'_anu'  => array( 'SMWURIValue', DataItem::TYPE_URI, false ),
			 // Phone number (URI) type
			'_tel'  => array( TelephoneUriValue::class, DataItem::TYPE_URI, false ),
			 // Page type
			'_wpg'  => array( 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false ),
			 // Property page type TODO: make available to user space
			'_wpp'  => array( 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false ),
			 // Category page type TODO: make available to user space
			'_wpc'  => array( 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false ),
			 // Form page type for Semantic Forms
			'_wpf'  => array( 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false ),
			 // Number type
			NumberValue::TYPE_ID => array( NumberValue::class, DataItem::TYPE_NUMBER, false ),
			 // Temperature type
			TemperatureValue::TYPE_ID  => array( TemperatureValue::class, DataItem::TYPE_NUMBER, false ),
			 // Time type
			TimeValue::TYPE_ID => array( TimeValue::class, DataItem::TYPE_TIME, false ),
			 // Boolean type
			'_boo'  => array( BooleanValue::class, DataItem::TYPE_BOOLEAN, false ),
			 // Value list type (replacing former nary properties)
			'_rec'  => array( 'SMWRecordValue', DataItem::TYPE_WIKIPAGE, true ),
			MonolingualTextValue::TYPE_ID => array( MonolingualTextValue::class, DataItem::TYPE_WIKIPAGE, true ),
			ReferenceValue::TYPE_ID => array( ReferenceValue::class, DataItem::TYPE_WIKIPAGE, true ),
			 // Geographical coordinates
			'_geo'  => array( null, DataItem::TYPE_GEO, false ),
			 // Geographical polygon
			'_gpo'  => array( null, DataItem::TYPE_BLOB, false ),
			// External identifier
			ExternalIdentifierValue::TYPE_ID => array( ExternalIdentifierValue::class, DataItem::TYPE_BLOB, false ),
			 // Type for numbers with units of measurement
			QuantityValue::TYPE_ID => array( QuantityValue::class, DataItem::TYPE_NUMBER, false ),
			// Special types are not avaialble directly for users (and have no local language name):
			// Special type page type
			'__typ' => array( 'SMWTypesValue', DataItem::TYPE_URI, false ),
			// Special type list for decalring _rec properties
			'__pls' => array( 'SMWPropertyListValue', DataItem::TYPE_BLOB, false ),
			// Special concept page type
			'__con' => array( 'SMWConceptValue', DataItem::TYPE_CONCEPT, false ),
			// Special string type
			'__sps' => array( 'SMWStringValue', DataItem::TYPE_BLOB, false ),
			// Special uri type
			'__spu' => array( 'SMWURIValue', DataItem::TYPE_URI, false ),
			// Special subobject type
			'__sob' => array( 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, true ),
			// Special subproperty type
			'__sup' => array( 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false ),
			// Special subcategory type
			'__suc' => array( 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false ),
			// Special Form page type for Semantic Forms
			'__spf' => array( 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false ),
			// Special instance of type
			'__sin' => array( 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false ),
			// Special redirect type
			'__red' => array( 'SMWWikiPageValue', DataItem::TYPE_WIKIPAGE, false ),
			// Special error type
			'__err' => array( 'SMWErrorValue', DataItem::TYPE_ERROR, false ),
			// Special error type
			'__errt' => array( ErrorMsgTextValue::class, DataItem::TYPE_BLOB, false ),
			// Sort key of a page
			'__key' => array( 'SMWStringValue', DataItem::TYPE_BLOB, false ),
			LanguageCodeValue::TYPE_ID => array( LanguageCodeValue::class, DataItem::TYPE_BLOB, false ),
			AllowsValue::TYPE_ID => array( AllowsValue::class, DataItem::TYPE_BLOB, false ),
			AllowsListValue::TYPE_ID => array( AllowsListValue::class, DataItem::TYPE_BLOB, false ),
			AllowsPatternValue::TYPE_ID => array( AllowsPatternValue::class, DataItem::TYPE_BLOB, false ),
			'__pvuc' => array( UniquenessConstraintValue::class, DataItem::TYPE_BOOLEAN, false ),
			'__pefu' => array( ExternalFormatterUriValue::class, DataItem::TYPE_URI, false )
		);
	}

}
