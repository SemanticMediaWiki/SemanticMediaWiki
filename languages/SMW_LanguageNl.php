<?php
/**
 * @file
 * @ingroup Language
 * @ingroup SMWLanguage
 * @author Siebrand Mazeland
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
 * Dutch language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Siebrand Mazeland
 * @author Jan Schoonderbeek
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageNl extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Pagina', // name of page datatype
		'_txt' => 'Tekst',  // name of the text type
		'_cod' => 'Code',  // name of the (source) code type
		'_boo' => 'Booleaans',  // name of the boolean type
		'_num' => 'Getal', // name for the datatype of numbers
		'_geo' => 'Geografische coördinaat', // name of the geocoord type
		'_tem' => 'Temperatuur',  // name of the temperature type
		'_dat' => 'Datum',  // name of the datetime (calendar) type
		'_ema' => 'E-mail',  // name of the email type
		'_uri' => 'URL',  // name of the URL type
		'_anu' => 'Annotatie-URI',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Telefoonnummer',  // name of the telephone (URI) type
		'_rec' => 'Record', // name of record data type
		'_qty' => 'Hoeveelheid', // name of the number type with units of measurement
	);

	protected $m_DatatypeAliases = array(
		'URI'             => '_uri',
		'Drijvende komma' => '_num',
		'Integer'         => '_num',
		'Opsomming'       => '_txt',
		'String'          => '_txt',  // old name of the string type
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'Heeft type',
		'_URI'  => 'Equivalent URI',
		'_SUBP' => 'Subeigenschap van',
		'_SUBC' => 'Subcategorie van',
		'_UNIT' => 'Weergaveeenheden',
		'_IMPO' => 'Geïmporteerd uit',
		'_CONV' => 'Komt overeen met',
		'_SERV' => 'Verleent dienst',
		'_PVAL' => 'Geldige waarde',
		'_MDAT' => 'Wijzigingsdatum',
		'_CDAT' => 'Creatiedatum',
		'_NEWP' => 'Is een nieuwe pagina',
		'_LEDT' => 'Laatste redacteur is',
		'_ERRP' => 'Heeft ongeldige waarde voor',
		'_LIST' => 'Heeft velden',
		'_SOBJ' => 'Heeft subobject',
		'_ASK'  => 'Heeft bevraging',
		'_ASKST'=> 'Zoekstring',
		'_ASKFO'=> 'Zoekopdracht-opmaak',
		'_ASKSI'=> 'Zoekopdracht-omvang',
		'_ASKDE'=> 'Zoekdiepte',
		'_ASKDU'=> 'Zoekduur',
		'_MEDIA'=> 'Heeft Mediatype',
		'_MIME' => 'Heeft MIME-type'
	);

	protected $m_SpecialPropertyAliases = array(
		'Weergave-eenheid'   => '_UNIT',
		'Bevragingsstring'   => '_ASKST',
		'Bevragingsopmaak'   => '_ASKFO',
		'Bevragingsgrootte'  => '_ASKSI',
		'Bevragingsdiepgang' => '_ASKDE'
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => 'Eigenschap',
		SMW_NS_PROPERTY_TALK  => 'Overleg_eigenschap',
		SMW_NS_TYPE           => 'Type',
		SMW_NS_TYPE_TALK      => 'Overleg_type',
		SMW_NS_CONCEPT        => 'Concept',
		SMW_NS_CONCEPT_TALK   => 'Overleg_concept'
	);

	protected $m_months = array( 'januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december' );

	protected $m_monthsshort = array( "jan", "feb", "mar", "apr", "mei", "jun", "jul", "aug", "sep", "okt", "nov", "dec" );

}
