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
 * Slovak language labels for important SMW labels (namespaces, datatypes,...).
 *
 * @author helix84
 * @author František Simančík
 * @ingroup SMWLanguage
 * @ingroup Language
 */
class SMWLanguageSk extends SMWLanguage {

	protected $m_DatatypeLabels = array(
		'_wpg' => 'Stránka', // name of page datatype
		'_txt' => 'Text',  // name of the text type
		'_cod' => 'Kód',  // name of the (source) code type
		'_boo' => 'Pravdivostná hodnota',  // name of the boolean type
		'_num' => 'Číslo', // name for the datatype of numbers
		'_geo' => 'Zemepisné súradnice', // name of the geocoord type
		'_tem' => 'Teplota',  // name of the temperature type
		'_dat' => 'Dátum',  // name of the datetime (calendar) type
		'_ema' => 'Email',  // name of the email type
		'_uri' => 'URL',  // name of the URL type
		'_anu' => 'URI anotácie',  // name of the annotation URI type (OWL annotation property)
		'_tel' => 'Telefónne číslo',  // name of the telephone (URI) type
		'_rec' => 'Record', // name of record data type //TODO: translate
		'_qty' => 'Rozmer', // name of the number type with units of measurement
	);

	protected $m_DatatypeAliases = array(
		'URI'                   => '_uri',
		'Celé číslo'            => '_num',
		'Desatinné číslo'       => '_num',
		'Reťazec'               => '_txt',  // old name of the string type
	);

	protected $m_SpecialProperties = array(
		// always start upper-case
		'_TYPE' => 'Má typ',
		'_URI'  => 'Ekvivalent URI',
		'_SUBP' => 'Je podatribútom',
		'_SUBC' => 'Je podkategóriou',
		'_UNIT' => 'Zobrazovacie jednotky',
		'_IMPO' => 'Importovaný z',
		'_CONV' => 'Zodpovedá',
		'_SERV' => 'Poskytuje službu',
		'_PVAL' => 'Povolená hodnota',
		'_MDAT' => 'Dátum zmeny',
		'_CDAT' => 'Dátum vytvorenia',
		'_NEWP' => 'Nová stránka',
		'_LEDT' => 'Posledný editor',
		'_ERRP' => 'Má nesprávnu hodnotu pre',
		'_LIST' => 'Has fields', // TODO: translate
		'_SOBJ' => 'Má podobjekt',
		'_ASK'  => 'Má požiadavku',
		'_ASKST'=> 'Reťazec požiadavky',
		'_ASKFO'=> 'Formát požiadavky',
		'_ASKSI'=> 'Veľkosť požiadavky',
		'_ASKDE'=> 'Hĺbka požiadavky',
		'_ASKDU'=> 'Query duration', // TODO: translate
		'_MEDIA'=> 'Media type',
		'_MIME' => 'Mime type'
	);

	protected $m_SpecialPropertyAliases = array(
		'Zobrazovacia jednotka' => '_UNIT'
	);

	protected $m_Namespaces = array(
		SMW_NS_PROPERTY       => 'Atribút',
		SMW_NS_PROPERTY_TALK  => 'Diskusia o atribúte',
		SMW_NS_TYPE           => 'Typ',
		SMW_NS_TYPE_TALK      => 'Diskusia o type',
		SMW_NS_CONCEPT        => 'Concept', // TODO: translate
		SMW_NS_CONCEPT_TALK   => 'Concept_talk' // TODO: translate
	);

}


