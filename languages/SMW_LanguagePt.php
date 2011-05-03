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
 * Portuguese language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Semíramis Herszon, Terry A. Hurlbut
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguagePt extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Página', // name of page datatype
		'_str' => 'Cadeia',  // name of the string type
		'_txt' => 'Texto',  // name of the text type (very long strings)
		'_cod' => 'Código',  // name of the (source) code type //TODO: translate
		'_boo' => 'Variável Booléen',  // name of the boolean type
		'_num' => 'Número', // name for the datatype of numbers
		'_geo' => 'Coordenadas geográficas', // name of the geocoord type
		'_tem' => 'Temperatura',  // name of the temperature type
		'_dat' => 'Data',  // name of the datetime (calendar) type
		'_ema' => 'Email',  // name of the email type (Portuguese does not have another word for this)
		'_uri' => 'URL',  // name of the URI type
		'_anu' => 'Anotação-URI',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Telephone number',  // name of the telephone (URI) type //TODO: translate
		'_rec' => 'Record', // name of record data type //TODO: translate
	);
	
	protected $m_DatatypeAliases = array(
		'URI'                   => '_uri',
		'Número inteiro'        => '_num',
		'Folga'			         => '_num',
		'Enumeração'            => '_str',
	);
	
	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'Tem o tipo',
		'_URI'  => 'URI equivalente',
		'_SUBP' => 'Sub-propriedade de',
		'_SUBC' => 'Subcategory of', // TODO: translate
		'_UNIT' => 'Unidades de amostra',
		'_IMPO' => 'Importado de',
		'_CONV' => 'Corresponde a',
		'_SERV' => 'Fornece o serviço',
		'_PVAL' => 'Permite valor',
		'_MDAT' => 'Modification date',  // TODO: translate
		'_ERRP' => 'Has improper value for', // TODO: translate
		'_LIST' => 'Has fields', // TODO: translate
	);
	
	protected $m_SpecialPropertyAliases = array(
		'Unidade de amostra'  => '_UNIT'
	);
	
	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => "Propriedade",
		SMW_NS_PROPERTY_TALK  => "Discussão_propriedade",
		SMW_NS_TYPE           => "Tipo",
		SMW_NS_TYPE_TALK      => "Discussão_tipo",
		SMW_NS_CONCEPT        => 'Conceito',
		SMW_NS_CONCEPT_TALK   => 'Discussão_conceito'
	);
	
	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_MDY, SMW_DMY, SMW_YMD, SMW_YDM ) );
	
	protected $m_months = array( "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro" );
	
	protected $m_monthsshort = array( "Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez" );

}


