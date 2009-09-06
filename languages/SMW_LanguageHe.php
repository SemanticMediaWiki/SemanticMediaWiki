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
);

protected $m_SpecialProperties = array(
	//always start upper-case
	'_TYPE'  => 'מטיפוס',
	'_URI'   => 'מזהה יחודי תואם',
	'_SUBP' => 'Subproperty of', // TODO: translate
	'_UNIT' => 'יחידת הצגה', // TODO: should be plural now ("units"), singluar stays alias//
	'_IMPO' => 'יובא מ',
	'_CONV' => 'מתורגם ל',
	'_SERV' => 'מספק שירות',
	'_PVAL' => 'ערכים אפשריים', //   TODO: check translation, should be singular value//
	'_MDAT' => 'Modification date',  // TODO: translate
	'_ERRP' => 'Has improper value for' // TODO: translate
);

protected $m_SpecialPropertyAliases = array(
	'יחידת הצגה'
	                    => '_UNIT',
);


protected $m_Namespaces = array(
	SMW_NS_PROPERTY       => 'תכונה',
	SMW_NS_PROPERTY_TALK  => 'שיחת_תכונה',
	SMW_NS_TYPE           => 'טיפוס',
	SMW_NS_TYPE_TALK      => 'שיחת_טיפוס',
	SMW_NS_CONCEPT        => 'Concept', // TODO: translate
	SMW_NS_CONCEPT_TALK   => 'Concept_talk' // TODO: translate
);


}
