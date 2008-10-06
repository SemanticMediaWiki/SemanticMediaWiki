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
 * Spanish language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Javier Calzada Prado, Carmen Jorge García-Reyes, Universidad Carlos III de Madrid, Jesús Espino García
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageEs extends SMWLanguage {

protected $m_DatatypeLabels = array(
	'_wpg' => 'Page', // name of page datatype  //TODO translate
	'_str' => 'Cadena de caracteres',  // name of the string type
	'_txt' => 'Texto',  // name of the text type (very long strings)
	'_cod' => 'Code',  // name of the (source) code type //TODO: translate
	'_boo' => 'Booleano',  // name of the boolean type
	'_num' => 'Número', // name for the datatype of numbers
	'_geo' => 'Coordenadas geográficas', // name of the geocoord type
	'_tem' => 'Temperatura',  // name of the temperature type
	'_dat' => 'Fecha',  // name of the datetime (calendar) type
	'_ema' => 'Dirección electrónica',  // name of the email type
	'_uri' => 'URL',  // name of the URL type
	'_anu' => 'Anotación-URI'  // name of the annotation URI type (OWL annotation property)
);

protected $m_DatatypeAliases = array(
	'URI'                   => '_uri',
	'Número entero'         => '_num',
	'Número con coma'       => '_num',
	'Enumeración'           => '_str',
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
	SMW_SP_HAS_TYPE  => 'Tiene tipo de datos',
	SMW_SP_HAS_URI   => 'URI equivalente',
	SMW_SP_SUBPROPERTY_OF => 'Subproperty of', // TODO: translate
	SMW_SP_DISPLAY_UNITS => 'Unidad de medida', // TODO: should be plural now ("units"), singluar stays alias
	SMW_SP_IMPORTED_FROM => 'Importado de',
	SMW_SP_CONVERSION_FACTOR => 'Corresponde a',
	SMW_SP_SERVICE_LINK => 'Provee servicio',
	SMW_SP_POSSIBLE_VALUE => 'Permite el valor'
);

protected $m_SpecialPropertyAliases = array(
	'Unidad de medida'  => SMW_SP_DISPLAY_UNITS,
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
	SMW_NS_RELATION       => "Relación",
	SMW_NS_RELATION_TALK  => "Discusión_relación",
	SMW_NS_PROPERTY       => "Atributo",
	SMW_NS_PROPERTY_TALK  => "Discusión_atributo",
	SMW_NS_TYPE           => "Tipos_de_datos",
	SMW_NS_TYPE_TALK      => "Discusión_tipos_de_datos",
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


