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
 * Finnish language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author Niklas Laxström
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageFi extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Sivu', // name of page datatype
		'_txt' => 'Teksti',  // name of the text type
		'_cod' => 'Lähdekoodi',  // name of the (source) code type
		'_boo' => 'Boolean',  // name of the boolean type
		'_num' => 'Luku',  // name for the datatype of numbers
		'_geo' => 'Maantieteellinen koordinaatti', // name of the geocoord type
		'_tem' => 'Lämpötila',  // name of the temperature type
		'_dat' => 'Päiväys',  // name of the datetime (calendar) type
		'_ema' => 'Sähköposti',  // name of the email type
		'_uri' => 'URL-osoite',  // name of the URL type
		'_anu' => 'Annotation URI',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Puhelinnumero',  // name of the telephone (URI) type
		'_rec' => 'Tietue', // name of record data type
		'_qty' => 'Määrä', // name of the number type with units of measurement
	);

	protected $m_DatatypeAliases = array(
		'Merkkijono' => '_txt',  // old name of the string type
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'On tyypiltään',
		'_URI'  => 'Yhtäpitävä URI',
		'_SUBP' => 'Yläominaisuus',
		'_SUBC' => 'Yläluokka',
		'_UNIT' => 'Tulostusyksikkö',
		'_IMPO' => 'Tuotu sanastosta',
		'_CONV' => 'Vastaa määrää',
		'_SERV' => 'Tarjoaa palvelun',
		'_PVAL' => 'Mahdollinen arvo',
		'_MDAT' => 'Muokkausaika',
		'_CDAT' => 'Creation date', // TODO: translate
		'_NEWP' => 'Is a new page', // TODO: translate
		'_LEDT' => 'Last editor is', // TODO: translate
		'_ERRP' => 'Sopimaton arvo kentälle',
		'_LIST' => 'Koostuu kentistä',
		'_SOBJ' => 'Has subobject', // TODO: translate
		'_ASK'  => 'Has query', // TODO: translate
		'_ASKST'=> 'Query string', // TODO: translate
		'_ASKFO'=> 'Query format', // TODO: translate
		'_ASKSI'=> 'Query size', // TODO: translate
		'_ASKDE'=> 'Query depth', // TODO: translate
		'_ASKDU'=> 'Query duration', // TODO: translate
		'_MEDIA'=> 'Media type',
		'_MIME' => 'Mime type'
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => 'Ominaisuus',
		SMW_NS_PROPERTY_TALK  => 'Keskustelu_ominaisuudesta',
		SMW_NS_TYPE           => 'Tyyppi',
		SMW_NS_TYPE_TALK      => 'Keskustelu_tyypistä',
		SMW_NS_CONCEPT        => 'Konsepti',
		SMW_NS_CONCEPT_TALK   => 'Keskustelu_konseptista'
	);

	protected $m_months = array(
		"tammikuu",
		"helmikuu",
		"maaliskuu",
		"huhtikuu",
		"toukokuu",
		"kesäkuu",
		"heinäkuu",
		"elokuu",
		"syyskuu",
		"lokakuu",
		"marraskuu",
		"joulukuu"
	);

	protected $m_monthsshort = array(
		"tammikuu",
		"helmikuu",
		"maaliskuu",
		"huhtikuu",
		"toukokuu",
		"kesäkuu",
		"heinäkuu",
		"elokuu",
		"syyskuu",
		"lokakuu",
		"marraskuu",
		"joulukuu"
	);

}
