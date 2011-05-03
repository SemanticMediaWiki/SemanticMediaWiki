<?php
/**
 * @file
 * @ingroup SMWLanguage
 */

/*
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) die();

global $smwgIP;
include_once( $smwgIP . 'languages/SMW_Language.php' );

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
		'_anu' => 'Anotación-URI',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Telephone number',  // name of the telephone (URI) type //TODO: translate
		'_rec' => 'Record', // name of record data type //TODO: translate
	);
	
	protected $m_DatatypeAliases = array(
		'URI'                   => '_uri',
		'Número entero'         => '_num',
		'Número con coma'       => '_num',
		'Enumeración'           => '_str',
	);
	
	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'Tiene tipo de datos',
		'_URI'  => 'URI equivalente',
		'_SUBP' => 'Subproperty of', // TODO: translate
		'_SUBC' => 'Subcategory of', // TODO: translate
		'_UNIT' => 'Unidad de medida', // TODO: should be plural now ("units"), singluar stays alias
		'_IMPO' => 'Importado de',
		'_CONV' => 'Corresponde a',
		'_SERV' => 'Provee servicio',
		'_PVAL' => 'Permite el valor',
		'_MDAT' => 'Modification date',  // TODO: translate
		'_ERRP' => 'Has improper value for', // TODO: translate
		'_LIST' => 'Has fields', // TODO: translate
	);
	
	protected $m_SpecialPropertyAliases = array(
		'Unidad de medida'  => '_UNIT',
	);
	
	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => "Atributo",
		SMW_NS_PROPERTY_TALK  => "Discusión_atributo",
		SMW_NS_TYPE           => "Tipos_de_datos",
		SMW_NS_TYPE_TALK      => "Discusión_tipos_de_datos",
		SMW_NS_CONCEPT        => 'Concept', // TODO: translate
		SMW_NS_CONCEPT_TALK   => 'Concept_talk' // TODO: translate
	);
	
	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_MDY, SMW_DMY, SMW_YMD, SMW_YDM ) );
	
	protected $m_months = array( "enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre" );
	
	protected $m_monthsshort = array( "ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic" );

}


