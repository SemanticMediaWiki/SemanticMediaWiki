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
 * English language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Markus Krötzsch
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageEn extends SMWLanguage {

protected $m_DatatypeLabels = array(
	'_wpg' => 'Page', // name of page datatype
	'_str' => 'String',  // name of the string type
	'_txt' => 'Text',  // name of the text type
	'_cod' => 'Code',  // name of the (source) code type
	'_boo' => 'Boolean',  // name of the boolean type
	'_num' => 'Number',  // name for the datatype of numbers
	'_geo' => 'Geographic coordinate', // name of the geocoord type
	'_tem' => 'Temperature',  // name of the temperature type
	'_dat' => 'Date',  // name of the datetime (calendar) type
	'_ema' => 'Email',  // name of the email type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'Annotation URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'         => '_uri',
	'Float'       => '_num',
	'Integer'     => '_num',
	'Enumeration' => '_str'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	'_TYPE' => 'Has type',
	'_URI'  => 'Equivalent URI',
	'_SUBP' => 'Subproperty of',
	'_UNIT' => 'Display units',
	'_IMPO' => 'Imported from',
	'_CONV' => 'Corresponds to',
	'_SERV' => 'Provides service',
	'_PVAL' => 'Allows value',
	'_MDAT' => 'Modification date',
	'_ERRP' => 'Has improper value for'
);

protected $m_SpecialPropertyAliases = array(
	'Display unit' => '_UNIT'
);

protected $m_Namespaces = array(
	SMW_NS_RELATION       => 'Relation',
	SMW_NS_RELATION_TALK  => 'Relation_talk',
	SMW_NS_PROPERTY       => 'Property',
	SMW_NS_PROPERTY_TALK  => 'Property_talk',
	SMW_NS_TYPE           => 'Type',
	SMW_NS_TYPE_TALK      => 'Type_talk',
	SMW_NS_CONCEPT        => 'Concept',
	SMW_NS_CONCEPT_TALK   => 'Concept_talk'
);

protected $m_dateformats = array(array(SMW_Y), array(SMW_MY,SMW_YM), array(SMW_MDY,SMW_DMY,SMW_YMD,SMW_YDM));

protected $m_months = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

protected $m_monthsshort = array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");

}


