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
 * Slovak language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author helix84
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageSk extends SMWLanguage {

protected $m_DatatypeLabels = array(
	'_wpg' => 'Page', // name of page datatype  //TODO translate
	'_str' => 'Reťazec',  // name of the string type
	'_txt' => 'Text',  // name of the text type (very long strings) //TODO: translate
	'_cod' => 'Code',  // name of the (source) code type //TODO: translate
	'_boo' => 'Boolean',  // name of the boolean type // TODO: translate
	'_num' => 'Číslo', // name for the datatype of numbers // TODO: check translation (done by pattern matching; mak)
	'_geo' => 'Zemepisné súradnice', // name of the geocoord type
	'_tem' => 'Teplota',  // name of the temperature type
	'_dat' => 'Dátum',  // name of the datetime (calendar) type
	'_ema' => 'Email',  // name of the email type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'URI anotácie'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'                   => '_uri',
	'Celé číslo'            => '_num',
	'Desatinné číslo'       => '_num',
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
	'Annotation URI'        => '_anu'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	'_TYPE' => 'Má typ',
	'_URI'  => 'Ekvivalent URI',
	'_SUBP' => 'Subproperty of', // TODO: translate
	'_UNIT' => 'Zobrazovacia jednotka', // TODO: should be plural now ("units"), singluar stays alias
	'_IMPO' => 'Importovaný z',
	'_CONV' => 'Zodpovedá',
	'_SERV' => 'Poskytuje službu',
	'_PVAL' => 'Allowed value'	//TODO translate
);

protected $m_SpecialPropertyAliases = array(
	'Zobrazovacia jednotka' => '_UNIT',
	// support English aliases for special properties
	'Has type'          => '_TYPE',
	'Equivalent URI'    => '_URI',
	'Subproperty of'    => '_SUBP',
	'Display units'     => '_UNIT',
	'Imported from'     => '_IMPO',
	'Corresponds to'    => '_CONV',
	'Provides service'  => '_SERV',
	'Allows value'      => '_PVAL'
);


protected $m_Namespaces = array(
	SMW_NS_RELATION       => 'Vzťah',
	SMW_NS_RELATION_TALK  => 'Diskusia o vzťahu',
	SMW_NS_PROPERTY       => 'Atribút',
	SMW_NS_PROPERTY_TALK  => 'Diskusia o atribúte',
	SMW_NS_TYPE           => 'Typ',
	SMW_NS_TYPE_TALK      => 'Diskusia o type',
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


