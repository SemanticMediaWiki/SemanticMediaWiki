<?php
/**
 * @file
 * @ingroup SMWLanguage
 */

/*
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if (!defined('MEDIAWIKI')) die();

global $smwgIP;
include_once($smwgIP . '/languages/SMW_Language.php');

/**
 * Hebrew language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Udi Oron אודי אורון
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageHe extends SMWLanguage {

protected $m_DatatypeLabels = array(
	'_wpg' => 'Page', // name of page datatype  //TODO translate
	'_str' => 'מחרוזת',  // name of the string type
	'_txt' => 'Text',  // name of the text type (very long strings) //TODO: translate
	'_cod' => 'Code',  // name of the (source) code type //TODO: translate
	'_boo' => 'נכוןלאנכון',  // name of the boolean type
	'_num' => 'Number', // name for the datatype of numbers //TODO: translate
	'_geo' => 'קורדינטות גיאוגרפיות', // name of the geocoord type
	'_tem' => 'טמפרטורה',  // name of the temperature type
	'_dat' => 'תאריך',  // name of the datetime (calendar) type
	'_ema' => 'דואל',  // name of the email (URI) type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'Annotation URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'מזהה יחודי'
	             => '_uri',
	'שלם'
	             => '_num',
	'נקודהצפה'
	             => '_num',
	// support English aliases:
	'Page'                  => '_wpg',
	'String'                => '_str',
	'Text'                  => '_txt',
	'Boolean'               => '_boo',
	'Number'                => '_num',
	'Geographic coordinate' => '_geo',
	'Temperature'           => '_tem',
	'Date'                  => '_dat',
	'Email'                 => '_ema',
	'URI'                   => '_uri',
	'Annotation URI'        => '_anu'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'מטיפוס',
	SMW_SP_HAS_URI   => 'מזהה יחודי תואם',
	SMW_SP_SUBPROPERTY_OF => 'Subproperty of', // TODO: translate
	SMW_SP_DISPLAY_UNITS => 'יחידת הצגה', // TODO: should be plural now ("units"), singluar stays alias//
	SMW_SP_IMPORTED_FROM => 'יובא מ',
	SMW_SP_CONVERSION_FACTOR => 'מתורגם ל',
	SMW_SP_SERVICE_LINK => 'מספק שירות',
	SMW_SP_POSSIBLE_VALUE => 'ערכים אפשריים' //   TODO: check translation, should be singular value//
);

protected $m_SpecialPropertyAliases = array(
	'יחידת הצגה'
	                    => SMW_SP_DISPLAY_UNITS,
	// support English aliases for special properties
	'Has type'          => SMW_SP_HAS_TYPE,
	'Equivalent URI'    => SMW_SP_HAS_URI,
	'Subproperty of'    => SMW_SP_SUBPROPERTY_OF,
	'Display units'     => SMW_SP_DISPLAY_UNITS,
	'Imported from'     => SMW_SP_IMPORTED_FROM,
	'Corresponds to'    => SMW_SP_CONVERSION_FACTOR,
	'Provides service'  => SMW_SP_SERVICE_LINK,
	'Allows value'      => SMW_SP_POSSIBLE_VALUE
);


protected $m_Namespaces = array(
	SMW_NS_RELATION       => 'יחס',
	SMW_NS_RELATION_TALK  => 'שיחת_יחס',
	SMW_NS_PROPERTY       => 'תכונה',
	SMW_NS_PROPERTY_TALK  => 'שיחת_תכונה',
	SMW_NS_TYPE           => 'טיפוס',
	SMW_NS_TYPE_TALK      => 'שיחת_טיפוס',
	SMW_NS_CONCEPT        => 'Concept', // TODO: translate
	SMW_NS_CONCEPT_TALK   => 'Concept_talk' // TODO: translate
);


protected $m_NamespaceAliases = array(
	// support English aliases for namespaces
	'Relation'      => SMW_NS_RELATION,
	'Relation_talk' => SMW_NS_RELATION_TALK,
	'Property'      => SMW_NS_PROPERTY,
	'Property_talk' => SMW_NS_PROPERTY_TALK,
	'Type'          => SMW_NS_TYPE,
	'Type_talk'     => SMW_NS_TYPE_TALK,
	'Concept'       => SMW_NS_CONCEPT,
	'Concept_talk'  => SMW_NS_CONCEPT_TALK
);

}
