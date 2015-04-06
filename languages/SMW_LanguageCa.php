<?php
/**
 * @file
 * @ingroup SMWLanguage
 */

/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

global $smwgIP;
include_once ( $smwgIP . 'languages/SMW_Language.php' );

/**
 * Catalan language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Toniher
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageCa extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Pàgina', // name of page datatype
		'_txt' => 'Text',  // name of the text type
		'_cod' => 'Codi',  // name of the (source) code type
		'_boo' => 'Booleà',  // name of the boolean type
		'_num' => 'Nombre',  // name for the datatype of numbers
		'_geo' => 'Coordenades geogràfiques', // name of the geocoord type
		'_tem' => 'Temperatura',  // name of the temperature type
		'_dat' => 'Data',  // name of the datetime (calendar) type
		'_ema' => 'Adreça electrònica',  // name of the email type
		'_uri' => 'URL',  // name of the URL type
		'_anu' => 'URI-Anotació',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Número de telèfon',  // name of the telephone (URI) type
		'_rec' => 'Registre', // name of record data type
		'_qty' => 'Quantitat', // name of the number type with units of measurement
	);

	protected $m_DatatypeAliases = array(
		'URI'         => '_uri',
		'Decimal'       => '_num',
		'Enter'     => '_num',
		'Cadena' => '_txt',  // old name of the string type
		'Enumeració' => '_txt',
		'Número de telèfon' => '_tel',
		'Adreça electrònica'       => '_ema',
		'Coordenada geogràfica' => '_geo',
		'Polígon geogràfic'    => '_gpo',
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'Té tipus',
		'_URI'  => 'URI equivalent',
		'_SUBP' => 'Subpropietat de',
		'_SUBC' => 'Subcategoria de',
		'_UNIT' => 'Unitats de mesura',
		'_IMPO' => 'Importat de',
		'_CONV' => 'Correspon a',
		'_SERV' => 'Proporciona servei',
		'_PVAL' => 'Permet valor',
		'_MDAT' => 'Data de modificació',
		'_CDAT' => 'Data de creació',
		'_NEWP' => 'És pàgina nova',
		'_LEDT' => 'Darrer editor és',
		'_ERRP' => 'Té valor incorrecte per a',
		'_LIST' => 'Té camps',
		'_SOBJ' => 'Té subobjecte',
		'_ASK'  => 'Té consulta',
		'_ASKST'=> 'Cadena de consulta',
		'_ASKFO'=> 'Format de consulta',
		'_ASKSI'=> 'Mida de consulta',
		'_ASKDE'=> 'Profunditat de consulta',
		'_ASKDU'=> 'Durada de consulta',
		'_MEDIA'=> 'Tipus Media',
		'_MIME' => 'Tipus MIME'
	);

	protected $m_SpecialPropertyAliases = array(
		'Unitat de mesura' => '_UNIT'
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => 'Propietat',
		SMW_NS_PROPERTY_TALK  => 'Propietat_Discussió',
		SMW_NS_TYPE           => 'Tipus',
		SMW_NS_TYPE_TALK      => 'Tipus_Discussió',
		SMW_NS_CONCEPT        => 'Concepte',
		SMW_NS_CONCEPT_TALK   => 'Concepte_Discussió'
	);

	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_MDY, SMW_DMY, SMW_YMD, SMW_YDM ) );

	protected $m_months = array( "gener", "febrer", "març", "abril", "maig", "juny", "juliol", "agost", "setembre", "octubre", "novembre", "desembre" );

	protected $m_monthsshort = array( "gen", "febr", "març", "abr", "maig", "juny", "jul", "ag", "set", "oct", "nov", "des" );

}


