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
 * Dutch language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Siebrand Mazeland
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageNl extends SMWLanguage {

protected $m_DatatypeLabels = array(
	'_wpg' => 'Pagina', // name of page datatype
	'_str' => 'String',  // name of the string type
	'_txt' => 'Tekst',  // name of the text type
	'_cod' => 'Code',  // name of the (source) code type //TODO: translate
	'_boo' => 'Booleans',  // name of the boolean type
	'_num' => 'Number', // name for the datatype of numbers // TODO: translate
	'_geo' => 'Geographische coordinaat', // name of the geocoord type
	'_tem' => 'Temperatuur',  // name of the temperature type
	'_dat' => 'Datum',  // name of the datetime (calendar) type
	'_ema' => 'E-mail',  // name of the email type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'Annotatie URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'                   => '_uri',
	'Integer'               => '_num',
	'Float'                 => '_num',
	'Opsomming'             => '_str',
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
	SMW_SP_HAS_TYPE  => 'Heeft type',
	SMW_SP_HAS_URI   => 'Equivalent URI', // TODO: translate
	SMW_SP_SUBPROPERTY_OF => 'Subeigenschap van',
	SMW_SP_DISPLAY_UNITS => 'Display units', // TODO: translate
	SMW_SP_IMPORTED_FROM => 'Geïmporteerd van',
	SMW_SP_CONVERSION_FACTOR => 'Komt overeen met',
	SMW_SP_SERVICE_LINK => 'Verleent dienst',
	SMW_SP_POSSIBLE_VALUE => 'Geldige waarde'
);

protected $m_SpecialPropertyAliases = array(
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
	SMW_NS_RELATION       => 'Relatie',
	SMW_NS_RELATION_TALK  => 'Overleg_relatie',
	SMW_NS_PROPERTY       => 'Eigenschap',
	SMW_NS_PROPERTY_TALK  => 'Overleg_eigenschap',
	SMW_NS_TYPE           => 'Type',
	SMW_NS_TYPE_TALK      => 'Overleg_type',
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
