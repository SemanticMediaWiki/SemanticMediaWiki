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
 * French language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Pierre Matringe
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageFr extends SMWLanguage {

protected $m_DatatypeLabels = array(
	'_wpg' => 'Page', // name of page datatype
	'_str' => 'Chaîne de caractères',  // name of the string type
	'_txt' => 'Texte',  // name of the text type (very long strings)
	'_cod' => 'Code',  // name of the (source) code type //TODO: translate
	'_boo' => 'Booléen',  // name of the boolean type
	'_num' => 'Nombre', // name for the datatype of numbers
	'_geo' => 'Coordonnées géographiques', // name of the geocoord type
	'_tem' => 'Température',  // name of the temperature type
	'_dat' => 'Date',  // name of the datetime (calendar) type
	'_ema' => 'Adresse électronique',  // name of the email type
	'_uri' => 'URL',  // name of the URI type
	'_anu' => 'Annotation-URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'                   => '_uri',
	'Nombre entier'         => '_num',
	'Nombre décimal'        => '_num',
	'Énumeration'           => '_str',
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
	'_TYPE' => 'A le type',
	'_URI'  => 'URI équivalente',
	'_SUBP' => 'Sous^-propriété de',
	'_UNIT' => 'Unités de mesure',
	'_IMPO' => 'Importé de',
	'_CONV' => 'Correspond à',
	'_SERV' => 'Fournit le service',
	'_PVAL' => 'Valeur possible'
);

protected $m_SpecialPropertyAliases = array(
	'Unité de mesure'   => '_UNIT',
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
	SMW_NS_RELATION       => "Relation",
	SMW_NS_RELATION_TALK  => "Discussion_relation",
	SMW_NS_PROPERTY       => "Attribut",
	SMW_NS_PROPERTY_TALK  => "Discussion_attribut",
	SMW_NS_TYPE           => "Type",
	SMW_NS_TYPE_TALK      => "Discussion_type",
	SMW_NS_CONCEPT        => 'Concept', // TODO: translate
	SMW_NS_CONCEPT_TALK   => 'Concept_talk' // TODO: translate
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

}


