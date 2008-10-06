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
 * Italian language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Davide Eynard
 * @author David Laniado
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageIt extends SMWLanguage {

protected $m_DatatypeLabels = array(
	'_wpg' => 'Pagina',  // name of page datatypee
	'_str' => 'Stringa',  //name of the string type
	'_txt' => 'Testo',   // name of the text type
	'_cod' => 'Code',  // name of the (source) code type //TODO: translate
	'_boo' => 'Booleano',  // name of the boolean type
	'_num' => 'Numero',  // name for the datatype of numbers
	'_geo' => 'Coordinate geografiche',  // name of the geocoord type
	'_tem' => 'Temperatura',  // name of the temperature type
	'_dat' => 'Data',  // name of the datetime (calendar) type
	'_ema' => 'Email',  // name of the email type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'Annotation URI' // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'         => '_uri',
	'Float'       => '_num',
	'Integer'     => '_num',
	'Intero'      => '_num',
	'Enumeration' => '_str',
	'Enumerazione'=> '_str'
);

protected $m_SpecialProperties = array(
	//always start upper-case
	SMW_SP_HAS_TYPE  => 'Ha tipo', //'Has type',
	SMW_SP_HAS_URI   => 'URI equivalente', //'Equivalent URI',
	SMW_SP_SUBPROPERTY_OF => 'Sottopropriet&agrave; di', // 'Subproperty of',
	SMW_SP_DISPLAY_UNITS => 'Display units', //TODO
	SMW_SP_IMPORTED_FROM => 'Importato da', // 'Imported from',
	SMW_SP_CONVERSION_FACTOR => 'Corrisponde a ', // 'Corresponds to',
	SMW_SP_SERVICE_LINK => 'Fornisce servizio', // 'Provides service',
	SMW_SP_POSSIBLE_VALUE => 'Ammette valore', //'Allows value'
);

protected $m_SpecialPropertyAliases = array(
	'Display unit' => SMW_SP_DISPLAY_UNITS
);

protected $m_Namespaces = array( // TODO: translate (English aliases can be kept, see other language files
	SMW_NS_RELATION       => 'Relation',
	SMW_NS_RELATION_TALK  => 'Relation_talk',
	SMW_NS_PROPERTY       => 'Property',
	SMW_NS_PROPERTY_TALK  => 'Property_talk',
	SMW_NS_TYPE           => 'Type',
	SMW_NS_TYPE_TALK      => 'Type_talk',
	SMW_NS_CONCEPT        => 'Concept',
	SMW_NS_CONCEPT_TALK   => 'Concept_talk'
);

}


