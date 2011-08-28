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
 * Norwegian language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Kirill Miazine km\@krot.org
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageNo extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Side', // name of page datatype
		'_str' => 'Kort tekst', // name of the string type
		'_txt' => 'Lang tekst', // name of the text type (very long strings)
		'_cod' => 'Kode', // name of the (source) code type
		'_boo' => 'Boolsk', // name of the boolean type
		'_num' => 'Tall', // name for the datatype of numbers
		'_geo' => 'Geografisk koordinat', // name of the geocoord type
		'_tem' => 'Temperatur',  // name of the temperature type
		'_dat' => 'Dato',  // name of the datetime (calendar) type
		'_ema' => 'E-post',  // name of the email type
		'_uri' => 'URL',  // name of the URL type
		'_anu' => 'URI-merknad', // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Telephone number',  // name of the telephone (URI) type //TODO: translate
		'_rec' => 'Record', // name of record data type //TODO: translate
	);
	
	protected $m_DatatypeAliases = array(
		'Streng'                => '_str',
		'Linje'                 => '_str',
		'Tekst'                 => '_txt',
		'Hellall'               => '_num',
		'Desimaltall'           => '_num',
		'Liste'                 => '_str',
		'Kildekode'             => '_cod',
		'Koordinat'             => '_geo',
		'Epost'                 => '_ema',
		'URI'                   => '_uri',
		'Nettadresse'           => '_uri'
	);
	
	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'Har type',
		'_URI'  => 'Ekvivalent URI',
		'_SUBP' => 'Underkategori av',
		'_SUBC' => 'Subcategory of', // TODO: translate
		'_UNIT' => 'Visningsenhet',
		'_IMPO' => 'Importert fra',
		'_CONV' => 'Svarer til',
		'_SERV' => 'Tilbyr tjeneste',
		'_PVAL' => 'Tillater verdi',
		'_MDAT' => 'Endringsdato',
		'_ERRP' => 'Feilaktig verdi for',
		'_LIST' => 'Has fields', // TODO: translate
	);
	
	protected $m_SpecialPropertyAliases = array(
		'Type' => '_TYPE',
		'Enhet' => '_UNIT',
		'Synonym URI' => '_URI',
		'Synonym adresse' => '_URI'
	);
	
	
	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => 'Egenskap',
		SMW_NS_PROPERTY_TALK  => 'Egenskap-diskusjon',
		SMW_NS_TYPE           => 'Type',
		SMW_NS_TYPE_TALK      => 'Type-diskusjon',
		SMW_NS_CONCEPT        => 'Konsept',
		SMW_NS_CONCEPT_TALK   => 'Konsept-diskusjon'
	);
	
	protected $m_dateformats = array( array( SMW_Y ), array( SMW_MY, SMW_YM ), array( SMW_DMY, SMW_MDY, SMW_YMD, SMW_YDM ) );
	
	protected $m_months = array( "januar", "februar", "mars", "april", "mai", "juni", "juli", "august", "september", "oktober", "november", "desember" );
	
	protected $m_monthsshort = array( "jan.", "feb.", "mars", "april", "mai", "juni", "juli", "aug.", "sep.", "okt.", "nov.", "des." );

}
