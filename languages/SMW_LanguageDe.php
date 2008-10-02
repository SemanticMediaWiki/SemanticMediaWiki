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
 * German language labels for important SMW labels (namespaces, datatypes,...).
 *
 * Main translations:
 * "property" --> "Attribut"
 * "type" --> "Datentyp"
 * "special properties" --> "Besondere Attribute"
 * "query" --> "Anfrage"
 * "subquery" --> Teilanfrage
 * "printout statement" --> Ausgabeanweisung
 *
 * @author Markus Krötzsch
 * @ingroup Language
 */
class SMWLanguageDe extends SMWLanguage {

protected $m_DatatypeLabels = array(
	'_wpg' => 'Seite', // name of page datatype
	'_str' => 'Zeichenkette',  // name of the string type
	'_txt' => 'Text',  // name of the text type
	'_cod' => 'Quellcode',  // name of the (source) code type
	'_boo' => 'Wahrheitswert',  // name of the boolean type
	'_num' => 'Zahl', // name for the datatype of numbers
	'_geo' => 'Geografische Koordinaten', // name of the geocoord type
	'_tem' => 'Temperatur',  // name of the temperature type
	'_dat' => 'Datum',  // name of the datetime (calendar) type
	'_ema' => 'Email',  // name of the email type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'URI-Annotation'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'                   => '_uri',
	'Ganze Zahl'            => '_num',
	'Dezimalzahl'           => '_num',
	'Aufzählung'            => '_str',
	// support English aliases:
	'Page'                  => '_wpg',
	'String'                => '_str',
	'Text'                  => '_txt',
	'Code'                  => '_cod',
	'Boolean'               => '_boo',
	'Number'                => '_num',
	'Geographic coordinate' => '_geo',
	'Temperature'           => '_tem',
	'Date'                  => '_dat',
	'Email'                 => '_ema',
	'Annotation URI'        => '_anu'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Datentyp',
	SMW_SP_HAS_URI   => 'Gleichwertige URI',
	SMW_SP_SUBPROPERTY_OF => 'Unterattribut von',
	SMW_SP_DISPLAY_UNITS => 'Einheiten',
	SMW_SP_IMPORTED_FROM => 'Importiert aus',
	SMW_SP_CONVERSION_FACTOR => 'Entspricht',
	SMW_SP_SERVICE_LINK => 'Bietet Service',
	SMW_SP_POSSIBLE_VALUE => 'Erlaubt Wert'
);

protected $m_SpecialPropertyAliases = array(
	'Hat Datentyp'     => SMW_SP_HAS_TYPE,
	'Ausgabeeinheit'   => SMW_SP_DISPLAY_UNITS,
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
	SMW_NS_RELATION       => "Relation",
	SMW_NS_RELATION_TALK  => "Relation_Diskussion",
	SMW_NS_PROPERTY       => "Attribut",
	SMW_NS_PROPERTY_TALK  => "Attribut_Diskussion",
	SMW_NS_TYPE           => "Datentyp",
	SMW_NS_TYPE_TALK      => "Datentyp_Diskussion",
	SMW_NS_CONCEPT        => 'Konzept',
	SMW_NS_CONCEPT_TALK   => 'Konzept_Diskussion'
);

protected $m_NamespaceAliases = array(
	// support English aliases for namespaces
	//'Relation'      => SMW_NS_RELATION,
	'Relation_talk' => SMW_NS_RELATION_TALK,
	'Property'      => SMW_NS_PROPERTY,
	'Property_talk' => SMW_NS_PROPERTY_TALK,
	'Type'          => SMW_NS_TYPE,
	'Type_talk'     => SMW_NS_TYPE_TALK,
	'Concept'       => SMW_NS_CONCEPT,
	'Concept_talk'  => SMW_NS_CONCEPT_TALK
);

protected $m_dateformats = array(array(SMW_Y), array(SMW_MY,SMW_YM), array(SMW_DMY,SMW_MDY,SMW_YMD,SMW_YDM));

protected $m_months = array("Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");

protected $m_monthsshort = array("Jan", "Feb", "Mär", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez");

}
